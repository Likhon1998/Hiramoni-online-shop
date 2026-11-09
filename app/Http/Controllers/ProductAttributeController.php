<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductAttributeController extends Controller
{
    public function __construct(protected ProductVariantService $variants) {}

    public function index()
    {
        $shopId = (int) Auth::user()->shop_id;
        ProductAttribute::ensureDefaults($shopId);

        $productAttributes = ProductAttribute::forShop($shopId)
            ->withCount(['variantValues as variant_values_count' => fn ($q) => $q->whereHas('product')])
            ->with([
                'values' => fn ($q) => $q->withCount(['variantValues as variant_values_count' => fn ($qq) => $qq->whereHas('product')]),
                'categories:id,name',
            ])
            ->get();
        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get(['id', 'name']);

        return view('attributes.index', compact('productAttributes', 'categories'));
    }

    public function store(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('product_attributes')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
            'type' => ['required', Rule::in(array_keys(ProductAttribute::TYPES))],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'values' => 'nullable|string|max:2000',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
        ]);

        $isColor = $data['type'] === ProductAttribute::TYPE_COLOR;
        $attribute = ProductAttribute::create([
            'shop_id' => $shopId,
            'name' => trim($data['name']),
            'slug' => ProductAttribute::uniqueSlug($shopId, $data['name']),
            'type' => $data['type'],
            'is_filterable' => $request->boolean('is_filterable', true),
            'is_variation' => $isColor || $request->boolean('is_variation'),
            'sort_order' => $data['sort_order'] ?? ((int) ProductAttribute::where('shop_id', $shopId)->max('sort_order') + 1),
        ]);

        if ($attribute->acceptsPresetOptions()) {
            foreach ($this->splitValues($data['values'] ?? '') as $label) {
                $this->variants->resolveValue($attribute, $label);
            }
        }

        $this->syncCategories($attribute, $data['category_ids'] ?? []);

        return back()->with('success', "Attribute \"{$attribute->name}\" created.");
    }

    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $this->authorizeShop($productAttribute);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('product_attributes')
                    ->where(fn ($q) => $q->where('shop_id', $productAttribute->shop_id))
                    ->ignore($productAttribute->id),
            ],
            'type' => ['required', Rule::in(array_keys(ProductAttribute::TYPES))],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('shop_id', $productAttribute->shop_id)),
            ],
        ]);

        $isColor = $data['type'] === ProductAttribute::TYPE_COLOR;

        $productAttribute->update([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'is_filterable' => $request->boolean('is_filterable'),
            'is_variation' => $isColor || $request->boolean('is_variation'),
            'sort_order' => $data['sort_order'] ?? $productAttribute->sort_order,
        ]);

        // Always sync — unchecked “all categories” must clear the pivot.
        $this->syncCategories($productAttribute, $request->input('category_ids', []));

        return back()->with('success', "Attribute \"{$productAttribute->name}\" updated.");
    }

    /** @param  list<int|string>  $categoryIds */
    protected function syncCategories(ProductAttribute $attribute, array $categoryIds): void
    {
        $sync = [];
        foreach (array_values(array_unique(array_map('intval', $categoryIds))) as $i => $categoryId) {
            if ($categoryId <= 0) {
                continue;
            }
            $sync[$categoryId] = [
                'is_required' => false,
                'is_variation_default' => (bool) $attribute->is_variation,
                'sort_order' => $i + 1,
            ];
        }
        $attribute->categories()->sync($sync);
    }

    public function destroy(ProductAttribute $productAttribute)
    {
        $this->authorizeShop($productAttribute);

        $inUse = $productAttribute->variantValues()->whereHas('product')->count();
        if ($inUse > 0) {
            return back()->with('error', "\"{$productAttribute->name}\" is used by {$inUse} product(s). Remove it from those products first.");
        }

        $name = $productAttribute->name;
        $productAttribute->categories()->detach();
        $productAttribute->values()->delete();
        $productAttribute->delete();

        return back()->with('success', "Attribute \"{$name}\" deleted.");
    }

    public function storeValue(Request $request, ProductAttribute $productAttribute)
    {
        $this->authorizeShop($productAttribute);

        $data = $request->validate([
            'values' => 'nullable|string|max:2000',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if (! $productAttribute->acceptsPresetOptions()) {
            return back()->with('error', "\"{$productAttribute->name}\" does not use a preset value list.");
        }

        $labels = $this->splitValues((string) ($data['values'] ?? ''));
        $hex = isset($data['color_hex']) ? strtoupper($data['color_hex']) : null;

        // Colour attributes can be added by picker alone when the shade name is unknown.
        if ($labels === [] && $productAttribute->isColor() && $hex) {
            $labels = ['Custom '.$hex];
        }

        if ($labels === []) {
            return back()->withErrors([
                'values' => $productAttribute->isColor()
                    ? 'Pick a colour or enter a shade name.'
                    : 'Enter at least one value.',
            ])->withInput();
        }

        foreach ($labels as $label) {
            $this->variants->resolveValue(
                $productAttribute,
                $label,
                count($labels) === 1 ? $hex : null,
            );
        }

        return back()->with('success', count($labels).' value(s) added to '.$productAttribute->name.'.');
    }

    public function updateValue(Request $request, ProductAttributeValue $attributeValue)
    {
        $attribute = $attributeValue->attribute;
        $this->authorizeShop($attribute);

        $data = $request->validate([
            'value' => 'required|string|max:80',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $slug = Str::slug($data['value']) ?: substr(md5($data['value']), 0, 12);
        $clash = ProductAttributeValue::where('product_attribute_id', $attribute->id)
            ->where('slug', $slug)->where('id', '!=', $attributeValue->id)->exists();
        if ($clash) {
            return back()->with('error', "\"{$data['value']}\" already exists in {$attribute->name}.");
        }

        $hex = isset($data['color_hex']) ? strtoupper($data['color_hex']) : null;

        $attributeValue->update([
            'value' => trim($data['value']),
            'slug' => $slug,
            'color_hex' => $attribute->isColor() ? ($hex ?? $attributeValue->color_hex) : null,
            'sort_order' => $data['sort_order'] ?? $attributeValue->sort_order,
        ]);

        Product::whereIn('id', $attributeValue->variantValues()->pluck('product_id'))
            ->get()
            ->each(fn (Product $product) => $this->variants->mirrorLegacyColumns($product));

        return back()->with('success', 'Value updated.');
    }

    public function destroyValue(ProductAttributeValue $attributeValue)
    {
        $this->authorizeShop($attributeValue->attribute);

        $inUse = $attributeValue->variantValues()->whereHas('product')->count();
        if ($inUse > 0) {
            return back()->with('error', "\"{$attributeValue->value}\" is used by {$inUse} product(s) and cannot be deleted.");
        }

        $attributeValue->variantValues()->delete();
        $attributeValue->delete();

        return back()->with('success', 'Value deleted.');
    }

    protected function authorizeShop(?ProductAttribute $attribute): void
    {
        abort_unless($attribute && (int) $attribute->shop_id === (int) Auth::user()->shop_id, 404);
    }

    /** @return list<string> */
    protected function splitValues(string $raw): array
    {
        return collect(preg_split('/[,\n]+/', $raw) ?: [])
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && mb_strlen($v) <= 80)
            ->unique(fn ($v) => Str::lower($v))
            ->values()
            ->all();
    }
}
