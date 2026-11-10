<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoCatalogMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_packaged_demo_photos_publish_into_public_storage(): void
    {
        $this->artisan('storefront:publish-demo-media')
            ->expectsOutputToContain('Published')
            ->assertSuccessful();

        $this->assertTrue(
            Storage::disk('public')->exists('products/beauty/revitalift-hyaluronic-serum-1.jpg'),
            'Serum photo should be copied from packaged demo media.'
        );
        $this->assertGreaterThan(
            2000,
            Storage::disk('public')->size('products/beauty/revitalift-hyaluronic-serum-1.jpg')
        );
        $this->assertTrue(Storage::disk('public')->exists('products/beauty/glow-ritual-combo-1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('products/beauty/soft-skin-body-combo-1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('products/beauty/care-lip-balm-duo-1.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('categories/skincare.jpg'));
    }

    public function test_publish_replaces_placeholder_images_on_catalog_products(): void
    {
        $shop = Shop::create(['name' => 'Hira Moni', 'is_active' => true]);
        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Soft Skin Body Combo',
            'barcode' => 'HM-CMB-0004',
            'cost_price' => 400,
            'selling_price' => 890,
            'stock_quantity' => 10,
            'is_published' => true,
            'image' => 'products/beauty/soft-skin-body-combo-placeholder.svg',
        ]);

        $this->artisan('storefront:publish-demo-media')->assertSuccessful();

        $product->refresh();
        $this->assertSame('products/beauty/soft-skin-body-combo-1.jpg', $product->image);
        $this->assertTrue(Storage::disk('public')->exists($product->image));
    }
}
