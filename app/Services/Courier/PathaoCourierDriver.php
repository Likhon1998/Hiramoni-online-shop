<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PathaoCourierDriver implements CourierDriver
{
    public function isConfigured(CourierService $courier): bool
    {
        if ($courier->driverKey() !== CourierService::DRIVER_PATHAO || ! $courier->hasApiCredentials()) {
            return false;
        }

        return filled($courier->extra('username'))
            && filled($courier->extra('password'))
            && filled($courier->extra('store_id'));
    }

    public function createConsignment(Order $order, CourierService $courier, string $type, array $options = []): CourierShipment
    {
        $token = $this->token($courier);
        $payload = [
            'store_id' => (int) $courier->extra('store_id'),
            'merchant_order_id' => $type === CourierConsignment::TYPE_RETURN ? $order->invoice_no.'-R' : $order->invoice_no,
            'recipient_name' => $order->delivery_name ?: ($order->customer?->name ?: 'Customer'),
            'recipient_phone' => $this->phone($order->delivery_phone ?: ($order->customer?->phone ?? '')),
            'recipient_address' => $order->delivery_address ?: ($order->customer?->address ?: 'Address on file'),
            'recipient_city' => (int) $courier->extra('city_id', 1),
            'recipient_zone' => (int) $courier->extra('zone_id', 1),
            'delivery_type' => 48,
            'item_type' => 2,
            'special_instruction' => $options['note'] ?? ($order->customer_note ?: ''),
            'item_quantity' => max(1, (int) $order->items->sum('quantity')),
            'item_weight' => 0.5,
            'amount_to_collect' => $type === CourierConsignment::TYPE_RETURN ? 0 : $order->amountCourierCollectsFromCustomer(),
            'item_description' => $order->invoice_no,
        ];

        $response = Http::timeout(20)->acceptJson()->asJson()
            ->withToken($token)
            ->post($courier->baseUrl().'/aladdin/api/v1/orders', $payload);

        $json = $response->json() ?? [];
        $data = $json['data'] ?? $json;
        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? 'Pathao booking failed.');
        }

        $tracking = (string) ($data['consignment_id'] ?? $data['tracking_code'] ?? '');
        if ($tracking === '') {
            throw new CourierBookingException('Pathao did not return a consignment id.');
        }

        return new CourierShipment(
            trackingNumber: $tracking,
            consignmentId: $tracking,
            status: CourierStatusMapper::map($data['order_status'] ?? 'Pending'),
            courierStatus: (string) ($data['order_status'] ?? 'Pending'),
            remote: true,
            payload: $data,
        );
    }

    public function fetchStatus(CourierConsignment $consignment, CourierService $courier): CourierShipment
    {
        $id = $consignment->consignment_id ?: $consignment->tracking_number;
        $response = Http::timeout(20)->acceptJson()
            ->withToken($this->token($courier))
            ->get($courier->baseUrl().'/aladdin/api/v1/orders/'.$id.'/info');

        $json = $response->json() ?? [];
        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? 'Pathao status lookup failed.');
        }

        $data = $json['data'] ?? $json;
        $raw = (string) ($data['order_status'] ?? $consignment->courier_status);

        return new CourierShipment(
            trackingNumber: (string) ($data['consignment_id'] ?? $consignment->tracking_number),
            consignmentId: (string) ($data['consignment_id'] ?? $consignment->consignment_id),
            status: CourierStatusMapper::map($raw),
            courierStatus: $raw,
            remote: true,
            payload: $data,
        );
    }

    protected function token(CourierService $courier): string
    {
        $cacheKey = 'courier:pathao:token:'.$courier->id;

        return Cache::remember($cacheKey, 3500, function () use ($courier) {
            $response = Http::timeout(20)->acceptJson()->asJson()->post($courier->baseUrl().'/aladdin/api/v1/issue-token', [
                'client_id' => $courier->api_key,
                'client_secret' => $courier->api_secret,
                'username' => $courier->extra('username'),
                'password' => $courier->extra('password'),
                'grant_type' => 'password',
            ]);
            $json = $response->json() ?? [];
            $token = $json['access_token'] ?? ($json['data']['access_token'] ?? null);
            if (! $response->successful() || ! $token) {
                throw new CourierBookingException($json['message'] ?? 'Pathao login failed. Check API credentials.');
            }

            return (string) $token;
        });
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
