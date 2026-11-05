<?php

namespace Database\Seeders;

use App\Models\CmsBlog;
use App\Models\CmsBlogCategory;
use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $settings = SiteSetting::query()->first();
        if (! $settings) {
            $settings = SiteSetting::create([
                'default_shop_id' => $shop->id,
                'store_name' => 'Hira Moni',
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
            ]);
        }

        $heroPath = $this->storeRemoteImage(
            'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=1600&q=80',
            'cms/blog-hero',
            'hero-beauty.jpg'
        );

        $settings->fill([
            'default_shop_id' => $settings->default_shop_id ?: $shop->id,
            'blog_hero_kicker' => 'BEAUTY JOURNAL',
            'blog_hero_title' => 'Glow Tips & Beauty Stories',
            'blog_hero_subtitle' => 'Skincare routines, makeup guides, and honest product notes from Hira Moni.',
            'blog_articles_title' => 'Latest Stories',
            'blog_newsletter_title' => 'Stay in the glow',
            'blog_newsletter_text' => 'Beauty tips, new arrivals, and gentle offers — straight to your inbox.',
            'blog_feature_1_title' => 'Expert Tips',
            'blog_feature_1_text' => 'Real routines that work',
            'blog_feature_2_title' => 'Product Guides',
            'blog_feature_2_text' => 'Find your perfect match',
            'blog_feature_3_title' => 'Fresh Ideas',
            'blog_feature_3_text' => 'Trends made simple',
        ]);
        if ($heroPath) {
            $settings->blog_hero_image = $heroPath;
        }
        $settings->save();

        $categoryDefs = [
            'skincare' => ['name' => 'Skincare', 'color' => 'rose', 'sort_order' => 1],
            'makeup' => ['name' => 'Makeup', 'color' => 'violet', 'sort_order' => 2],
            'routines' => ['name' => 'Routines', 'color' => 'emerald', 'sort_order' => 3],
            'guides' => ['name' => 'Buying Guides', 'color' => 'amber', 'sort_order' => 4],
        ];

        $categories = [];
        foreach ($categoryDefs as $slug => $def) {
            $categories[$slug] = CmsBlogCategory::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => $slug],
                [
                    'name' => $def['name'],
                    'color' => $def['color'],
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Hide leftover gadget categories/posts from the previous demo.
        CmsBlogCategory::where('shop_id', $shop->id)
            ->whereNotIn('slug', array_keys($categoryDefs))
            ->update(['is_active' => false]);

        $keepSlugs = [];

        $posts = [
            [
                'title' => 'Build a Simple Morning Skincare Routine That Actually Sticks',
                'slug' => 'simple-morning-skincare-routine',
                'category' => 'routines',
                'excerpt' => 'Cleanse, treat, moisturize, protect — a four-step glow ritual you can finish before your first coffee.',
                'body' => "A good morning routine does not need ten bottles. Start with a gentle cleanser to remove overnight oil without stripping your skin barrier.\n\nNext, apply a lightweight serum or essence on damp skin so actives absorb better. Lock everything in with a moisturizer suited to your skin type — gel for oily skin, cream for dry skin.\n\nFinish with SPF every single day. Sunscreen is the step that keeps tone even and makeup sitting smoother. Keep products nearby, set a 5-minute timer if you need to, and consistency will do more than any viral trend.",
                'author_name' => 'Hira Beauty Desk',
                'reading_time' => 5,
                'views_count' => 2140,
                'is_featured' => true,
                'published_at' => '2025-10-18 10:00:00',
                'image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=1200&q=80',
                'file' => 'morning-routine.jpg',
            ],
            [
                'title' => 'How to Choose the Right Moisturizer for Your Skin Type',
                'slug' => 'choose-moisturizer-by-skin-type',
                'category' => 'guides',
                'excerpt' => 'Oily, dry, combination, or sensitive — match texture and ingredients so your skin stays calm, not congested.',
                'body' => "Moisturizer is not optional, even if your skin feels oily. Dehydrated skin often overproduces oil, which makes shine and breakouts worse.\n\nOily and acne-prone skins usually love gel or gel-cream formulas with niacinamide or hyaluronic acid. Dry skins need richer creams with ceramides and nourishing oils. Combination skins can use a balanced lotion and spot-treat dry cheeks.\n\nSensitive skins should keep fragrance low and patch-test new products. Apply moisturizer on slightly damp skin, and give it a minute before makeup so foundation does not pill.",
                'author_name' => 'Nadia Rahman',
                'reading_time' => 6,
                'views_count' => 1890,
                'is_featured' => false,
                'published_at' => '2025-10-14 09:00:00',
                'image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=1200&q=80',
                'file' => 'moisturizer-guide.jpg',
            ],
            [
                'title' => 'Everyday Makeup Looks That Feel Fresh, Not Heavy',
                'slug' => 'everyday-fresh-makeup-looks',
                'category' => 'makeup',
                'excerpt' => 'Tinted moisturizer, soft blush, and a defined lash line — polished beauty that still looks like you.',
                'body' => "Everyday makeup should enhance, not hide. Begin with skincare and a light base — tinted moisturizer or a sheer foundation worked only where you need coverage.\n\nWarm the cheeks with a cream blush and tap a little on the lips for a matched flush. Define brows lightly, curl lashes, and finish with mascara. A soft brown liner in the outer corners adds depth without looking dramatic.\n\nKeep a balm or gloss nearby for touch-ups. The goal is healthy skin showing through, not a full glam face before brunch.",
                'author_name' => 'Maya Chowdhury',
                'reading_time' => 5,
                'views_count' => 2460,
                'is_featured' => true,
                'published_at' => '2025-10-10 11:30:00',
                'image' => 'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=1200&q=80',
                'file' => 'everyday-makeup.jpg',
            ],
            [
                'title' => 'Lip Care 101: Soft Lips Before Any Lipstick',
                'slug' => 'lip-care-before-lipstick',
                'category' => 'skincare',
                'excerpt' => 'Exfoliate gently, hydrate deeply, then color — the order that keeps lipsticks smooth and flattering.',
                'body' => "Flaky lips make even the best lipstick look patchy. Once or twice a week, massage a soft sugar scrub or a damp washcloth in tiny circles, then rinse.\n\nFollow with a rich balm or overnight lip mask. In the day, layer a thin balm under lipstick, blot, then apply color. Matte formulas need more prep than glosses.\n\nAvoid licking your lips — saliva dries them out. Keep a balm in your bag and reapply after coffee or meals for a smoother canvas every time.",
                'author_name' => 'Hira Beauty Desk',
                'reading_time' => 4,
                'views_count' => 1320,
                'is_featured' => false,
                'published_at' => '2025-10-06 14:00:00',
                'image' => 'https://images.unsplash.com/photo-1631214524020-7e18db9a8f92?w=1200&q=80',
                'file' => 'lip-care.jpg',
            ],
            [
                'title' => 'Niacinamide, Hyaluronic Acid & Retinol: What Goes Where',
                'slug' => 'niacinamide-hyaluronic-retinol-order',
                'category' => 'skincare',
                'excerpt' => 'A calm, logical order for popular actives so you get results without unnecessary irritation.',
                'body' => "Layering actives is simpler when you think thin to thick, and gentle to strong. After cleansing, apply hyaluronic acid on damp skin to pull in moisture.\n\nNiacinamide plays well with most routines and can sit after hydrating serums to help with pores and barrier comfort. Retinol belongs at night, after water-based serums and before moisturizer — start slow, two to three nights a week.\n\nNever mix strong acids and retinol on the same night when you are starting out. Always use SPF the next morning. If skin stings or peels heavily, scale back and buffer with moisturizer.",
                'author_name' => 'Dr. Farah Islam',
                'reading_time' => 7,
                'views_count' => 2780,
                'is_featured' => false,
                'published_at' => '2025-10-02 16:00:00',
                'image' => 'https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd?w=1200&q=80',
                'file' => 'actives-guide.jpg',
            ],
            [
                'title' => 'How to Build a Beauty Basket Under ৳2,000',
                'slug' => 'beauty-basket-under-2000',
                'category' => 'guides',
                'excerpt' => 'Cleanser, moisturizer, SPF, and one hero product — a smart starter kit without overspending.',
                'body' => "You do not need a vanity full of bottles to look polished. Prioritize a gentle cleanser, a daily moisturizer, and sunscreen. Those three protect and prep better than unused specialty jars.\n\nAdd one hero product based on your goal: a brightening serum, a tinted lip balm, or a reliable mascara. Shop trusted brands you already love from our shelves, and check sizes — travel or mid sizes stretch budgets further.\n\nReassess after two weeks. If skin feels comfortable and makeup sits well, you already have a winning basket. Upgrade slowly instead of buying everything at once.",
                'author_name' => 'Nadia Rahman',
                'reading_time' => 5,
                'views_count' => 1650,
                'is_featured' => false,
                'published_at' => '2025-09-28 08:30:00',
                'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&q=80',
                'file' => 'beauty-basket.jpg',
            ],
        ];

        foreach ($posts as $post) {
            $keepSlugs[] = $post['slug'];
            $cover = $this->storeRemoteImage($post['image'], 'cms/blogs', $post['file']);

            CmsBlog::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => $post['slug']],
                [
                    'category_id' => $categories[$post['category']]->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'cover_image' => $cover,
                    'author_name' => $post['author_name'],
                    'is_published' => true,
                    'is_featured' => $post['is_featured'],
                    'views_count' => $post['views_count'],
                    'reading_time' => $post['reading_time'],
                    'published_at' => $post['published_at'],
                ]
            );
        }

        CmsBlog::where('shop_id', $shop->id)
            ->whereNotIn('slug', $keepSlugs)
            ->update(['is_published' => false]);
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative)) {
                return $relative;
            }

            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'IntegratedCommerceBlogSeeder/1.0'])
                ->get($url);

            if (! $response->successful()) {
                $this->command?->warn("Failed to download image: {$url}");

                return null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            $this->command?->warn('Image download error: '.$e->getMessage());

            return null;
        }
    }
}
