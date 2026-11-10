<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Services\CustomerSegmentService;
use App\Services\OrderCreationException;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_has_clv_repeat_addresses_notes_block_and_full_order_history(): void
    {
        $admin = $this->shopAdmin();
        $product = Product::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Serum',
            'barcode' => 'CRM-1',
            'cost_price' => 200,
            'selling_price' => 1000,
            'stock_quantity' => 50,
            'is_published' => true,
        ]);

        $place = function () use ($admin, $product) {
            return app(OrderCreationService::class)->place(
                $admin->shop_id,
                ['name' => 'Nadia', 'phone' => '01711999001', 'address' => 'House 12, Gulshan'],
                [['id' => $product->id, 'qty' => 1]],
            )['order'];
        };

        $first = $place();
        $second = $place();
        $customer = $first->customer->fresh();

        $this->assertTrue($customer->addresses()->exists());
        $stats = app(CustomerSegmentService::class)->stats($customer);
        $this->assertSame(2, $stats['orders']);
        $this->assertTrue($stats['is_repeat']);
        $this->assertGreaterThanOrEqual($stats['spent'], $stats['clv']);

        $this->actingAs($admin, 'admin')
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer lifetime value')
            ->assertSee('Repeat customer')
            ->assertSee('Complete order history')
            ->assertSee($first->invoice_no)
            ->assertSee($second->invoice_no)
            ->assertSee('Delivery addresses')
            ->assertSee('Customer notes');

        $this->actingAs($admin, 'admin')->post(route('customers.addresses.store', $customer), [
            'label' => 'Office',
            'address' => 'Level 4, Banani',
            'name' => 'Nadia',
            'phone' => '01711999001',
        ])->assertRedirect();
        $this->assertSame(2, $customer->addresses()->count());

        $this->actingAs($admin, 'admin')->post(route('customers.notes.store', $customer), [
            'body' => 'Prefers evening delivery.',
        ])->assertRedirect();
        $this->assertDatabaseHas('customer_notes', ['customer_id' => $customer->id, 'body' => 'Prefers evening delivery.']);

        $this->actingAs($admin, 'admin')->post(route('customers.block', $customer), [
            'reason' => 'Repeated COD refusals',
        ])->assertRedirect();
        $this->assertTrue($customer->fresh()->is_blocked);

        $this->expectException(OrderCreationException::class);
        $place();
    }
}
