<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CourierService;
use App\Services\Courier\CourierBookingException;
use App\Services\Courier\SteadfastCourierDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourierServiceController extends Controller
{
    use ShopScoped;

    public function index(Request $request)
    {
        CourierService::ensureDefaults($this->shopId());

        $services = CourierService::forShop($this->shopId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $drivers = CourierService::DRIVERS;

        $activeTab = (string) ($request->query('tab') ?: $services->first()?->id ?: 'new');

        return view('cms.couriers.index', compact('services', 'drivers', 'activeTab'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $courier = CourierService::create([
            'shop_id' => $this->shopId(),
            'name' => $data['name'],
            'driver' => $data['driver'],
            'api_base_url' => $data['api_base_url'] ?? null,
            'api_key' => $data['api_key'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'webhook_secret' => $data['webhook_secret'] ?? null,
            'extra_config' => $this->extraConfig($request),
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return redirect()
            ->route('cms.couriers.index', ['tab' => $courier->id])
            ->with('success', 'Courier service added.');
    }

    public function update(Request $request, CourierService $courier)
    {
        $this->authorizeShop($courier);
        $data = $this->validated($request, $courier->id);

        $payload = [
            'name' => $data['name'],
            'driver' => $data['driver'],
            'api_base_url' => $data['api_base_url'] ?? null,
            'webhook_secret' => $data['webhook_secret'] ?: $courier->webhook_secret,
            'extra_config' => array_filter($this->extraConfig($request) + (array) $courier->extra_config),
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key'] = $data['api_key'];
        }
        if (filled($data['api_secret'] ?? null)) {
            $payload['api_secret'] = $data['api_secret'];
        }

        $courier->update($payload);

        return redirect()
            ->route('cms.couriers.index', ['tab' => $courier->id])
            ->with('success', 'Courier service updated.');
    }

    public function destroy(CourierService $courier)
    {
        $this->authorizeShop($courier);

        if ($courier->orders()->exists()) {
            $courier->update(['is_active' => false]);

            return redirect()
                ->route('cms.couriers.index', ['tab' => $courier->id])
                ->with('success', 'Courier has past orders — deactivated instead of deleted.');
        }

        $courier->delete();

        return redirect()
            ->route('cms.couriers.index')
            ->with('success', 'Courier service deleted.');
    }

    public function test(CourierService $courier)
    {
        $this->authorizeShop($courier);

        if ($courier->driverKey() !== CourierService::DRIVER_STEADFAST) {
            return redirect()
                ->route('cms.couriers.index', ['tab' => $courier->id])
                ->with('error', 'Connection test is available for Steadfast. Pathao and RedX are verified when you ship the first parcel.');
        }

        try {
            $message = app(SteadfastCourierDriver::class)->testConnection($courier);
        } catch (CourierBookingException $e) {
            return redirect()
                ->route('cms.couriers.index', ['tab' => $courier->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('cms.couriers.index', ['tab' => $courier->id])
            ->with('success', $message);
    }

    private function extraConfig(Request $request): array
    {
        return array_filter([
            'store_id' => $request->input('store_id'),
            'city_id' => $request->input('city_id'),
            'zone_id' => $request->input('zone_id'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'area_id' => $request->input('area_id'),
            'area_name' => $request->input('area_name'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('courier_services', 'name')
                    ->where(fn ($q) => $q->where('shop_id', $this->shopId()))
                    ->ignore($ignoreId),
            ],
            'driver' => ['required', Rule::in(array_keys(CourierService::DRIVERS))],
            'api_base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'webhook_secret' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);
    }
}
