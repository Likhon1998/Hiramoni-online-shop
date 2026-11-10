<?php

namespace App\Http\Controllers;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryReportController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()?->can('manage orders')) {
            abort(403);
        }

        $shopId = (int) Auth::user()->shop_id;
        $start = Carbon::parse($request->input('start_date', now()->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $courierId = $request->integer('courier_id') ?: null;
        $type = $request->input('type', 'forward');
        if (! in_array($type, ['forward', 'return', 'all'], true)) {
            $type = 'forward';
        }

        $query = CourierConsignment::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$start, $end])
            ->with(['order:id,invoice_no,status,delivery_zone,total_amount,delivery_charge,shipping_tracking_no', 'courierService:id,name']);

        if ($courierId) {
            $query->where('courier_service_id', $courierId);
        }
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $rows = (clone $query)->orderByDesc('id')->limit(200)->get();

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $booked = (int) $counts->sum();
        $success = (int) ($counts[CourierConsignment::STATUS_DELIVERED] ?? 0);
        $failed = (int) ($counts[CourierConsignment::STATUS_FAILED] ?? 0)
            + (int) ($counts[CourierConsignment::STATUS_RETURNED] ?? 0)
            + (int) ($counts[CourierConsignment::STATUS_CANCELLED] ?? 0);
        $inTransit = (int) ($counts[CourierConsignment::STATUS_IN_TRANSIT] ?? 0)
            + (int) ($counts[CourierConsignment::STATUS_BOOKED] ?? 0)
            + (int) ($counts[CourierConsignment::STATUS_PENDING] ?? 0);

        $rate = $booked > 0 ? round(($success / $booked) * 100, 1) : 0.0;

        $couriers = CourierService::forShop($shopId)->orderBy('name')->get(['id', 'name']);

        return view('reports.delivery', [
            'rows' => $rows,
            'couriers' => $couriers,
            'startDate' => $start,
            'endDate' => $end,
            'courierId' => $courierId,
            'type' => $type,
            'booked' => $booked,
            'success' => $success,
            'failed' => $failed,
            'inTransit' => $inTransit,
            'rate' => $rate,
            'statusLabels' => OrderStatus::labels(),
        ]);
    }
}
