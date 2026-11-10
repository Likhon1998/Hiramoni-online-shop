<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_timed_sale_is_charged_and_shown_on_homepage_and_deals(): void
    {
        $admin = $this->shopAdmin();
        SiteSetting::create([
            'default_shop_id' => $admin->shop_id,
            'store_name' => 'Hira Moni',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $product = Product::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Glow Serum Sale',
            'barcode' => 'SALE-1',
            'cost_price' => 200,
            'selling_price' => 1000,
            'stock_quantity' => 10,
            'is_published' => true,
            'is_featured' => true,
        ]);
        $product->applySale(700, now()->subHour(), now()->addDay());
        $product->refresh();

        $this->assertTrue($product->isOnSale());
        $this->assertEquals(700.0, $product->currentPrice());
        $this->assertEquals(1000.0, $product->compareAtPrice());
        $this->assertSame(30, $product->discountPercent());

        $this->get('/')->assertOk()
            ->assertSee('Glow Serum Sale')
            ->assertSee('gw-flash', false)
            ->assertSee('gw-flash-stage', false)
            ->assertSee('-30%')
            ->assertSee('tn-product-save', false)
            ->assertSee('ফ্ল্যাশ')
            ->assertSee('সেল')
            ->assertSee('সব অফার দেখুন')
            ->assertSee('বাঁচান');

        $this->get(route('website.shop', ['filter' => 'deals']))->assertOk()
            ->assertSee('Glow Serum Sale');

        $this->postJson(route('website.cart.sync'), [
            'items' => [['id' => $product->id, 'qty' => 2]],
        ])->assertOk()->assertJsonPath('items.0.price', 700)->assertJsonPath('subtotal', 1400);
    }

    public function test_original_price_shows_as_compare_at_without_a_timed_sale(): void
    {
        $product = new Product([
            'selling_price' => 890,
            'original_price' => 1050,
        ]);

        $this->assertFalse($product->isOnSale());
        $this->assertEquals(890.0, $product->currentPrice());
        $this->assertEquals(1050.0, $product->compareAtPrice());
        $this->assertSame(15, $product->discountPercent());
    }
}
