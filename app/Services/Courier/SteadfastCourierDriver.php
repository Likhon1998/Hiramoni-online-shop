<?php

namespace App\Services\Courier;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class SteadfastCourierDriver implements CourierDriver
{
    public function isConfigured(CourierService $courier): bool
    {
        return $courier->driverKey() === CourierService::DRIVER_STEADFAST && $courier->hasApiCredentials();
    }

    public function createConsignment(Order $order, CourierService $courier, string $type, array $options = []): CourierShipment
    {
        $invoice = (string) $order->invoice_no;
        if ($type === CourierConsignment::TYPE_RETURN) {
            $invoice .= '-R';
        }

        $body = [
            'invoice' => $invoice,
            'recipient_name' => $order->delivery_name ?: ($order->customer?->name ?: 'Customer'),
            'recipient_phone' => $this->phone($order->delivery_phone ?: ($order->customer?->phone ?? '')),
            'recipient_address' => $order->delivery_address ?: ($order->customer?->address ?: 'Address on file'),
            'cod_amount' => $type === CourierConsignment::TYPE_RETURN ? 0 : $order->amountCourierCollectsFromCustomer(),
            'note' => $options['note'] ?? ($order->customer_note ?: $order->invoice_no),
        ];

        if ($type === CourierConsignment::TYPE_RETURN && filled($options['consignment_id'] ?? null)) {
            $response = $this->client($courier)->post($courier->baseUrl().'/create_return_request', [
                'consignment_id' => $options['consignment_id'],
                'reason' => $options['note'] ?? 'Customer return',
            ]);
            if ($response->successful() && (int) $response->json('status') === 200) {
                $data = $response->json('consignment') ?? $response->json();

                return $this->fromPayload($data, $options['tracking_number'] ?? $order->shipping_tracking_no);
            }
        }

        $response = $this->client($courier)->post($courier->baseUrl().'/create_order', $body);
        $json = $response->json() ?? [];

        if (! $response->successful() || (int) ($json['status'] ?? 0) !== 200) {
            throw new CourierBookingException($json['message'] ?? ('Steadfast booking failed (HTTP '.$response->status().').'));
        }

        $data = $json['consignment'] ?? $json;

        return $this->fromPayload($data, $options['tracking_number'] ?? null);
    }

    public function fetchStatus(CourierConsignment $consignment, CourierService $courier): CourierShipment
    {
        $tracking = $consignment->tracking_number;
        $url = $tracking
            ? $courier->baseUrl().'/status_by_trackingcode/'.rawurlencode($tracking)
            : $courier->baseUrl().'/status_by_cid/'.rawurlencode((string) $consignment->consignment_id);

        $response = $this->client($courier)->get($url);
        $json = $response->json() ?? [];

        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? 'Steadfast status lookup failed.');
        }

        $raw = (string) ($json['delivery_status'] ?? $json['status'] ?? $consignment->courier_status);

        return new CourierShipment(
            trackingNumber: (string) ($json['tracking_code'] ?? $tracking),
            consignmentId: isset($json['consignment_id']) ? (string) $json['consignment_id'] : $consignment->consignment_id,
            status: CourierStatusMapper::map($raw),
            courierStatus: $raw,
            remote: true,
            payload: $json,
        );
    }

    public function testConnection(CourierService $courier): string
    {
        $response = $this->client($courier)->get($courier->baseUrl().'/get_balance');
        $json = $response->json() ?? [];
        if (! $response->successful()) {
            throw new CourierBookingException($json['message'] ?? 'Could not reach Steadfast.');
        }

        $balance = $json['current_balance'] ?? $json['balance'] ?? null;

        return $balance !== null ? 'Connected. Steadfast balance: '.$balance : 'Connected to Steadfast.';
    }

    protected function fromPayload(array $data, ?string $fallbackTracking): CourierShipment
    {
        $tracking = (string) ($data['tracking_code'] ?? $data['tracking_number'] ?? $fallbackTracking ?? '');
        if ($tracking === '') {
            throw new CourierBookingException('Steadfast did not return a tracking number.');
        }

        $raw = (string) ($data['status'] ?? 'in_review');

        return new CourierShipment(
            trackingNumber: $tracking,
            consignmentId: isset($data['consignment_id']) ? (string) $data['consignment_id'] : $tracking,
            status: CourierStatusMapper::map($raw),
            courierStatus: $raw,
            remote: true,
            payload: $data,
        );
    }

    protected function client(CourierService $courier)
    {
        return Http::timeout(20)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Api-Key' => (string) $courier->api_key,
                'Secret-Key' => (string) $courier->api_secret,
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
