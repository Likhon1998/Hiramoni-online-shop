<?php

namespace Tests\Feature;

use App\Models\CourierConsignment;
use App\Models\CourierService;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['shop_id' => $this->shop->id, 'role' => 'admin'])->save();
        $this->admin->assignRole('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Lipstick', 'barcode' => 'LIP-1',
            'cost_price' => 100, 'selling_price' => 500, 'stock_quantity' => 20, 'is_published' => true,
        ]);
    }

    private function placeOrder(): Order
    {
        return app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Rima', 'phone' => '01711000000', 'address' => 'Dhanmondi, Dhaka'],
            [['id' => $this->product->id, 'qty' => 1]],
            null,
            ['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'],
        )['order'];
    }

    private function pack(Order $order): Order
    {
        $order->forceFill(['status' => 'packed', 'verified_at' => now()])->save();

        return $order;
    }

    public function test_shipping_without_api_keys_creates_local_consignment_and_tracking(): void
    {
        $courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'Paperfly', 'driver' => 'manual', 'is_active' => true]);
        $order = $this->pack($this->placeOrder());

        $this->actingAs($this->admin, 'admin')
            ->post(route('online-orders.update-status', $order), [
                'status' => 'shipped',
                'courier_service_id' => $courier->id,
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertNotEmpty($order->shipping_tracking_no);
        $this->assertTrue($order->consignments()->forward()->exists());
        $consignment = $order->consignments()->forward()->first();
        $this->assertSame($order->shipping_tracking_no, $consignment->tracking_number);
        $this->assertFalse($consignment->is_remote);
    }

    public function test_steadfast_api_books_consignment_and_sync_marks_delivered(): void
    {
        Http::fake([
            '*create_order*' => Http::response([
                'status' => 200,
                'consignment' => [
                    'consignment_id' => 1424107,
                    'tracking_code' => 'SF-LIVE-99',
                    'status' => 'in_review',
                ],
            ], 200),
            '*status_by_trackingcode*' => Http::response([
                'status' => 200,
                'delivery_status' => 'delivered',
                'tracking_code' => 'SF-LIVE-99',
            ], 200),
        ]);

        $courier = CourierService::create([
            'shop_id' => $this->shop->id,
            'name' => 'Steadfast',
            'driver' => 'steadfast',
            'api_key' => 'key-1',
            'api_secret' => 'secret-1',
            'is_active' => true,
        ]);
        $order = $this->pack($this->placeOrder());

        $this->actingAs($this->admin, 'admin')
            ->post(route('online-orders.update-status', $order), [
                'status' => 'shipped',
                'courier_service_id' => $courier->id,
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('SF-LIVE-99', $order->shipping_tracking_no);
        $consignment = $order->consignments()->forward()->first();
        $this->assertTrue($consignment->is_remote);
        $this->assertSame('1424107', $consignment->consignment_id);

        $this->actingAs($this->admin, 'admin')
            ->post(route('online-orders.sync-courier', $order))
            ->assertSessionHas('success');

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(CourierConsignment::STATUS_DELIVERED, $consignment->fresh()->status);
    }

    public function test_webhook_updates_delivery_status(): void
    {
        $order = $this->pack($this->placeOrder());
        $courier = CourierService::create([
            'shop_id' => $this->shop->id, 'name' => 'Steadfast', 'driver' => 'steadfast',
            'webhook_secret' => 'hook-secret', 'is_active' => true,
        ]);
        $order->forceFill([
            'status' => 'shipped',
            'courier_service_id' => $courier->id,
            'shipping_courier' => 'Steadfast',
            'shipping_tracking_no' => 'SF-WH-1',
            'shipped_at' => now(),
        ])->save();
        CourierConsignment::create([
            'shop_id' => $this->shop->id,
            'order_id' => $order->id,
            'courier_service_id' => $courier->id,
            'type' => CourierConsignment::TYPE_FORWARD,
            'driver' => 'steadfast',
            'tracking_number' => 'SF-WH-1',
            'status' => CourierConsignment::STATUS_IN_TRANSIT,
            'is_remote' => true,
            'booked_at' => now(),
        ]);

        $this->postJson(route('webhooks.courier', 'steadfast'), [
            'tracking_code' => 'SF-WH-1',
            'delivery_status' => 'delivered',
            'secret' => 'hook-secret',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_return_request_creates_return_consignment(): void
    {
        $courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'Pathao', 'driver' => 'manual', 'is_active' => true]);
        $order = $this->pack($this->placeOrder());
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), [
            'status' => 'shipped', 'courier_service_id' => $courier->id,
        ]);
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), ['status' => 'delivered']);
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), [
            'status' => 'return_requested', 'return_reason' => 'Wrong shade',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('return_requested', $order->status);
        $return = $order->consignments()->returns()->first();
        $this->assertNotNull($return);
        $this->assertNotEmpty($return->tracking_number);
        $this->assertSame(CourierConsignment::TYPE_RETURN, $return->type);
    }

    public function test_delivery_report_lists_success_and_failure(): void
    {
        $courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'RedX', 'driver' => 'manual', 'is_active' => true]);
        $order = $this->pack($this->placeOrder());
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), [
            'status' => 'shipped', 'courier_service_id' => $courier->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('reports.delivery', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Success & failure report')
            ->assertSee($order->invoice_no);
    }

    public function test_courier_failed_status_marks_order_returned(): void
    {
        $order = $this->pack($this->placeOrder());
        $courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'Steadfast', 'driver' => 'steadfast', 'is_active' => true]);
        $order->forceFill([
            'status' => 'shipped', 'courier_service_id' => $courier->id,
            'shipping_tracking_no' => 'SF-FAIL', 'shipped_at' => now(),
        ])->save();
        $consignment = CourierConsignment::create([
            'shop_id' => $this->shop->id,
            'order_id' => $order->id,
            'courier_service_id' => $courier->id,
            'type' => CourierConsignment::TYPE_FORWARD,
            'driver' => 'steadfast',
            'tracking_number' => 'SF-FAIL',
            'status' => CourierConsignment::STATUS_IN_TRANSIT,
            'is_remote' => true,
        ]);

        app(\App\Services\CourierStatusSyncService::class)->applyRemoteStatus($consignment, 'returned');

        $this->assertSame('returned', $order->fresh()->status);
        $this->assertSame(CourierConsignment::STATUS_RETURNED, $consignment->fresh()->status);
        $this->assertSame(0, $order->consignments()->returns()->count());
    }

    public function test_courier_settings_page_uses_a_tabbed_panel(): void
    {
        CourierService::ensureDefaults($this->shop->id);

        $this->actingAs($this->admin, 'admin')
            ->get(route('cms.couriers.index'))
            ->assertOk()
            ->assertSee('How parcels are booked')
            ->assertSee('Pathao')
            ->assertSee('Steadfast')
            ->assertSee('+ Add')
            ->assertSee('Courier services')
            ->assertDontSee('Pathao store id');
    }
}
