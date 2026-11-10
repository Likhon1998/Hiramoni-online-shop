<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WebsiteService;
use Database\Seeders\CosmeticsCatalogSeeder;
use Illuminate\Console\Command;

class EnsureStorefrontDeals extends Command
{
    protected $signature = 'storefront:ensure-deals {--days=14 : How long new timed sales should last}';

    protected $description = 'Make sure the storefront Deals section has live discounted products';

    public function handle(WebsiteService $website): int
    {
        $shopId = $website->shopId();
        if (! $shopId) {
            $this->warn('No shop found.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $reactivated = $this->reactivateExpiredSales($shopId, $days);

        $seeder = new CosmeticsCatalogSeeder;
        $seeder->setCommand($this);
        $fromCatalog = $seeder->applyCatalogSales($shopId, $days);

        $fallback = $this->fallbackBestsellerSales($shopId, $days);
        $live = Product::query()->where('shop_id', $shopId)->onSale()->count();

        $this->info("Deals ready: {$live} on sale (reactivated {$reactivated}, catalog {$fromCatalog}, fallback {$fallback}).");

        return $live > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function reactivateExpiredSales(int $shopId, int $days): int
    {
        $count = 0;
        Product::query()
            ->where('shop_id', $shopId)
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'selling_price')
            ->where(function ($q) {
                $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '<', now());
            })
            ->orderBy('id')
            ->get()
            ->each(function (Product $product) use ($days, &$count) {
                $product->applySale(
                    (float) $product->sale_price,
                    now()->subHour(),
                    now()->addDays($days)
                );
                $count++;
            });

        return $count;
    }

    private function fallbackBestsellerSales(int $shopId, int $days): int
    {
        $live = Product::query()->where('shop_id', $shopId)->onSale()->count();
        if ($live >= 4) {
            return 0;
        }

        $count = 0;
        Product::query()
            ->where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->where('selling_price', '>', 0)
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->orderByDesc('stock_quantity')
            ->orderBy('id')
            ->take(8)
            ->get()
            ->each(function (Product $product) use ($days, &$count) {
                if ($product->isOnSale()) {
                    return;
                }

                $list = (float) $product->selling_price;
                $sale = max(1, round($list * 0.85));
                if ($sale >= $list) {
                    $sale = max(1, $list - 50);
                }
                if ($sale >= $list) {
                    return;
                }

                if ((float) ($product->original_price ?? 0) <= $list) {
                    $product->original_price = $list;
                }
                $product->applySale($sale, now()->subHour(), now()->addDays($days));
                $count++;
            });

        return $count;
    }
}
