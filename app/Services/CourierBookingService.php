<?php

namespace App\Services;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use App\Services\Courier\CourierBookingException;
use App\Services\Courier\CourierDriverFactory;
use App\Services\Courier\CourierShipment;
use App\Services\Courier\ManualCourierDriver;
use Illuminate\Support\Facades\Log;

class CourierBookingService
{
    public function __construct(
        protected CourierDriverFactory $factory,
        protected ManualCourierDriver $manual,
    ) {}

    public function bookForward(Order $order, CourierService $courier, ?string $manualTracking = null, ?string $note = null): CourierConsignment
    {
        $existing = $this->latest($order, CourierConsignment::TYPE_FORWARD);
        if ($existing && filled($existing->tracking_number) && ! $existing->isFailure()) {
            return $existing;
        }

        $order->loadMissing(['customer', 'items']);
        $shipment = $this->create($order, $courier, CourierConsignment::TYPE_FORWARD, [
            'tracking_number' => $manualTracking,
            'note' => $note,
        ], requireRemote: false);

        $row = $this->persist($order, $courier, CourierConsignment::TYPE_FORWARD, $shipment, $existing);

        $order->forceFill([
            'courier_service_id' => $courier->id,
            'shipping_courier' => $courier->name,
            'shipping_tracking_no' => $row->tracking_number,
        ])->save();

        return $row;
    }

    public function bookReturn(Order $order, ?CourierService $courier = null, ?string $reason = null): ?CourierConsignment
    {
        $courier ??= $order->courierService;
        if (! $courier) {
            return null;
        }

        $existing = $this->latest($order, CourierConsignment::TYPE_RETURN);
        if ($existing && filled($existing->tracking_number) && $existing->isOpen()) {
            return $existing;
        }

        $forward = $this->latest($order, CourierConsignment::TYPE_FORWARD);
        $order->loadMissing(['customer', 'items']);

        $shipment = $this->create($order, $courier, CourierConsignment::TYPE_RETURN, [
            'note' => $reason ?: $order->return_reason,
            'consignment_id' => $forward?->consignment_id,
            'tracking_number' => null,
        ], requireRemote: false);

        return $this->persist($order, $courier, CourierConsignment::TYPE_RETURN, $shipment, $existing);
    }

    public function latest(Order $order, string $type): ?CourierConsignment
    {
        return CourierConsignment::query()
            ->where('order_id', $order->id)
            ->where('type', $type)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array{tracking_number?: ?string, note?: ?string, consignment_id?: ?string}  $options
     */
    protected function create(Order $order, CourierService $courier, string $type, array $options, bool $requireRemote): CourierShipment
    {
        $driver = $this->factory->make($courier);
        $api = $this->factory->apiDriver($courier);

        try {
            return ($api ?? $driver)->createConsignment($order, $courier, $type, $options);
        } catch (CourierBookingException $e) {
            if ($requireRemote) {
                throw new OrderWorkflowException('Courier booking failed: '.$e->getMessage());
            }

            Log::warning('Courier API booking failed; using local tracking.', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'error' => $e->getMessage(),
            ]);

            $fallback = $this->manual->createConsignment($order, $courier, $type, $options);

            return new CourierShipment(
                trackingNumber: $fallback->trackingNumber,
                consignmentId: $fallback->consignmentId,
                status: $fallback->status,
                courierStatus: $fallback->courierStatus,
                remote: false,
                payload: $fallback->payload,
                error: $e->getMessage(),
            );
        }
    }

    protected function persist(
        Order $order,
        CourierService $courier,
        string $type,
        CourierShipment $shipment,
        ?CourierConsignment $existing,
    ): CourierConsignment {
        $attributes = [
            'shop_id' => $order->shop_id,
            'order_id' => $order->id,
            'courier_service_id' => $courier->id,
            'type' => $type,
            'driver' => $courier->driverKey(),
            'tracking_number' => $shipment->trackingNumber,
            'consignment_id' => $shipment->consignmentId,
            'invoice_ref' => $type === CourierConsignment::TYPE_RETURN ? $order->invoice_no.'-R' : $order->invoice_no,
            'status' => $shipment->status,
            'courier_status' => $shipment->courierStatus,
            'is_remote' => $shipment->remote,
            'payload' => $shipment->payload,
            'last_error' => $shipment->error,
            'booked_at' => now(),
            'last_synced_at' => $shipment->remote ? now() : null,
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return CourierConsignment::create($attributes);
    }
}
