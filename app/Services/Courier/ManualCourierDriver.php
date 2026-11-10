<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use Illuminate\Support\Str;

class ManualCourierDriver implements CourierDriver
{
    public function isConfigured(CourierService $courier): bool
    {
        return true;
    }

    public function createConsignment(Order $order, CourierService $courier, string $type, array $options = []): CourierShipment
    {
        $tracking = trim((string) ($options['tracking_number'] ?? ''));
        if ($tracking === '') {
            $prefix = $type === CourierConsignment::TYPE_RETURN ? 'RET' : 'SHP';
            $tracking = 'HM-'.$prefix.'-'.preg_replace('/[^A-Za-z0-9]/', '', (string) $order->invoice_no).'-'.Str::upper(Str::random(4));
        }

        return new CourierShipment(
            trackingNumber: $tracking,
            consignmentId: $tracking,
            status: CourierConsignment::STATUS_BOOKED,
            courierStatus: 'booked_locally',
            remote: false,
            payload: ['source' => 'manual'],
        );
    }

    public function fetchStatus(CourierConsignment $consignment, CourierService $courier): CourierShipment
    {
        return new CourierShipment(
            trackingNumber: (string) $consignment->tracking_number,
            consignmentId: $consignment->consignment_id,
            status: $consignment->status,
            courierStatus: $consignment->courier_status,
            remote: false,
            payload: $consignment->payload ?? [],
        );
    }
}
