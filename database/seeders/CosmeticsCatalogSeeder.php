<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\SiteSetting;
use App\Services\ProductVariantService;
use App\Services\WebsiteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cosmetics storefront catalog: trusted beauty brands + relevant products only.
 * Idempotent via barcode keys HM-BTY-0001…
 */
class CosmeticsCatalogSeeder extends Seeder
{
    private const PHOTO = 'https://images.unsplash.com/%s?w=900&h=900&fit=crop&q=80';

    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop found — skip CosmeticsCatalogSeeder.');

            return;
        }

        $website = app(WebsiteService::class);
        $website->ensureTrustedBrands($shop->id);
        $this->syncTrustedBrandLogos($shop->id);

        ProductAttribute::ensureDefaults($shop->id);

        $categories = $this->ensureCategories($shop->id);
        $brands = $this->trustedBrandMap($shop->id);

        $this->purgeIrrelevantCatalog($shop->id, array_keys($brands));

        $this->publishPackagedImages();
        $count = $this->seedProducts($shop->id, $categories, $brands);

        $this->command?->info("Cosmetics catalog ready: {$count} products across ".count($brands).' trusted brands.');
    }

    /** @return array<string, Brand> keyed by brand name */
    private function trustedBrandMap(int $shopId): array
    {
        $map = [];
        foreach (app(WebsiteService::class)->trustedBrandDefs() as $def) {
            $brand = Brand::where('shop_id', $shopId)
                ->get()
                ->first(fn (Brand $b) => Str::slug($b->name) === $def['slug']
                    || strcasecmp($b->name, $def['name']) === 0);

            if ($brand) {
                $brand->forceFill([
                    'name' => $def['name'],
                    'is_active' => true,
                    'sort_order' => $def['sort'] ?? ($brand->sort_order ?: 10),
                ])->save();
                $map[$def['name']] = $brand->fresh();
            }
        }

        return $map;
    }

    private function syncTrustedBrandLogos(int $shopId): void
    {
        foreach (app(WebsiteService::class)->trustedBrandDefs() as $def) {
            $brand = Brand::where('shop_id', $shopId)
                ->get()
                ->first(fn (Brand $b) => Str::slug($b->name) === $def['slug']
                    || strcasecmp($b->name, $def['name']) === 0);

            if (! $brand) {
                continue;
            }

            $source = public_path('images/brands/'.$def['file']);
            if (! is_file($source)) {
                continue;
            }

            $dest = 'brands/'.$def['file'];
            if (! Storage::disk('public')->exists($dest) || Storage::disk('public')->size($dest) < 100) {
                Storage::disk('public')->put($dest, file_get_contents($source));
            }

            if ($brand->logo_path !== $dest) {
                $brand->update(['logo_path' => $dest]);
            }
        }
    }

    /** @return array<string, Category> */
    private function ensureCategories(int $shopId): array
    {
        $defs = $this->categoryDefs();

        $map = [];
        foreach ($defs as $i => $def) {
            $slug = Str::slug($def['name']);
            $image = $this->storeRemoteImage(sprintf(self::PHOTO, $def['photo']), 'categories', $slug.'.jpg');

            $map[$def['name']] = Category::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => $slug],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'description' => $def['description'],
                    'is_featured' => true,
                    'image_path' => $image,
                ]
            );
        }

        return $map;
    }

    /** @param  list<string>  $keepBrandNames */
    private function purgeIrrelevantCatalog(int $shopId, array $keepBrandNames): void
    {
        $seedBarcodes = [];
        $n = 0;
        foreach ($this->catalog() as $item) {
            $shades = $item['shades'] ?? [null];
            foreach ($shades as $_shade) {
                $n++;
                $seedBarcodes[] = $item['barcode'] ?? 'HM-BTY-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            }
        }

        // Remove every product that is not part of the cosmetics barcode set.
        $ids = Product::where('shop_id', $shopId)
            ->where(function ($q) use ($seedBarcodes) {
                $q->whereNull('barcode')
                    ->orWhere('barcode', '')
                    ->orWhereNotIn('barcode', $seedBarcodes);
            })
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            if (Schema::hasTable('product_variant_values')) {
                DB::table('product_variant_values')->whereIn('product_id', $ids)->delete();
            }
            ProductImage::whereIn('product_id', $ids)->delete();
            Product::whereIn('id', $ids)->delete();
            $this->command?->info('Removed '.$ids->count().' irrelevant products.');
        }

        Brand::where('shop_id', $shopId)
            ->whereNotIn('name', $keepBrandNames)
            ->each(function (Brand $brand) {
                if ($brand->products()->exists()) {
                    return;
                }
                $brand->delete();
            });
    }

    /** @param  array<string, Category>  $categories @param  array<string, Brand>  $brands */
    private function seedProducts(int $shopId, array $categories, array $brands): int
    {
        $n = 0;
        $variants = app(ProductVariantService::class);
        $colorAttr = ProductAttribute::where('shop_id', $shopId)->where('slug', 'color')->first();

        foreach ($this->catalog() as $item) {
            $brand = $brands[$item['brand']] ?? null;
            $category = $categories[$item['category']] ?? null;

            if (! $brand || ! $category) {
                $this->command?->warn("Skip {$item['name']} — missing brand/category.");

                continue;
            }

            $shades = $item['shades'] ?? [null];
            $group = count($shades) > 1
                ? ($item['variant_group'] ?? Str::slug($item['name']))
                : null;

            foreach ($shades as $shadeIndex => $shade) {
                $n++;
                $barcode = $item['barcode'] ?? 'HM-BTY-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
                $shadeName = is_array($shade) ? (string) ($shade['name'] ?? '') : '';
                $shadeHex = is_array($shade) ? (string) ($shade['hex'] ?? '#E91E63') : (string) ($item['accent'] ?? '#E91E63');
                $displayName = $shadeName !== '' ? $item['name'].' — '.$shadeName : $item['name'];

                $images = [];
                foreach ($item['photos'] as $k => $photoId) {
                    $path = $this->storeRemoteImage(
                        sprintf(self::PHOTO, $photoId),
                        'products/beauty',
                        Str::slug($displayName).'-'.($k + 1).'.jpg'
                    );
                    if ($path) {
                        $images[] = $path;
                    }
                }

                if ($images === []) {
                    $images[] = $this->storePlaceholder($displayName, $shadeHex);
                }

                $seed = crc32($barcode);
                $product = Product::updateOrCreate(
                    ['shop_id' => $shopId, 'barcode' => $barcode],
                    [
                        'name' => $displayName,
                        'sku' => 'HM-'.strtoupper(Str::limit(Str::slug($item['brand'], ''), 4, '')).'-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'brand_name' => $brand->name,
                        'variant_group' => $group,
                        'color' => $shadeName !== '' ? $shadeName : null,
                        'color_hex' => $shadeName !== '' ? $shadeHex : null,
                        'cost_price' => $item['cost'],
                        'selling_price' => $item['price'],
                        'original_price' => $item['original'] ?? $item['price'],
                        'stock_quantity' => 18 + (($seed + $shadeIndex) % 40),
                        'reserved_stock' => 0,
                        'alert_quantity' => 5,
                        'availability' => 'in_stock',
                        'short_description' => $item['summary'],
                        'description' => $item['description'],
                        'seo_title' => $item['name'].' | '.$brand->name.' | Hira Moni',
                        'meta_description' => Str::limit($item['summary'].' Shop '.$brand->name.' at Hira Moni.', 155, ''),
                        'image' => $images[0] ?? null,
                        'image_2' => $images[1] ?? null,
                        'image_3' => $images[2] ?? null,
                        'rating' => $item['rating'] ?? round(4.3 + ($seed % 6) / 10, 1),
                        'review_count' => $item['reviews'] ?? 24 + ($seed % 160),
                        'is_published' => true,
                        'is_new_arrival' => (bool) ($item['new'] ?? false) && $shadeIndex === 0,
                        'is_best_seller' => (bool) ($item['best'] ?? false) && $shadeIndex === 0,
                        'is_featured' => (bool) ($item['featured'] ?? false) && $shadeIndex === 0,
                        'is_combo' => (bool) ($item['combo'] ?? false),
                        'combo_items' => ! empty($item['combo'])
                            ? implode("\n", (array) ($item['combo_items'] ?? []))
                            : null,
                    ]
                );

                $product->galleryImages()->delete();
                foreach ($images as $sort => $path) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'sort_order' => $sort,
                    ]);
                }

                if ($colorAttr && $shadeName !== '') {
                    $variants->syncValues($product, [
                        $colorAttr->id => ['value' => $shadeName, 'hex' => $shadeHex],
                    ]);
                }

                if (! empty($item['sale_percent']) && $shadeIndex === 0) {
                    $pct = min(80, max(1, (float) $item['sale_percent']));
                    $salePrice = max(1, (float) round($item['price'] * (1 - $pct / 100)));
                    if ($salePrice < (float) $item['price']) {
                        $product->applySale($salePrice, now()->subHour(), now()->addDays(10));
                    }
                }
            }
        }

        return $n;
    }

    /**
     * Copy packaged Unsplash JPEGs into storage using the same filenames the catalog uses.
     * Safe to run on every deploy so LiteSpeed's /storage copy has real product photos.
     */
    public function publishPackagedImages(): int
    {
        $written = 0;

        foreach ($this->catalog() as $item) {
            $shades = $item['shades'] ?? [null];
            foreach ($shades as $shade) {
                $shadeName = is_array($shade) ? (string) ($shade['name'] ?? '') : '';
                $displayName = $shadeName !== '' ? $item['name'].' — '.$shadeName : $item['name'];
                foreach ($item['photos'] as $k => $photoId) {
                    if ($this->publishPackagedPhoto($photoId, 'products/beauty', Str::slug($displayName).'-'.($k + 1).'.jpg')) {
                        $written++;
                    }
                }
            }
        }

        foreach ($this->categoryDefs() as $def) {
            if ($this->publishPackagedPhoto($def['photo'], 'categories', Str::slug($def['name']).'.jpg')) {
                $written++;
            }
        }

        return $written;
    }

    private function categoryDefs(): array
    {
        return [
            ['name' => 'Skincare', 'icon' => 'droplet', 'description' => 'Serums, creams and daily glow essentials', 'photo' => 'photo-1556228720-195a672e8a03'],
            ['name' => 'Makeup', 'icon' => 'lipstick', 'description' => 'Lips, face and eye makeup favourites', 'photo' => 'photo-1596462502278-27bfdc403348'],
            ['name' => 'Hair Care', 'icon' => 'scissors', 'description' => 'Shampoo, conditioner and hair treatments', 'photo' => 'photo-1522338140262-f46f5913618a'],
            ['name' => 'Body Care', 'icon' => 'droplet', 'description' => 'Lotions, washes and body moisturisers', 'photo' => 'photo-1612817288484-6f916006741a'],
            ['name' => 'Fragrance', 'icon' => 'spray', 'description' => 'Light scents and everyday fragrance', 'photo' => 'photo-1541643600914-78b084683601'],
            ['name' => 'Beauty Tools', 'icon' => 'brush', 'description' => 'Brushes, sponges and beauty tools', 'photo' => 'photo-1512496015851-a90fb38ba796'],
            ['name' => 'Nail Care', 'icon' => 'nail', 'description' => 'Polishes, care oils and nail essentials', 'photo' => 'photo-1604654894610-df63bc536371'],
            ['name' => "Men's Grooming", 'icon' => 'grooming', 'description' => 'Face wash, beard care and men’s essentials', 'photo' => 'photo-1621607512214-68297480165e'],
        ];
    }

    private function publishPackagedPhoto(string $photoId, string $directory, string $filename): bool
    {
        $bytes = $this->packagedPhotoBytes($photoId);
        if ($bytes === null) {
            return false;
        }

        Storage::disk('public')->put(trim($directory, '/').'/'.$filename, $bytes);

        return true;
    }

    private function packagedPhotoBytes(?string $photoId): ?string
    {
        if (! $photoId) {
            return null;
        }

        $path = database_path('seeders/assets/unsplash/'.$photoId.'.jpg');
        if (is_file($path) && filesize($path) > 1000) {
            return File::get($path);
        }

        return null;
    }

    private function storePlaceholder(string $name, string $color): string
    {
        $slug = Str::slug($name) ?: 'product';
        $path = 'products/beauty/'.$slug.'-placeholder.svg';
        $label = htmlspecialchars(Str::limit($name, 28, '…'), ENT_QUOTES, 'UTF-8');

        Storage::disk('public')->put($path, <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFF5F8"/><stop offset="1" stop-color="#FCE4EC"/></linearGradient></defs>
  <rect width="800" height="800" fill="url(#g)"/>
  <circle cx="400" cy="360" r="120" fill="{$color}" opacity=".18"/>
  <rect x="300" y="280" width="200" height="260" rx="28" fill="{$color}" opacity=".85"/>
  <text x="400" y="620" text-anchor="middle" font-family="Georgia, serif" font-size="28" fill="#3A1528">{$label}</text>
</svg>
SVG);

        return $path;
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;
        $photoId = preg_match('#(photo-[a-zA-Z0-9_-]+)#', $url, $m) ? $m[1] : null;
        $packaged = $this->packagedPhotoBytes($photoId);

        if ($packaged !== null) {
            Storage::disk('public')->put($relative, $packaged);

            return $relative;
        }

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'HiraMoniCosmeticsSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 500) {
                return null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return [
            // L'Oréal Paris
            [
                'brand' => "L'Oréal Paris", 'category' => 'Skincare', 'name' => 'Revitalift Hyaluronic Serum',
                'cost' => 920, 'price' => 1850, 'original' => 2200, 'best' => true, 'featured' => true,
                'accent' => '#1a1a2e', 'photos' => ['photo-1571781926291-c477ebfd024b', 'photo-1556228578-0d85b1a4d571'],
                'summary' => '1.5% hyaluronic acid serum for plump, hydrated skin.',
                'description' => "L'Oréal Paris Revitalift serum with pure hyaluronic acid.\n\n• Visibly plumps fine lines\n• Lightweight, fast-absorbing texture\n• Suitable for daily AM/PM use",
            ],
            [
                'brand' => "L'Oréal Paris", 'category' => 'Makeup', 'name' => 'True Match Foundation',
                'cost' => 680, 'price' => 1450, 'original' => 1690, 'featured' => true,
                'accent' => '#c49a6c', 'photos' => ['photo-1487412912498-0447578fcca8', 'photo-1522335789203-aabd1fc54bc9'],
                'summary' => 'Natural coverage foundation that blends like your skin.',
                'description' => "True Match foundation with blendable medium coverage.\n\n• Matches skin undertone\n• Breathable finish\n• Buildable for day-to-night looks",
                'shades' => [
                    ['name' => 'Ivory', 'hex' => '#F3E0C8'],
                    ['name' => 'Beige', 'hex' => '#D4A574'],
                    ['name' => 'Golden', 'hex' => '#C49A6C'],
                    ['name' => 'Caramel', 'hex' => '#8D5524'],
                ],
            ],
            [
                'brand' => "L'Oréal Paris", 'category' => 'Hair Care', 'name' => 'Elvive Total Repair Shampoo',
                'cost' => 320, 'price' => 690, 'original' => 850, 'new' => true,
                'accent' => '#6a1b9a', 'photos' => ['photo-1522338242992-e1a54906a8da', 'photo-1519735777090-ec97162dc266'],
                'summary' => 'Repairing shampoo for weak, tired hair.',
                'description' => "Elvive Total Repair cleanses while helping restore strength.\n\n• Reduces breakage feel\n• Soft, shiny finish\n• For daily wash routines",
            ],

            // Maybelline
            [
                'brand' => 'Maybelline New York', 'category' => 'Makeup', 'name' => 'Fit Me Matte + Poreless Foundation',
                'cost' => 520, 'price' => 1200, 'original' => 1390, 'best' => true, 'featured' => true, 'sale_percent' => 20,
                'accent' => '#0096d6', 'photos' => ['photo-1596462502278-27bfdc403348', 'photo-1512496015851-a90fb38ba796'],
                'summary' => 'Oil-control foundation for a natural matte look.',
                'description' => "Maybelline Fit Me foundation for normal to oily skin.\n\n• Soft matte finish\n• Helps blur pores\n• Lightweight everyday wear",
                'shades' => [
                    ['name' => '112 Natural Ivory', 'hex' => '#F0D5B8'],
                    ['name' => '220 Natural Beige', 'hex' => '#D7A878'],
                    ['name' => '330 Toffee', 'hex' => '#A66B3D'],
                    ['name' => '355 Coconut', 'hex' => '#6B3F24'],
                ],
            ],
            [
                'brand' => 'Maybelline New York', 'category' => 'Makeup', 'name' => 'SuperStay Matte Ink Lipstick',
                'cost' => 280, 'price' => 890, 'original' => 990, 'best' => true, 'sale_percent' => 15,
                'accent' => '#c2185b', 'photos' => ['photo-1571875257727-256c39da42af', 'photo-1487412912498-0447578fcca8'],
                'summary' => 'Long-wear liquid lipstick with intense matte colour.',
                'description' => "SuperStay Matte Ink stays put through the day.\n\n• Up to 16-hour wear feel\n• Arrow applicator for precise lips\n• Highly pigmented shades",
                'shades' => [
                    ['name' => 'Pioneer', 'hex' => '#8B1E3F'],
                    ['name' => 'Lover', 'hex' => '#C2185B'],
                    ['name' => 'Loverer', 'hex' => '#E91E63'],
                    ['name' => 'Lover', 'hex' => '#B71C1C'],
                    ['name' => 'Dreamer', 'hex' => '#AD1457'],
                ],
            ],
            [
                'brand' => 'Maybelline New York', 'category' => 'Makeup', 'name' => 'Lash Sensational Mascara',
                'cost' => 260, 'price' => 780, 'original' => 920, 'new' => true,
                'accent' => '#111827', 'photos' => ['photo-1487412912498-0447578fcca8', 'photo-1512496015851-a90fb38ba796'],
                'summary' => 'Fan-effect mascara for fuller-looking lashes.',
                'description' => "Lash Sensational coats from root to tip.\n\n• Soft brush fan effect\n• Buildable volume\n• Everyday black finish",
            ],

            // Pond's
            [
                'brand' => "Pond's", 'category' => 'Skincare', 'name' => 'Bright Beauty Spot-less Cream',
                'cost' => 280, 'price' => 680, 'original' => 790, 'best' => true, 'featured' => true, 'sale_percent' => 25,
                'accent' => '#f4c430', 'photos' => ['photo-1556228720-195a672e8a03', 'photo-1570172619644-dfd03ed5d881'],
                'summary' => 'Daily brightening cream for a spot-less looking glow.',
                'description' => "Pond's Bright Beauty cream for everyday radiance.\n\n• Helps even skin tone look\n• Lightweight moisturising feel\n• Day cream for all skin types",
            ],
            [
                'brand' => "Pond's", 'category' => 'Skincare', 'name' => 'Cold Cream Moisture Whip',
                'cost' => 220, 'price' => 520, 'original' => 620,
                'accent' => '#fff8e7', 'photos' => ['photo-1612817288484-6f916006741a', 'photo-1556228578-0d85b1a4d571'],
                'summary' => 'Classic cold cream for soft, moisturised skin.',
                'description' => "Pond's Cold Cream deeply moisturises dry skin.\n\n• Rich cream texture\n• Softens dry patches\n• Trusted daily care",
            ],
            [
                'brand' => "Pond's", 'category' => 'Skincare', 'name' => 'Age Miracle Day Cream',
                'cost' => 540, 'price' => 1250, 'original' => 1490, 'featured' => true,
                'accent' => '#b71c1c', 'photos' => ['photo-1571781926291-c477ebfd024b', 'photo-1556228720-195a672e8a03'],
                'summary' => 'Anti-ageing day cream for firmer-looking skin.',
                'description' => "Age Miracle day cream with SPF feel protection routine.\n\n• Reduces appearance of fine lines\n• Softens skin texture\n• Morning moisturiser",
            ],

            // Dove
            [
                'brand' => 'Dove', 'category' => 'Body Care', 'name' => 'Nourishing Body Lotion',
                'cost' => 240, 'price' => 520, 'original' => 650, 'best' => true, 'featured' => true, 'sale_percent' => 20,
                'accent' => '#ffffff', 'photos' => ['photo-1612817288484-6f916006741a', 'photo-1556228453-efd6c1ff04f6'],
                'summary' => '24h moisture body lotion with gentle cleansers care.',
                'description' => "Dove body lotion with ¼ moisturising cream.\n\n• Soft, non-greasy feel\n• Suitable for everyday use\n• Leaves skin smooth",
            ],
            [
                'brand' => 'Dove', 'category' => 'Hair Care', 'name' => 'Hair Fall Rescue Shampoo',
                'cost' => 260, 'price' => 590, 'original' => 720, 'new' => true,
                'accent' => '#0d47a1', 'photos' => ['photo-1522338242992-e1a54906a8da', 'photo-1522338140262-f46f5913618a'],
                'summary' => 'Shampoo to help reduce hair fall from breakage.',
                'description' => "Dove Hair Fall Rescue strengthens from wash one.\n\n• Cleanses gently\n• Helps reduce breakage\n• Soft, manageable hair",
            ],
            [
                'brand' => 'Dove', 'category' => 'Body Care', 'name' => 'Beauty Moisture Cream Bar',
                'cost' => 90, 'price' => 180, 'original' => 220,
                'accent' => '#e3f2fd', 'photos' => ['photo-1608248543803-ba4f8c70ae0b', 'photo-1612817288484-6f916006741a'],
                'summary' => 'Moisturising beauty bar for soft, clean skin.',
                'description' => "Dove Beauty Bar is milder than ordinary soap.\n\n• ¼ moisturising cream\n• Gentle on skin\n• Fresh clean feel",
            ],

            // NYX
            [
                'brand' => 'NYX Professional Makeup', 'category' => 'Makeup', 'name' => 'Soft Matte Lip Cream',
                'cost' => 320, 'price' => 890, 'original' => 1050, 'best' => true, 'featured' => true, 'sale_percent' => 18,
                'accent' => '#000000', 'photos' => ['photo-1571875257727-256c39da42af', 'photo-1487412912498-0447578fcca8'],
                'summary' => 'Creamy matte lip colour with a soft-blur finish.',
                'description' => "NYX Soft Matte Lip Cream is a cult favourite.\n\n• Lightweight creamy formula\n• Soft matte payoff\n• Buildable colour",
                'shades' => [
                    ['name' => 'Abu Dhabi', 'hex' => '#C47A6A'],
                    ['name' => 'Copenhagen', 'hex' => '#9C2A3E'],
                    ['name' => 'Rome', 'hex' => '#7A1F2B'],
                    ['name' => 'Tokyo', 'hex' => '#E57373'],
                    ['name' => 'Madrid', 'hex' => '#D32F2F'],
                ],
            ],
            [
                'brand' => 'NYX Professional Makeup', 'category' => 'Makeup', 'name' => 'Setting Spray Matte Finish',
                'cost' => 380, 'price' => 980, 'original' => 1150,
                'accent' => '#212121', 'photos' => ['photo-1512496015851-a90fb38ba796', 'photo-1596462502278-27bfdc403348'],
                'summary' => 'Matte setting spray to lock makeup in place.',
                'description' => "NYX setting spray for long-lasting makeup looks.\n\n• Matte finish mist\n• Helps control shine\n• Easy all-over spray",
            ],
            [
                'brand' => 'NYX Professional Makeup', 'category' => 'Makeup', 'name' => 'Epic Ink Waterproof Liner',
                'cost' => 240, 'price' => 720, 'original' => 850, 'new' => true,
                'accent' => '#111111', 'photos' => ['photo-1522335789203-aabd1fc54bc9', 'photo-1512496015851-a90fb38ba796'],
                'summary' => 'Precision waterproof liquid eyeliner.',
                'description' => "Epic Ink Liner for sharp, smudge-resistant lines.\n\n• Flex tip applicator\n• Intense black pigment\n• Waterproof formula",
            ],

            // Olay
            [
                'brand' => 'Olay', 'category' => 'Skincare', 'name' => 'Regenerist Micro-Sculpting Cream',
                'cost' => 780, 'price' => 1690, 'original' => 1990, 'best' => true, 'featured' => true, 'sale_percent' => 22,
                'accent' => '#c62828', 'photos' => ['photo-1570172619644-dfd03ed5d881', 'photo-1556228720-195a672e8a03'],
                'summary' => 'Firming moisturiser for smoother-looking skin.',
                'description' => "Olay Regenerist cream with amino-peptides & hyaluronic acid.\n\n• Hydrates deeply\n• Improves firmness look\n• Rich yet absorbent texture",
            ],
            [
                'brand' => 'Olay', 'category' => 'Skincare', 'name' => 'Total Effects 7-in-1 Day Cream',
                'cost' => 620, 'price' => 1450, 'original' => 1690,
                'accent' => '#ad1457', 'photos' => ['photo-1571781926291-c477ebfd024b', 'photo-1556228578-0d85b1a4d571'],
                'summary' => 'Multi-benefit day cream against 7 signs of ageing.',
                'description' => "Total Effects day cream for complete daily care.\n\n• Moisturises & brightens look\n• Smooths rough texture\n• Morning skincare staple",
            ],
            [
                'brand' => 'Olay', 'category' => 'Skincare', 'name' => 'Whip Active Moisturiser',
                'cost' => 480, 'price' => 1190, 'original' => 1390, 'new' => true,
                'accent' => '#f8bbd0', 'photos' => ['photo-1556228453-efd6c1ff04f6', 'photo-1612817288484-6f916006741a'],
                'summary' => 'Air-light whipped moisturiser for a fresh finish.',
                'description' => "Olay Whip feels light but hydrates all day.\n\n• Whipped texture\n• Non-greasy feel\n• Ideal under makeup",
            ],

            // Nivea
            [
                'brand' => 'Nivea', 'category' => 'Body Care', 'name' => 'Soft Moisturising Cream',
                'cost' => 160, 'price' => 380, 'original' => 450, 'best' => true, 'featured' => true,
                'accent' => '#1565c0', 'photos' => ['photo-1556228453-efd6c1ff04f6', 'photo-1612817288484-6f916006741a'],
                'summary' => 'Light moisturising cream for face, body and hands.',
                'description' => "Nivea Soft with Vitamin E and jojoba oil.\n\n• Fast-absorbing\n• For face & body\n• Fresh soft scent",
            ],
            [
                'brand' => 'Nivea', 'category' => 'Body Care', 'name' => 'Rich Nourishing Body Milk',
                'cost' => 220, 'price' => 520, 'original' => 640,
                'accent' => '#0d47a1', 'photos' => ['photo-1612817288484-6f916006741a', 'photo-1608248543803-ba4f8c70ae0b'],
                'summary' => 'Rich body milk for very dry skin.',
                'description' => "Nivea Body Milk deeply nourishes dry skin.\n\n• 48h moisture feel\n• Almond oil care\n• Smooth after-bath ritual",
            ],
            [
                'brand' => 'Nivea', 'category' => 'Skincare', 'name' => 'Care Lip Balm Duo',
                'cost' => 90, 'price' => 220, 'original' => 280, 'new' => true,
                'accent' => '#1976d2', 'photos' => ['photo-1487412912498-0447578fcca8', 'photo-1571875257727-256c39da42af'],
                'summary' => 'Classic Nivea lip care for soft, protected lips.',
                'description' => "Nivea Lip Balm keeps lips smooth all day.\n\n• Shea butter care\n• Pocket-friendly tin/stick\n• Everyday essential",
            ],

            // Nail Care
            [
                'brand' => 'NYX Professional Makeup', 'category' => 'Nail Care', 'name' => 'Shine Loud Nail Lacquer',
                'cost' => 180, 'price' => 450, 'original' => 520, 'best' => true, 'featured' => true, 'new' => true,
                'accent' => '#c2185b', 'photos' => ['photo-1604654894610-df63bc536371', 'photo-1519014816548-bf5fe059798b'],
                'summary' => 'High-shine nail colour with a smooth, even finish.',
                'description' => "NYX Shine Loud lacquer for bold, glossy nails.\n\n• Buildable colour\n• Quick-dry feel\n• Everyday glam shades",
            ],
            [
                'brand' => "L'Oréal Paris", 'category' => 'Nail Care', 'name' => 'Color Riche Nail Care Oil',
                'cost' => 220, 'price' => 590, 'original' => 690, 'featured' => true,
                'accent' => '#f8bbd0', 'photos' => ['photo-1519014816548-bf5fe059798b', 'photo-1604654894610-df63bc536371'],
                'summary' => 'Nourishing nail & cuticle oil for softer tips.',
                'description' => "L'Oréal nail care oil hydrates cuticles daily.\n\n• Softens dry cuticles\n• Lightweight oil texture\n• Perfect prep before polish",
            ],

            // Men's Grooming
            [
                'brand' => 'Nivea', 'category' => "Men's Grooming", 'name' => 'Men Deep Clean Face Wash',
                'cost' => 160, 'price' => 390, 'original' => 480, 'best' => true, 'featured' => true, 'new' => true,
                'accent' => '#0d47a1', 'photos' => ['photo-1621607512214-68297480165e', 'photo-1515377905703-c4788e51af15'],
                'summary' => 'Deep-cleansing face wash for men’s everyday skincare.',
                'description' => "Nivea Men face wash removes dirt and excess oil.\n\n• Fresh clean feel\n• For normal to oily skin\n• Morning & night use",
            ],
            [
                'brand' => 'Dove', 'category' => "Men's Grooming", 'name' => 'Men+Care Hydrate Face Cream',
                'cost' => 280, 'price' => 690, 'original' => 820, 'featured' => true,
                'accent' => '#1565c0', 'photos' => ['photo-1515377905703-c4788e51af15', 'photo-1621607512214-68297480165e'],
                'summary' => 'Lightweight hydrating cream for men’s skin.',
                'description' => "Dove Men+Care moisturises without a heavy feel.\n\n• Non-greasy finish\n• Helps reduce dryness\n• Daily post-wash cream",
            ],

            // Combo packs (homepage Beauty Combo Sets)
            [
                'barcode' => 'HM-CMB-0001', 'combo' => true, 'featured' => true, 'best' => true,
                'brand' => "L'Oréal Paris", 'category' => 'Skincare', 'name' => 'Glow Ritual Combo',
                'cost' => 1480, 'price' => 2490, 'original' => 3050,
                'accent' => '#1a1a2e', 'photos' => ['photo-1571781926291-c477ebfd024b', 'photo-1556228578-0d85b1a4d571'],
                'summary' => 'Serum + day cream kit for a hydrated morning glow.',
                'description' => "A complete AM skincare ritual from L'Oréal Paris.\n\n• Hyaluronic serum for plump skin\n• Lightweight day cream to lock in moisture\n• Save versus buying each item alone",
                'combo_items' => [
                    '1x Revitalift Hyaluronic Serum 30ml',
                    '1x Revitalift Day Cream 50ml',
                    'Travel pouch',
                ],
            ],
            [
                'barcode' => 'HM-CMB-0002', 'combo' => true, 'featured' => true,
                'brand' => 'Maybelline New York', 'category' => 'Makeup', 'name' => 'Everyday Makeup Combo',
                'cost' => 1060, 'price' => 2290, 'original' => 2870,
                'accent' => '#c2185b', 'photos' => ['photo-1596462502278-27bfdc403348', 'photo-1571875257727-256c39da42af'],
                'summary' => 'Foundation, mascara and matte lipstick — a full everyday face.',
                'description' => "Maybelline essentials for a polished everyday look.\n\n• Soft-matte foundation\n• Fan-effect mascara\n• Long-wear matte lipstick",
                'combo_items' => [
                    '1x Fit Me Matte + Poreless Foundation',
                    '1x Lash Sensational Mascara',
                    '1x SuperStay Matte Ink Lipstick',
                ],
            ],
            [
                'barcode' => 'HM-CMB-0003', 'combo' => true, 'featured' => true,
                'brand' => "Pond's", 'category' => 'Skincare', 'name' => 'Bright Start Combo',
                'cost' => 500, 'price' => 990, 'original' => 1410,
                'accent' => '#f4c430', 'photos' => ['photo-1556228720-195a672e8a03', 'photo-1570172619644-dfd03ed5d881'],
                'summary' => 'Brightening cream and moisture whip for a fresh daytime glow.',
                'description' => "Pond's brightening care in one easy kit.\n\n• Spot-less looking day cream\n• Classic cold cream moisture\n• Ideal first skincare set",
                'combo_items' => [
                    '1x Bright Beauty Spot-less Cream',
                    '1x Cold Cream Moisture Whip',
                    '1x Mini cleansing towelettes',
                ],
            ],
            [
                'barcode' => 'HM-CMB-0004', 'combo' => true, 'featured' => true,
                'brand' => 'Dove', 'category' => 'Body Care', 'name' => 'Soft Skin Body Combo',
                'cost' => 420, 'price' => 890, 'original' => 1180,
                'accent' => '#1565c0', 'photos' => ['photo-1612817288484-6f916006741a', 'photo-1608248543803-ba4f8c70ae0b'],
                'summary' => 'Body wash and lotion pairing for all-day softness.',
                'description' => "Dove body care from shower to moisturise.\n\n• Gentle cleansing wash\n• Nourishing body lotion\n• Soft-skin ritual in one pack",
                'combo_items' => [
                    '1x Deep Moisture Body Wash 250ml',
                    '1x Intensive Repair Lotion 250ml',
                    '1x Mini beauty bar',
                ],
            ],
            [
                'barcode' => 'HM-CMB-0005', 'combo' => true, 'featured' => true,
                'brand' => 'Olay', 'category' => 'Skincare', 'name' => 'Regenerist Night Combo',
                'cost' => 1680, 'price' => 2890, 'original' => 3480,
                'accent' => '#b71c1c', 'photos' => ['photo-1571781926291-c477ebfd024b', 'photo-1570172619644-dfd03ed5d881'],
                'summary' => 'Micro-sculpting cream plus hydrating cleanser for overnight repair.',
                'description' => "Olay Regenerist evening ritual in one set.\n\n• Firming night cream\n• Gentle hydrating cleanser\n• Ready-to-gift skincare duo",
                'combo_items' => [
                    '1x Regenerist Micro-Sculpting Cream',
                    '1x Regenerist Hydrating Cleanser',
                    '1x Sleep mask sachet',
                ],
            ],
            [
                'barcode' => 'HM-CMB-0006', 'combo' => true, 'featured' => true, 'new' => true,
                'brand' => 'NYX Professional Makeup', 'category' => 'Makeup', 'name' => 'Night Out Makeup Combo',
                'cost' => 720, 'price' => 1590, 'original' => 1980,
                'accent' => '#111827', 'photos' => ['photo-1512496015851-a90fb38ba796', 'photo-1487412912498-0447578fcca8'],
                'summary' => 'Lip, liner and setting spray kit for nights out.',
                'description' => "NYX glam essentials packed together.\n\n• Bold lip colour\n• Precise liner\n• Makeup setting spray to lock the look",
                'combo_items' => [
                    '1x Soft Matte Lip Cream',
                    '1x Epic Ink Waterproof Liner',
                    '1x Setting Spray Matte Finish',
                ],
            ],
        ];
    }
}
