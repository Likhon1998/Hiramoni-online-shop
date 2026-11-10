<?php

namespace App\Services\Courier;

class CourierShipment
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $trackingNumber,
        public readonly ?string $consignmentId,
        public readonly string $status,
        public readonly ?string $courierStatus,
        public readonly bool $remote,
        public readonly array $payload = [],
        public readonly ?string $error = null,
    ) {}
}
