<?php

namespace App\Services;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use App\Services\Courier\CourierBookingException;
use App\Services\Courier\CourierDriverFactory;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\Log;

class CourierStatusSyncService
{
    public function __construct(
        protected CourierDriverFactory $factory,
        protected OrderWorkflowService $workflow,
    ) {}

    public function syncOpen(?int $shopId = null, int $limit = 80): int
    {
        $query = CourierConsignment::query()
            ->open()
            ->where('is_remote', true)
            ->with(['order', 'courierService'])
            ->orderBy('last_synced_at')
            ->limit($limit);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $updated = 0;
        foreach ($query->get() as $consignment) {
            if ($this->syncOne($consignment)) {
                $updated++;
            }
        }

        return $updated;
    }

    public function syncOrder(Order $order): int
    {
        $updated = 0;
        foreach ($order->consignments()->with('courierService')->get() as $consignment) {
            if ($this->syncOne($consignment)) {
                $updated++;
            }
        }

        return $updated;
    }

    public function applyRemoteStatus(CourierConsignment $consignment, string $rawStatus, array $payload = []): bool
    {
        $mapped = \App\Services\Courier\CourierStatusMapper::map($rawStatus);
        $changed = $consignment->status !== $mapped || $consignment->courier_status !== $rawStatus;

        $updates = [
            'status' => $mapped,
            'courier_status' => $rawStatus,
            'payload' => $payload !== [] ? array_merge($consignment->payload ?? [], $payload) : $consignment->payload,
            'last_synced_at' => now(),
            'last_error' => null,
        ];

        if ($mapped === CourierConsignment::STATUS_DELIVERED && ! $consignment->delivered_at) {
            $updates['delivered_at'] = now();
        }
        if (in_array($mapped, [CourierConsignment::STATUS_FAILED, CourierConsignment::STATUS_RETURNED, CourierConsignment::STATUS_CANCELLED], true) && ! $consignment->failed_at) {
            $updates['failed_at'] = now();
        }

        $consignment->update($updates);
        $consignment->courierService?->forceFill(['last_synced_at' => now()])->save();

        $this->mirrorOrder($consignment->fresh(['order']), $mapped);

        return $changed;
    }

    public function syncOne(CourierConsignment $consignment): bool
    {
        $courier = $consignment->courierService;
        if (! $courier || ! $consignment->tracking_number) {
            return false;
        }

        $driver = $this->factory->apiDriver($courier);
        if (! $driver) {
            $consignment->update(['last_synced_at' => now()]);

            return false;
        }

        try {
            $shipment = $driver->fetchStatus($consignment, $courier);

            return $this->applyRemoteStatus($consignment, (string) $shipment->courierStatus, $shipment->payload);
        } catch (CourierBookingException $e) {
            $consignment->update([
                'last_error' => $e->getMessage(),
                'last_synced_at' => now(),
            ]);
            Log::notice('Courier status sync failed.', [
                'consignment_id' => $consignment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function mirrorOrder(CourierConsignment $consignment, string $mapped): void
    {
        $order = $consignment->order;
        if (! $order || ! $order->isOnlineOrder()) {
            return;
        }

        $from = $order->workflowStatus();

        try {
            if ($consignment->type === CourierConsignment::TYPE_FORWARD) {
                if ($mapped === CourierConsignment::STATUS_DELIVERED && $from === OrderStatus::SHIPPED) {
                    $this->workflow->transition($order, OrderStatus::DELIVERED, [
                        'note' => 'Courier confirmed delivery ('.$consignment->tracking_number.').',
                        'skip_return_booking' => true,
                    ]);
                }

                if (in_array($mapped, [CourierConsignment::STATUS_FAILED, CourierConsignment::STATUS_RETURNED, CourierConsignment::STATUS_CANCELLED], true)
                    && in_array($from, [OrderStatus::SHIPPED, OrderStatus::DELIVERED], true)) {
                    $this->workflow->transition($order, OrderStatus::RETURNED, [
                        'note' => 'Courier reported delivery failure / return ('.$consignment->tracking_number.').',
                        'return_reason' => 'Courier: '.($consignment->courier_status ?: $mapped),
                        'skip_return_booking' => true,
                    ]);
                }
            }

            if ($consignment->type === CourierConsignment::TYPE_RETURN
                && $mapped === CourierConsignment::STATUS_DELIVERED
                && $from === OrderStatus::RETURN_REQUESTED) {
                $this->workflow->transition($order, OrderStatus::RETURNED, [
                    'note' => 'Return parcel received at the warehouse ('.$consignment->tracking_number.').',
                    'skip_return_booking' => true,
                ]);
            }
        } catch (OrderWorkflowException $e) {
            Log::notice('Courier sync could not update order status.', [
                'order_id' => $order->id,
                'from' => $from,
                'mapped' => $mapped,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
