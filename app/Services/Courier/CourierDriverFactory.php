<?php

namespace App\Services\Courier;

use App\Models\CourierService;

class CourierDriverFactory
{
    public function make(CourierService $courier): CourierDriver
    {
        $driver = match ($courier->driverKey()) {
            CourierService::DRIVER_STEADFAST => app(SteadfastCourierDriver::class),
            CourierService::DRIVER_PATHAO => app(PathaoCourierDriver::class),
            CourierService::DRIVER_REDX => app(RedxCourierDriver::class),
            default => app(ManualCourierDriver::class),
        };

        return $driver->isConfigured($courier) ? $driver : app(ManualCourierDriver::class);
    }

    public function apiDriver(CourierService $courier): ?CourierDriver
    {
        $driver = match ($courier->driverKey()) {
            CourierService::DRIVER_STEADFAST => app(SteadfastCourierDriver::class),
            CourierService::DRIVER_PATHAO => app(PathaoCourierDriver::class),
            CourierService::DRIVER_REDX => app(RedxCourierDriver::class),
            default => null,
        };

        if (! $driver || ! $driver->isConfigured($courier)) {
            return null;
        }

        return $driver;
    }
}
