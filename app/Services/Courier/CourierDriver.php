<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;

interface CourierDriver
{
    public function isConfigured(CourierService $courier): bool;

    public function createConsignment(Order $order, CourierService $courier, string $type, array $options = []): CourierShipment;

    public function fetchStatus(CourierConsignment $consignment, CourierService $courier): CourierShipment;
}
