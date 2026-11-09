<?php

namespace App\Support;

use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Services\ProductVariantService;
use Illuminate\Support\Str;

/**
 * Seeds / syncs a reusable cosmetics attribute vocabulary and
 * category ↔ attribute assignments without wiping custom shop data.
 */
class CosmeticsAttributeCatalog
{
    /**
     * Shop-wide attribute definitions.
     *
     * @return list<array{name: string, slug: string, type: string, is_variation?: bool, is_filterable?: bool, values?: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Shade', 'slug' => 'color', 'type' => ProductAttribute::TYPE_COLOR, 'is_variation' => true, 'is_filterable' => true],
            ['name' => 'Size', 'slug' => 'size', 'type' => ProductAttribute::TYPE_SELECT, 'is_variation' => true, 'is_filterable' => true, 'values' => ['Travel', 'Mini', 'Full size']],
            ['name' => 'Volume / Weight', 'slug' => 'volume', 'type' => ProductAttribute::TYPE_SELECT, 'is_variation' => true, 'is_filterable' => true, 'values' => ['15ml', '30ml', '50ml', '100ml', '50g', '100g', '200ml']],
            ['name' => 'Finish', 'slug' => 'finish', 'type' => ProductAttribute::TYPE_SELECT, 'is_variation' => false, 'is_filterable' => true, 'values' => ['Matte', 'Dewy', 'Satin', 'Glossy', 'Natural', 'Radiant']],
            ['name' => 'Coverage', 'slug' => 'coverage', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['Sheer', 'Light', 'Medium', 'Full']],
            ['name' => 'Skin type', 'slug' => 'skin-type', 'type' => ProductAttribute::TYPE_MULTISELECT, 'is_filterable' => true, 'values' => ['Normal', 'Dry', 'Oily', 'Combination', 'Sensitive', 'All']],
            ['name' => 'Skin concern', 'slug' => 'skin-concern', 'type' => ProductAttribute::TYPE_MULTISELECT, 'is_filterable' => true, 'values' => ['Acne', 'Dryness', 'Dullness', 'Aging', 'Pigmentation', 'Pores', 'Redness']],
            ['name' => 'Key ingredients', 'slug' => 'key-ingredients', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'Benefits', 'slug' => 'benefits', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'SPF Protection', 'slug' => 'spf', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['None', 'SPF 15', 'SPF 30', 'SPF 50', 'SPF 50+']],
            ['name' => 'Formulation', 'slug' => 'formulation', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['Cream', 'Gel', 'Serum', 'Lotion', 'Oil', 'Powder', 'Balm', 'Foam', 'Spray', 'Stick']],
            ['name' => 'Fragrance-Free', 'slug' => 'fragrance-free', 'type' => ProductAttribute::TYPE_BOOLEAN, 'is_filterable' => true],
            ['name' => 'Dermatologically Tested', 'slug' => 'derm-tested', 'type' => ProductAttribute::TYPE_BOOLEAN],
            ['name' => 'Hair type', 'slug' => 'hair-type', 'type' => ProductAttribute::TYPE_MULTISELECT, 'is_filterable' => true, 'values' => ['Straight', 'Wavy', 'Curly', 'Coily', 'Fine', 'Thick', 'Damaged', 'Color-treated', 'All']],
            ['name' => 'Hair concern', 'slug' => 'hair-concern', 'type' => ProductAttribute::TYPE_MULTISELECT, 'is_filterable' => true, 'values' => ['Dryness', 'Frizz', 'Hair fall', 'Dandruff', 'Damage', 'Volume', 'Oiliness']],
            ['name' => 'Hair benefits', 'slug' => 'hair-benefits', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'Fragrance', 'slug' => 'fragrance', 'type' => ProductAttribute::TYPE_SELECT, 'is_variation' => false, 'is_filterable' => true],
            ['name' => 'Fragrance Family', 'slug' => 'fragrance-family', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['Floral', 'Woody', 'Fresh', 'Oriental', 'Citrus', 'Aquatic', 'Gourmand']],
            ['name' => 'Top Notes', 'slug' => 'top-notes', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'Middle Notes', 'slug' => 'middle-notes', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'Base Notes', 'slug' => 'base-notes', 'type' => ProductAttribute::TYPE_TEXT],
            ['name' => 'Concentration', 'slug' => 'concentration', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['EDP', 'EDT', 'EDC', 'Parfum', 'Body mist']],
            ['name' => 'Nail Finish', 'slug' => 'nail-finish', 'type' => ProductAttribute::TYPE_SELECT, 'values' => ['Glossy', 'Matte', 'Shimmer', 'Gel-like']],
            ['name' => 'Tool Type', 'slug' => 'tool-type', 'type' => ProductAttribute::TYPE_SELECT, 'is_filterable' => true, 'values' => ['Brush', 'Sponge', 'Tweezers', 'Mirror', 'Applicator', 'Other']],
            ['name' => 'Material', 'slug' => 'material', 'type' => ProductAttribute::TYPE_SELECT, 'values' => ['Plastic', 'Metal', 'Wood', 'Silicone', 'Natural bristle', 'Synthetic']],
            ['name' => 'Usage', 'slug' => 'usage', 'type' => ProductAttribute::TYPE_TEXT],
        ];
    }

    /** Old gadget catalog slugs — never re-seeded for beauty shops. */
    public const OBSOLETE_GADGET_SLUGS = ['storage', 'ram', 'weight'];

    /**
     * Category name (case-insensitive contains) => attribute slugs.
     * Variation defaults marked with * prefix.
     *
     * @return array<string, list<string>>
     */
    public static function categoryMap(): array
    {
        return [
            'skincare' => ['*volume', 'skin-type', 'skin-concern', 'key-ingredients', 'benefits', 'spf', 'formulation', 'fragrance-free', 'derm-tested'],
            'makeup' => ['*color', '*volume', 'finish', 'coverage', 'formulation', 'skin-type', 'spf', 'benefits'],
            'hair' => ['*volume', 'hair-type', 'hair-concern', 'key-ingredients', 'formulation', 'hair-benefits'],
            'body' => ['*volume', 'skin-type', 'fragrance', 'key-ingredients', 'benefits', 'formulation'],
            'fragrance' => ['*volume', 'fragrance-family', 'top-notes', 'middle-notes', 'base-notes', 'concentration'],
            'perfume' => ['*volume', 'fragrance-family', 'top-notes', 'middle-notes', 'base-notes', 'concentration'],
            'nail' => ['*color', 'nail-finish', 'formulation', 'volume'],
            'tool' => ['tool-type', 'material', 'size', 'usage', 'color'],
            'accessor' => ['tool-type', 'material', 'size', 'usage', 'color'],
            'kit' => ['volume', 'skin-type', 'benefits'],
            'gift' => ['volume', 'benefits'],
            'grooming' => ['*volume', 'skin-type', 'formulation', 'key-ingredients', 'benefits'],
        ];
    }

    public static function syncShop(int $shopId): void
    {
        $variants = app(ProductVariantService::class);
        $bySlug = [];

        foreach (self::definitions() as $i => $def) {
            $attr = ProductAttribute::firstOrCreate(
                ['shop_id' => $shopId, 'slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'is_filterable' => (bool) ($def['is_filterable'] ?? false),
                    'is_variation' => (bool) ($def['is_variation'] ?? false),
                    'sort_order' => $i + 1,
                ],
            );

            $updates = [];
            if ($attr->name !== $def['name'] && in_array($attr->name, ['Color', $def['slug']], true)) {
                $updates['name'] = $def['name'];
            }
            if ($attr->wasRecentlyCreated === false) {
                // Soft upgrade: fill flags/types when still on old defaults
                if (! $attr->is_variation && ($def['is_variation'] ?? false) && in_array($attr->slug, ['color', 'size', 'volume'], true)) {
                    $updates['is_variation'] = true;
                }
                if ($attr->type === ProductAttribute::TYPE_SELECT && $def['type'] !== ProductAttribute::TYPE_SELECT
                    && in_array($attr->slug, ['skin-type', 'fragrance-free', 'derm-tested', 'key-ingredients', 'benefits'], true)
                    && $attr->variantValues()->count() === 0) {
                    $updates['type'] = $def['type'];
                }
            }
            if ($updates !== []) {
                $attr->update($updates);
            }

            foreach ($def['values'] ?? [] as $label) {
                $variants->resolveValue($attr, $label);
            }

            $bySlug[$attr->slug] = $attr;
        }

        // Cosmetics rename Color → Shade
        if (isset($bySlug['color']) && $bySlug['color']->name === 'Color') {
            $bySlug['color']->update(['name' => 'Shade']);
        }

        self::pruneObsoleteGadgetAttributes($shopId);
        self::syncCategoryAssignments($shopId, $bySlug);
    }

    /**
     * Drop unused phone/gadget attributes (Storage / RAM / Weight) left from older catalogs.
     * Attributes still attached to products are left alone.
     */
    public static function pruneObsoleteGadgetAttributes(int $shopId): int
    {
        $removed = 0;
        $attrs = ProductAttribute::query()
            ->where('shop_id', $shopId)
            ->whereIn('slug', self::OBSOLETE_GADGET_SLUGS)
            ->get();

        foreach ($attrs as $attr) {
            if ($attr->variantValues()->exists()) {
                continue;
            }
            $attr->categories()->detach();
            $attr->values()->delete();
            $attr->delete();
            $removed++;
        }

        return $removed;
    }

    /**
     * @param  array<string, ProductAttribute>  $bySlug
     */
    public static function syncCategoryAssignments(int $shopId, array $bySlug): void
    {
        $categories = Category::where('shop_id', $shopId)->get();
        foreach ($categories as $category) {
            $hay = Str::lower($category->name.' '.$category->slug);
            $matched = null;
            foreach (self::categoryMap() as $needle => $slugs) {
                if (str_contains($hay, $needle)) {
                    $matched = $slugs;
                    break;
                }
            }
            if ($matched === null) {
                // Unmatched categories still get core beauty attrs so the form is never empty.
                $matched = ['*color', '*volume', 'finish', 'skin-type', 'formulation', 'benefits'];
            }

            $sync = [];
            foreach ($matched as $order => $slug) {
                $isVariationDefault = str_starts_with($slug, '*');
                $slug = ltrim($slug, '*');
                $attr = $bySlug[$slug] ?? ProductAttribute::where('shop_id', $shopId)->where('slug', $slug)->first();
                if (! $attr) {
                    continue;
                }
                $sync[$attr->id] = [
                    'is_required' => false,
                    'is_variation_default' => $isVariationDefault || (bool) $attr->is_variation,
                    'sort_order' => $order + 1,
                ];
            }

            if ($sync !== []) {
                $category->productAttributes()->syncWithoutDetaching($sync);
            }
        }
    }
}
