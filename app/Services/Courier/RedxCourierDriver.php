<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class RedxCourierDriver implements CourierDriver
{
    public function isConfigured(CourierService $courier): bool
    {
        return $courier->driverKey() === CourierService::DRIVER_REDX
            && $courier->hasApiCredentials()
            && filled($courier->extra('area_id'));
    }

    public function createConsignment(Order $order, CourierService $courier, string $type, array $options = []): CourierShipment
    {
        $payload = [
            'customer_name' => $order->delivery_name ?: ($order->customer?->name ?: 'Customer'),
            'customer_phone' => $this->phone($order->delivery_phone ?: ($order->customer?->phone ?? '')),
            'delivery_area' => (string) $courier->extra('area_name', 'Dhaka'),
            'delivery_area_id' => (int) $courier->extra('area_id'),
            'customer_address' => $order->delivery_address ?: ($order->customer?->address ?: 'Address on file'),
            'merchant_invoice_id' => $type === CourierConsignment::TYPE_RETURN ? $order->invoice_no.'-R' : $order->invoice_no,
            'cash_collection_amount' => $type === CourierConsignment::TYPE_RETURN ? 0 : $order->amountCourierCollectsFromCustomer(),
            'parcel_weight' => 500,
            'instruction' => $options['note'] ?? ($order->customer_note ?: ''),
        ];

        $response = $this->client($courier)->post($courier->baseUrl().'/v1.0.0-beta/parcel', $payload);
        $json = $response->json() ?? [];
        $data = $json['content'] ?? $json;

        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? ($json['error']['message'] ?? 'RedX booking failed.'));
        }

        $tracking = (string) ($data['tracking_id'] ?? $data['tracking_code'] ?? '');
        if ($tracking === '') {
            throw new CourierBookingException('RedX did not return a tracking id.');
        }

        return new CourierShipment(
            trackingNumber: $tracking,
            consignmentId: $tracking,
            status: CourierConsignment::STATUS_BOOKED,
            courierStatus: (string) ($data['status'] ?? 'created'),
            remote: true,
            payload: $data,
        );
    }

    public function fetchStatus(CourierConsignment $consignment, CourierService $courier): CourierShipment
    {
        $tracking = $consignment->tracking_number;
        $response = $this->client($courier)->get($courier->baseUrl().'/v1.0.0-beta/parcel/info/'.rawurlencode((string) $tracking));
        $json = $response->json() ?? [];
        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? 'RedX status lookup failed.');
        }

        $data = $json['parcel'] ?? ($json['content'] ?? $json);
        $raw = (string) ($data['status'] ?? $data['parcel_status'] ?? $consignment->courier_status);

        return new CourierShipment(
            trackingNumber: (string) ($data['tracking_id'] ?? $tracking),
            consignmentId: (string) ($data['tracking_id'] ?? $consignment->consignment_id),
            status: CourierStatusMapper::map($raw),
            courierStatus: $raw,
            remote: true,
            payload: $data,
        );
    }

    protected function client(CourierService $courier)
    {
        return Http::timeout(20)->acceptJson()->asJson()->withHeaders([
            'API-ACCESS-TOKEN' => 'Bearer '.$courier->api_key,
        ]);
    }

    protected function phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '01700000000';
        if (str_starts_with($digits, '88')) {
            $digits = substr($digits, 2);
        }

        return substr($digits, 0, 11);
    }
}
