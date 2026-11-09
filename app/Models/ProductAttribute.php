<?php

namespace App\Models;

use App\Support\CosmeticsAttributeCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    public const TYPE_SELECT = 'select';

    public const TYPE_COLOR = 'color';

    public const TYPE_TEXT = 'text';

    public const TYPE_NUMBER = 'number';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_MULTISELECT = 'multiselect';

    public const TYPES = [
        self::TYPE_SELECT => 'Options (chips)',
        self::TYPE_COLOR => 'Shade swatches',
        self::TYPE_TEXT => 'Free text',
        self::TYPE_NUMBER => 'Number',
        self::TYPE_BOOLEAN => 'Yes / No',
        self::TYPE_MULTISELECT => 'Multi-select',
    ];

    /**
     * Beauty-store defaults. Slug `color` stays for legacy columns / filters;
     * the storefront label is “Shade”.
     */
    public const DEFAULTS = [
        ['name' => 'Shade', 'slug' => 'color', 'type' => self::TYPE_COLOR],
        ['name' => 'Size', 'slug' => 'size', 'type' => self::TYPE_SELECT],
        ['name' => 'Finish', 'slug' => 'finish', 'type' => self::TYPE_SELECT],
        ['name' => 'Skin type', 'slug' => 'skin-type', 'type' => self::TYPE_MULTISELECT],
    ];

    /** Obsolete gadget attributes — pruned when unused (see CosmeticsAttributeCatalog). */
    public const LEGACY_GADGET_SLUGS = ['storage', 'ram', 'weight'];

    protected $fillable = [
        'shop_id', 'name', 'slug', 'type', 'is_filterable', 'is_variation', 'sort_order',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_variation' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order')->orderBy('value');
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product_attribute')
            ->withPivot(['is_required', 'is_variation_default', 'sort_order'])
            ->withTimestamps();
    }

    public function isColor(): bool
    {
        return $this->type === self::TYPE_COLOR;
    }

    public function isBoolean(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    public function isMultiselect(): bool
    {
        return $this->type === self::TYPE_MULTISELECT;
    }

    public function isVariationDimension(): bool
    {
        return (bool) $this->is_variation || $this->isColor();
    }

    public function acceptsPresetOptions(): bool
    {
        return in_array($this->type, [self::TYPE_SELECT, self::TYPE_COLOR, self::TYPE_MULTISELECT], true);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Ensure beauty attributes + category assignments exist.
     * Renames legacy “Color” → “Shade” without wiping custom attributes.
     */
    public static function ensureDefaults(int $shopId): void
    {
        foreach (self::DEFAULTS as $i => $def) {
            $attr = self::firstOrCreate(
                ['shop_id' => $shopId, 'slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'is_filterable' => true,
                    'is_variation' => in_array($def['slug'], ['color', 'size'], true),
                    'sort_order' => $i + 1,
                ],
            );

            if ($def['slug'] === 'color' && $attr->name === 'Color') {
                $attr->update(['name' => 'Shade']);
            }
        }

        CosmeticsAttributeCatalog::syncShop($shopId);
    }

    public static function uniqueSlug(int $shopId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'attribute';
        $slug = $base;
        $n = 2;
        while (self::where('shop_id', $shopId)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
