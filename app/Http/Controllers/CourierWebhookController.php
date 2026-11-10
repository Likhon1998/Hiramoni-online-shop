<?php

namespace App\Http\Controllers;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Services\CourierStatusSyncService;
use Illuminate\Http\Request;

class CourierWebhookController extends Controller
{
    public function __invoke(Request $request, string $driver, CourierStatusSyncService $sync)
    {
        $payload = $request->all();
        $tracking = $this->trackingFrom($payload);
        if (! $tracking) {
            return response()->json(['ok' => false, 'error' => 'missing tracking'], 422);
        }

        $consignment = CourierConsignment::query()
            ->where('tracking_number', $tracking)
            ->when($driver !== 'any', fn ($q) => $q->where('driver', $driver))
            ->latest('id')
            ->first();

        if (! $consignment) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $courier = CourierService::find($consignment->courier_service_id);
        if ($courier && filled($courier->webhook_secret)) {
            $provided = (string) ($request->header('X-Webhook-Secret') ?: $request->input('secret', ''));
            if (! hash_equals((string) $courier->webhook_secret, $provided)) {
                return response()->json(['ok' => false, 'error' => 'invalid secret'], 403);
            }
        }

        $raw = $this->statusFrom($payload);
        $sync->applyRemoteStatus($consignment, $raw, $payload);

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function trackingFrom(array $payload): ?string
    {
        $consignment = is_array($payload['consignment'] ?? null) ? $payload['consignment'] : [];

        foreach ([
            $payload['tracking_code'] ?? null,
            $payload['tracking_number'] ?? null,
            $payload['tracking_id'] ?? null,
            $consignment['tracking_code'] ?? null,
            $consignment['tracking_number'] ?? null,
        ] as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function statusFrom(array $payload): string
    {
        $consignment = is_array($payload['consignment'] ?? null) ? $payload['consignment'] : [];

        return (string) (
            $payload['delivery_status']
            ?? $payload['status']
            ?? $consignment['status']
            ?? $payload['order_status']
            ?? 'in_transit'
        );
    }
}
