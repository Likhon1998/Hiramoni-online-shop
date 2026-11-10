<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoCatalogMediaTest extends TestCase
{
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
        $this->assertTrue(Storage::disk('public')->exists('categories/skincare.jpg'));
    }
}
