<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;

class CourierStatusMapper
{
    public static function map(?string $raw): string
    {
        $status = strtolower(trim((string) $raw));
        $status = str_replace(['-', ' '], '_', $status);

        return match (true) {
            $status === '' => CourierConsignment::STATUS_BOOKED,
            str_contains($status, 'deliver') && ! str_contains($status, 'pending') && ! str_contains($status, 'undeliver') => CourierConsignment::STATUS_DELIVERED,
            in_array($status, ['completed', 'success', 'picked_and_delivered'], true) => CourierConsignment::STATUS_DELIVERED,
            str_contains($status, 'return') || str_contains($status, 'refuse') || str_contains($status, 'undeliver') => CourierConsignment::STATUS_RETURNED,
            str_contains($status, 'cancel') || str_contains($status, 'fail') || str_contains($status, 'hold') => CourierConsignment::STATUS_FAILED,
            str_contains($status, 'transit') || str_contains($status, 'picked') || str_contains($status, 'dispatch')
                || str_contains($status, 'on_the_way') || str_contains($status, 'assigned') => CourierConsignment::STATUS_IN_TRANSIT,
            str_contains($status, 'pending') || str_contains($status, 'review') || str_contains($status, 'created')
                || str_contains($status, 'booked') => CourierConsignment::STATUS_BOOKED,
            default => CourierConsignment::STATUS_IN_TRANSIT,
        };
    }
}
