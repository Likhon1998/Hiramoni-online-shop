{{-- Shared product form fields — used by create & edit --}}
@php
    $product = $product ?? null;
    $variantOf = $variantOf ?? null;
    $siblings = $siblings ?? collect();
    $isEdit = $product !== null;
    $prefill = $product ?? $variantOf;
    $showImei = retail_enabled() || (bool) ($product?->requires_imei);

    $currentValues = $product
        ? $product->variantValues->filter(fn ($v) => $v->attributeValue)->keyBy('product_attribute_id')
        : collect();

    $attributePayload = $productAttributes->map(fn ($a) => [
        'id' => $a->id,
        'name' => $a->name,
        'type' => $a->type,
        'slug' => $a->slug,
        'is_variation' => (bool) $a->is_variation || $a->isColor(),
        'category_ids' => $a->relationLoaded('categories')
            ? $a->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            : [],
        'values' => $a->values->map(fn ($v) => ['value' => $v->value, 'hex' => $v->swatchHex()])->values(),
    ])->values();

    $colorAttributeId = $productAttributes->firstWhere('slug', 'color')?->id;
    $sizeAttributeId = $productAttributes->firstWhere('slug', 'size')?->id;
    $volumeAttributeId = $productAttributes->firstWhere('slug', 'volume')?->id;
    $finishAttributeId = $productAttributes->firstWhere('slug', 'finish')?->id;

    $defaultVariationAttrIds = $productAttributes
        ->filter(fn ($a) => $a->is_variation || $a->isColor() || in_array($a->slug, ['color', 'volume', 'size'], true))
        ->take(2)
        ->pluck('id')
        ->values();
    if ($defaultVariationAttrIds->isEmpty()) {
        $defaultVariationAttrIds = collect([$colorAttributeId, $volumeAttributeId ?: $finishAttributeId ?: $sizeAttributeId])->filter()->values();
    }

    // Beauty presets — quick-fill shade names with hex for lip / face colour
    $shadePresets = [
        ['value' => 'Nude Rose', 'hex' => '#C47A6A'],
        ['value' => 'Berry Crush', 'hex' => '#9C2A3E'],
        ['value' => 'Cherry Red', 'hex' => '#C2185B'],
        ['value' => 'Deep Plum', 'hex' => '#6A1B3A'],
        ['value' => 'Coral Glow', 'hex' => '#E57373'],
        ['value' => 'Ivory', 'hex' => '#F3E0C8'],
        ['value' => 'Beige', 'hex' => '#D4A574'],
        ['value' => 'Golden', 'hex' => '#C49A6C'],
        ['value' => 'Caramel', 'hex' => '#8D5524'],
        ['value' => 'Espresso', 'hex' => '#4A2C1A'],
    ];

    $oldVariants = collect(old('variants', []))->values()->map(function ($row, $i) {
        return [
            '_key' => 'v'.($i + 1),
            'options' => (object) ($row['options'] ?? []),
            'barcode' => $row['barcode'] ?? '',
            'sku' => $row['sku'] ?? '',
            'cost_price' => $row['cost_price'] ?? '',
            'selling_price' => $row['selling_price'] ?? '',
            'stock_quantity' => $row['stock_quantity'] ?? 0,
            'imei_list' => $row['imei_list'] ?? '',
            '_files' => [],
        ];
    });
    $oldSelectedAttrs = collect(old('variants', []))
        ->flatMap(fn ($row) => array_keys(array_filter($row['options'] ?? [], fn ($o) => filled($o['value'] ?? null))))
        ->map(fn ($id) => (int) $id)->unique()->values();
    $selectedAttrs = $oldSelectedAttrs->isNotEmpty()
        ? $oldSelectedAttrs
        : $defaultVariationAttrIds;

    $defaultMode = $isEdit ? 'simple' : ($variantOf ? 'simple' : '');
    $initialCategoryId = (int) old('category_id', $prefill?->category_id ?? 0);
    $hexByAttr = [];
    foreach ($productAttributes as $attribute) {
        if (! $attribute->isColor()) {
            continue;
        }
        $current = $currentValues->get($attribute->id)?->attributeValue;
        $hexByAttr[$attribute->id] = old(
            "attributes.{$attribute->id}.hex",
            $current?->swatchHex() ?? '#E91E63'
        );
    }
    $seoSectionOpen = (bool) (
        $product?->seo_title
        || $product?->meta_description
        || $product?->og_image
        || $errors->hasAny(['seo_title', 'meta_description', 'og_title', 'og_description', 'og_image_file'])
    );
@endphp

<div class="space-y-5 pb-24"
     x-data="{
        name: @js(old('name', $isEdit ? $product->name : ($variantOf?->storefrontDisplayName() ?? ''))),
        variantGroup: @js(old('variant_group', $prefill?->variant_group ?? '')),
        selling: @js(old('selling_price', $prefill?->selling_price ?? '')),
        seoTitle: @js(old('seo_title', $prefill?->seo_title ?? '')),
        metaDescription: @js(old('meta_description', $prefill?->meta_description ?? '')),
        seoOpen: {{ $seoSectionOpen ? 'true' : 'false' }},
        dtype: @js(old('pos_discount_type', $prefill?->pos_discount_type ?? '')),
        dval: @js(old('pos_discount_value', $prefill?->pos_discount_value ?? '')),
        hexByAttr: @js($hexByAttr),
        autoGroup: {{ ($isEdit || $variantOf) ? 'false' : 'true' }},
        productMode: @js(old('product_mode', $defaultMode)),
        comboFlag: {{ old('is_combo', $prefill?->is_combo ?? false) ? 'true' : 'false' }},
        comboItems: @js(old('combo_items', $prefill?->combo_items ?? '')),
        requiresImei: @js((bool) old('requires_imei', $product?->requires_imei ?? false)),
        imeiText: @js(old('imei_list', ($isEdit && $product) ? $product->availableImeis->pluck('imei')->implode("\n") : '')),
        attributes: @js($attributePayload),
        selectedAttrs: @js($selectedAttrs),
        selectedCategoryId: @js($initialCategoryId ?: null),
        shadePresets: @js($shadePresets),
        colorAttrId: @js($colorAttributeId),
        genValues: {},
        variantUid: {{ max(1, $oldVariants->count()) }},
        variants: @js($oldVariants),
        categoryModal: false,
        brandModal: false,
        quickName: '',
        quickTagline: '',
        quickDescription: '',
        quickIcon: 'sparkles',
        quickActive: true,
        quickFile: null,
        quickFileLabel: '',
        quickLoading: false,
        quickError: '',
        categoryIcons: @js(collect(\App\Support\CategoryIcons::forPicker())->map(fn ($i) => ['key' => $i['key'], 'label' => $i['label'], 'color' => $i['color'], 'bg' => $i['bg']])->values()),
        categoryUrl: @js(route('categories.store')),
        brandUrl: @js(route('brands.store')),
        csrf: @js(csrf_token()),
        get hasMode() { return {{ $isEdit ? 'true' : 'false' }} || ['simple', 'variable', 'combo'].includes(this.productMode); },
        get isMulti() { return this.productMode === 'variable'; },
        get isComboMode() { return this.productMode === 'combo'; },
        get isSimple() { return this.productMode === 'simple' || this.productMode === 'combo' || {{ $isEdit ? 'true' : 'false' }}; },
        get visibleAttributes() {
            const cat = Number(this.selectedCategoryId || 0);
            if (!cat) return this.attributes;
            const linked = this.attributes.filter(a => (a.category_ids || []).includes(cat));
            return linked.length ? linked : this.attributes;
        },
        get variationCandidates() {
            const list = this.visibleAttributes.filter(a => a.is_variation || a.type === 'color' || ['color','size','volume'].includes(a.slug));
            return list.length ? list : this.visibleAttributes;
        },
        get chosenAttributes() { return this.attributes.filter(a => this.selectedAttrs.includes(a.id)); },
        get colorAttribute() { return this.attributes.find(a => a.id === this.colorAttrId) || null; },
        get comboLineCount() {
            return String(this.comboItems || '').split(/\n+/).map(s => s.trim()).filter(Boolean).length;
        },
        onCategoryChange(event) {
            this.selectedCategoryId = event.target.value ? Number(event.target.value) : null;
            if (this.isMulti) {
                const preferred = this.variationCandidates.map(a => a.id);
                if (preferred.length) {
                    this.selectedAttrs = preferred.slice(0, 2);
                }
            }
        },
        chooseMode(mode) {
            this.productMode = mode;
            if (mode === 'combo') this.comboFlag = true;
            if (mode === 'variable') {
                const preferred = this.variationCandidates.map(a => a.id);
                if (preferred.length && !this.selectedAttrs.length) this.selectedAttrs = preferred.slice(0, 2);
            }
            this.syncGroup();
            this.$nextTick(() => document.getElementById('product_name_input')?.focus());
        },
        slugify(s) {
            return String(s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
        },
        syncGroup() {
            if (this.autoGroup) this.variantGroup = this.slugify(this.name);
        },
        toggleAttr(id) {
            this.selectedAttrs = this.selectedAttrs.includes(id)
                ? this.selectedAttrs.filter(x => x !== id)
                : [...this.selectedAttrs, id];
        },
        hexFor(attr, value) {
            const name = String(value || '').toLowerCase();
            const fromAttr = (attr.values || []).find(v => v.value.toLowerCase() === name);
            if (fromAttr?.hex) return fromAttr.hex;
            const preset = this.shadePresets.find(p => p.value.toLowerCase() === name);
            return preset ? preset.hex : '#cbd5e1';
        },
        togglePresetShade(preset) {
            if (!this.colorAttrId) return;
            if (!this.selectedAttrs.includes(this.colorAttrId)) {
                this.selectedAttrs = [...this.selectedAttrs, this.colorAttrId];
            }
            const current = String(this.genValues[this.colorAttrId] || '')
                .split(',').map(v => v.trim()).filter(Boolean);
            const exists = current.some(v => v.toLowerCase() === preset.value.toLowerCase());
            this.genValues = {
                ...this.genValues,
                [this.colorAttrId]: exists
                    ? current.filter(v => v.toLowerCase() !== preset.value.toLowerCase()).join(', ')
                    : [...current, preset.value].join(', '),
            };
        },
        isPresetSelected(preset) {
            const current = String(this.genValues[this.colorAttrId] || '')
                .split(',').map(v => v.trim().toLowerCase()).filter(Boolean);
            return current.includes(String(preset.value || '').toLowerCase());
        },
        newRow(options = {}) {
            this.variantUid++;
            return {
                _key: 'v' + this.variantUid, options, barcode: '', sku: '',
                cost_price: '', selling_price: '', stock_quantity: 0, imei_list: '', _files: [],
            };
        },
        addVariantRow() {
            const options = {};
            this.chosenAttributes.forEach(a => { options[a.id] = { value: '', hex: a.type === 'color' ? '#E91E63' : '' }; });
            this.variants.push(this.newRow(options));
        },
        generateVariants() {
            const lists = this.chosenAttributes
                .map(a => ({ attr: a, values: String(this.genValues[a.id] || '').split(',').map(v => v.trim()).filter(Boolean) }))
                .filter(l => l.values.length);
            if (!lists.length) return;
            let combos = [{}];
            lists.forEach(({ attr, values }) => {
                const next = [];
                combos.forEach(c => values.forEach(v => next.push({ ...c, [attr.id]: { value: v, hex: attr.type === 'color' ? this.hexFor(attr, v) : '' } })));
                combos = next;
            });
            const key = (opts) => Object.keys(opts).sort().map(k => k + ':' + String(opts[k]?.value || '').toLowerCase()).join('|');
            const existing = new Set(this.variants.map(r => key(r.options)));
            combos.slice(0, 100).forEach(opts => {
                if (!existing.has(key(opts))) this.variants.push(this.newRow(opts));
            });
        },
        removeVariantRow(i) {
            const row = this.variants[i];
            (row._files || []).forEach((f) => { if (f?.url) URL.revokeObjectURL(f.url); });
            this.variants.splice(i, 1);
        },
        rowLabel(row) {
            return this.chosenAttributes.map(a => row.options?.[a.id]?.value).filter(Boolean).join(' / ') || 'New variant';
        },
        syncVariantFiles(index) {
            const row = this.variants[index];
            if (!row) return;
            const input = document.getElementById('variant-file-input-' + row._key);
            if (!input) return;
            const dt = new DataTransfer();
            (row._files || []).forEach((item) => dt.items.add(item.file));
            input.files = dt.files;
        },
        pickVariantImages(index, event) {
            const row = this.variants[index];
            if (!row) return;
            if (!row._files) row._files = [];
            const picked = Array.from(event.target.files || []);
            const room = 20 - row._files.length;
            picked.slice(0, room).forEach((file) => {
                row._files.push({ name: file.name, url: URL.createObjectURL(file), file });
            });
            event.target.value = '';
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        removeVariantImage(index, fileIndex) {
            const row = this.variants[index];
            if (!row || !row._files) return;
            const removed = row._files.splice(fileIndex, 1)[0];
            if (removed?.url) URL.revokeObjectURL(removed.url);
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        resetQuickForm() {
            this.quickName = '';
            this.quickTagline = '';
            this.quickDescription = '';
            this.quickIcon = 'sparkles';
            this.quickActive = true;
            this.quickFile = null;
            this.quickFileLabel = '';
            this.quickError = '';
            if (this.$refs.quickCategoryFile) this.$refs.quickCategoryFile.value = '';
            if (this.$refs.quickBrandFile) this.$refs.quickBrandFile.value = '';
        },
        lockBodyScroll(on) {
            document.documentElement.classList.toggle('overflow-hidden', !!on);
            document.body.classList.toggle('overflow-hidden', !!on);
        },
        openCategoryModal() {
            this.resetQuickForm();
            this.brandModal = false;
            this.categoryModal = true;
            this.lockBodyScroll(true);
            this.$nextTick(() => this.$refs.quickCategoryInput?.focus());
        },
        openBrandModal() {
            this.resetQuickForm();
            this.categoryModal = false;
            this.brandModal = true;
            this.lockBodyScroll(true);
            this.$nextTick(() => this.$refs.quickBrandInput?.focus());
        },
        closeQuickModals() {
            this.categoryModal = false;
            this.brandModal = false;
            this.lockBodyScroll(false);
        },
        onQuickFile(event, kind) {
            const file = event.target.files?.[0] || null;
            this.quickFile = file;
            this.quickFileLabel = file ? file.name : '';
        },
        suggestCategoryIcon() {
            const hay = String(this.quickName || '').toLowerCase();
            const match = (this.categoryIcons || []).find(i =>
                (i.label || '').toLowerCase() === hay
                || hay.includes((i.label || '').toLowerCase())
                || (i.key || '').toLowerCase() === hay
            );
            if (match) this.quickIcon = match.key;
        },
        async quickCreateForm(url, formData, selectId, key, fallback) {
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                    this.quickError = first || data.message || fallback;
                    return false;
                }
                const select = document.getElementById(selectId);
                if (select) {
                    const opt = document.createElement('option');
                    opt.value = data[key].id;
                    opt.textContent = data[key].name;
                    opt.selected = true;
                    select.appendChild(opt);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (key === 'category') {
                    this.selectedCategoryId = Number(data.category.id);
                }
                return true;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
                return false;
            } finally {
                this.quickLoading = false;
            }
        },
        async saveQuickCategory() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a category name.'; return; }
            const fd = new FormData();
            fd.append('name', name);
            if ((this.quickDescription || '').trim()) fd.append('description', this.quickDescription.trim());
            if (this.quickIcon) fd.append('icon', this.quickIcon);
            if (this.quickFile) fd.append('image', this.quickFile);
            if (await this.quickCreateForm(this.categoryUrl, fd, 'category_id', 'category', 'Could not create category.')) {
                this.closeQuickModals();
                this.resetQuickForm();
            }
        },
        async saveQuickBrand() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a brand name.'; return; }
            const fd = new FormData();
            fd.append('name', name);
            if ((this.quickTagline || '').trim()) fd.append('tagline', this.quickTagline.trim());
            fd.append('is_active', this.quickActive ? '1' : '0');
            if (this.quickFile) fd.append('logo', this.quickFile);
            if (await this.quickCreateForm(this.brandUrl, fd, 'brand_id', 'brand', 'Could not create brand.')) {
                this.closeQuickModals();
                this.resetQuickForm();
            }
        },
     }"
     x-init="syncGroup()"
     @keydown.escape.window="if (categoryModal || brandModal) closeQuickModals()">

    @foreach($productAttributes as $attribute)
        <datalist id="attr-values-{{ $attribute->id }}">
            @foreach($attribute->values as $value)
                <option value="{{ $value->value }}">
            @endforeach
        </datalist>
    @endforeach

    @if($variantOf)
        <input type="hidden" name="variant_of" value="{{ $variantOf->id }}">
        <div class="rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3 text-sm text-slate-700">
            Adding a new shade / option to <strong>{{ $variantOf->storefrontDisplayName() }}</strong>.
            Set shade name and swatch below — e.g. “— Pioneer” or “— Soft Matte / 30ml”.
        </div>
    @endif

    @if(!$isEdit && !$variantOf)
    <section class="rounded-xl border border-rose-100 bg-white overflow-hidden shadow-sm shadow-rose-50/80">
        <div class="px-4 py-3 border-b border-rose-50 bg-gradient-to-r from-rose-50/90 to-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Product type</p>
            <h3 class="text-sm font-semibold text-slate-800">Product type</h3>
            <p class="text-xs text-slate-500 mt-0.5">Choose how this beauty product is sold. Mode is form-only — not stored as a DB column.</p>
        </div>
        <div class="p-4">
            <input type="hidden" name="product_mode" :value="productMode">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button type="button" @click="chooseMode('simple')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'simple' ? 'border-rose-500 bg-rose-50/70 ring-2 ring-rose-200' : 'border-slate-200 hover:border-rose-200'">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Simple Product</span>
                        <span class="block text-[12px] text-slate-500 mt-1">One SKU — face wash, sunscreen, shampoo. One price &amp; stock.</span>
                    </span>
                </button>
                <button type="button" @click="chooseMode('variable')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'variable' ? 'border-fuchsia-500 bg-fuchsia-50/60 ring-2 ring-fuchsia-200' : 'border-slate-200 hover:border-fuchsia-200'">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-fuchsia-100 text-fuchsia-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Variable Product</span>
                        <span class="block text-[12px] text-slate-500 mt-1">Shade / size family — each combination is its own SKU (max 100).</span>
                    </span>
                </button>
                <button type="button" @click="chooseMode('combo')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'combo' ? 'border-amber-500 bg-amber-50/70 ring-2 ring-amber-200' : 'border-slate-200 hover:border-amber-200'">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Combo Product</span>
                        <span class="block text-[12px] text-slate-500 mt-1">Kit / gift set — one SKU with a text list of what’s inside.</span>
                    </span>
                </button>
            </div>
            <p x-show="!hasMode" class="mt-3 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2" x-cloak>
                Select a product type to continue.
            </p>
        </div>
    </section>
    @else
        <input type="hidden" name="product_mode" value="simple">
    @endif

    <div x-show="hasMode" x-cloak class="space-y-5">

    @if($isEdit && ($product->variant_group || $siblings->isNotEmpty()))
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Shades in this product</h3>
                <p class="text-xs text-slate-500 mt-0.5">All shades share one store page. You are editing <strong>{{ $product->variantLabel() ?: 'this shade' }}</strong>.</p>
            </div>
            <a href="{{ route('products.create', ['variant_of' => $product->id]) }}"
               class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                + Add shade
            </a>
        </div>
        @if($siblings->isNotEmpty())
            <div class="divide-y divide-slate-100">
                @foreach($siblings as $sibling)
                    <a href="{{ route('products.edit', $sibling) }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="font-medium text-slate-800">{{ $sibling->variantLabel() ?: $sibling->name }}</span>
                            <span class="ml-2 font-mono text-[11px] text-slate-400">{{ $sibling->barcode }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-slate-500">
                            {{ format_taka($sibling->selling_price) }} · {{ $sibling->availableStock() }} available
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    <template x-if="isSimple">
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Images</p>
            <h3 class="text-sm font-semibold text-slate-800">Product images</h3>
            <p class="text-xs text-slate-500 mt-0.5">Up to 20 photos. The first photo is the main thumbnail.@if($variantOf) Leave empty to reuse the photos of the original variant.@endif</p>
        </div>
        <div class="p-4">
            @include('products.partials.image-uploads', ['product' => $product ?? null])
        </div>
    </section>
    </template>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Basics</p>
            <h3 class="text-sm font-semibold text-slate-800">Basic information</h3>
            <p class="text-xs text-slate-500 mt-0.5">Title, brand and category customers see on the store. Category drives which attributes appear below.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product name <span class="text-red-500">*</span></label>
                <input type="text" id="product_name_input" name="name" x-model="name" @input="syncGroup()"
                       :required="hasMode"
                       class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5"
                       placeholder="e.g. SuperStay Matte Ink, Soft Matte Lip Cream, Hyaluronic Serum">
                <p class="text-[11px] text-slate-400 mt-1" x-show="isMulti" x-cloak>Each shade is saved as “Name — Shade” (customers pick swatches on the product page).</p>
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="category_id" class="block text-xs font-semibold text-slate-600">Category</label>
                        <button type="button" @click="openCategoryModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600"
                                title="Add category">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="category_id" name="category_id" @change="onCategoryChange($event)"
                            class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $prefill?->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="brand_id" class="block text-xs font-semibold text-slate-600">Brand</label>
                        <button type="button" @click="openBrandModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600"
                                title="Add brand">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="brand_id" name="brand_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5">
                        <option value="">No brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $prefill?->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div x-show="isSimple">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Barcode / product code</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode ?? '') }}"
                           :disabled="isMulti"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5 font-mono"
                           placeholder="Auto-generated if empty">
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product?->sku ?? '') }}"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5 font-mono"
                           placeholder="Optional">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Availability label</label>
                    <select name="availability" class="block w-full rounded-lg border-slate-200 bg-white focus:border-rose-400 focus:ring-rose-100 text-sm py-2.5">
                        @php $avail = old('availability', $prefill?->availability ?? 'in_stock'); @endphp
                        <option value="in_stock" @selected($avail === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected($avail === 'pre_order')>Pre Order</option>
                        <option value="up_coming" @selected($avail === 'up_coming')>Coming Soon</option>
                        <option value="out_of_stock" @selected($avail === 'out_of_stock')>Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pricing</p>
            <h3 class="text-sm font-semibold text-slate-800">Pricing &amp; inventory defaults</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <span x-show="isSimple">Cost and selling price for this item. Opening stock is set in Description &amp; visibility.</span>
                <span x-show="isMulti" x-cloak>Default price for every shade — override per generated SKU below.</span>
            </p>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cost price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $prefill?->cost_price ?? '') }}"
                       :required="hasMode" :disabled="!hasMode"
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                @error('cost_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Selling price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="selling_price" x-model="selling"
                       :required="hasMode" :disabled="!hasMode"
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-medium text-rose-700">
                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="px-4 pb-4">
            <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4 space-y-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Always-on discount</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Applies on the store until you clear it. Timed sales can still apply — the customer always pays the lower price.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Type</label>
                        <select name="pos_discount_type" x-model="dtype" class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                            <option value="">No discount</option>
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed (Tk)</option>
                        </select>
                        @error('pos_discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                            <span x-text="dtype === 'percent' ? 'Percent off' : 'Amount off (Tk)'"></span>
                        </label>
                        <input type="number" step="0.01" min="0" name="pos_discount_value" x-model="dval"
                               :disabled="!dtype" :required="!!dtype"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5 disabled:bg-slate-100 disabled:text-slate-400">
                        @error('pos_discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <p class="text-xs text-slate-600 pb-2.5" x-show="dtype && selling && dval" x-cloak>
                            Offer ≈
                            <span class="font-bold text-rose-700"
                                  x-text="'Tk ' + (dtype === 'percent'
                                    ? Math.round(Math.max(0, Number(selling) * (1 - Number(dval)/100))).toLocaleString()
                                    : Math.round(Math.max(0, Number(selling) - Number(dval))).toLocaleString())"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Category-driven cosmetics attributes for simple / combo / edit --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden" x-show="isSimple">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Attributes</p>
                <h3 class="text-sm font-semibold text-slate-800">Cosmetics product attributes</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    <span x-show="selectedCategoryId">Showing attributes for the selected category (plus any already saved values).</span>
                    <span x-show="!selectedCategoryId">Select a category to focus attributes — or fill any field below.</span>
                </p>
            </div>
            <a href="{{ route('attributes.index') }}" class="shrink-0 text-xs font-semibold text-rose-600 hover:underline">Manage attributes</a>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($productAttributes as $attribute)
                @php
                    $current = $currentValues->get($attribute->id)?->attributeValue;
                    $valueOld = old("attributes.{$attribute->id}.value", $current?->value ?? '');
                    $hexOld = old("attributes.{$attribute->id}.hex", $current?->swatchHex() ?? '#E91E63');
                    $catIds = $attribute->relationLoaded('categories')
                        ? $attribute->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                        : [];
                    $alwaysShow = filled($valueOld) || $isEdit;
                @endphp
                <div x-cloak
                     x-show="{{ $alwaysShow ? 'true' : 'false' }} || !selectedCategoryId || visibleAttributes.some(a => a.id === {{ $attribute->id }})"
                     @if($catIds !== []) data-category-ids="{{ implode(',', $catIds) }}" @endif
                     class="min-w-0">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                        {{ $attribute->name }}
                        @if($attribute->is_variation)
                            <span class="ml-1 rounded bg-fuchsia-50 px-1 py-0.5 text-[9px] font-bold uppercase tracking-wide text-fuchsia-600">SKU</span>
                        @endif
                    </label>
                    @if($attribute->isBoolean())
                        <select name="attributes[{{ $attribute->id }}][value]" class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                            <option value="">—</option>
                            <option value="Yes" @selected($valueOld === 'Yes')>Yes</option>
                            <option value="No" @selected($valueOld === 'No')>No</option>
                        </select>
                    @elseif($attribute->type === 'number')
                        <input type="number" step="any" name="attributes[{{ $attribute->id }}][value]" value="{{ $valueOld }}"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5" placeholder="Optional">
                    @elseif($attribute->type === 'text')
                        <textarea name="attributes[{{ $attribute->id }}][value]" rows="2"
                                  class="block w-full rounded-lg border-slate-200 text-sm"
                                  placeholder="Optional">{{ $valueOld }}</textarea>
                    @elseif($attribute->isMultiselect() && $attribute->values->isNotEmpty())
                        <select name="attributes[{{ $attribute->id }}][value]" class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                            <option value="">—</option>
                            @foreach($attribute->values as $opt)
                                <option value="{{ $opt->value }}" @selected($valueOld === $opt->value || str_contains(','.$valueOld.',', ','.$opt->value.','))>{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-slate-400">Pick one primary value, or type several comma-separated after save if needed.</p>
                    @else
                        <div class="flex items-center gap-2">
                            <input type="text" name="attributes[{{ $attribute->id }}][value]" value="{{ $valueOld }}"
                                   list="attr-values-{{ $attribute->id }}"
                                   class="block w-full min-w-0 rounded-lg border-slate-200 text-sm py-2.5"
                                   placeholder="{{ $attribute->isColor() ? 'e.g. Pioneer, Nude Rose' : ($attribute->isMultiselect() ? 'Comma-separated values' : 'Optional') }}">
                            @if($attribute->isColor())
                                <label class="relative inline-flex h-10 w-10 shrink-0 cursor-pointer overflow-hidden rounded-full border border-slate-200 shadow-sm ring-1 ring-black/5"
                                       :style="'background-color:' + (hexByAttr[{{ $attribute->id }}] || '#E91E63')"
                                       title="Shade swatch">
                                    <input type="color" name="attributes[{{ $attribute->id }}][hex]" x-model="hexByAttr[{{ $attribute->id }}]"
                                           class="absolute inset-0 h-full w-full cursor-pointer opacity-0" aria-label="Pick shade color">
                                </label>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="px-4 pb-4" x-show="{{ $isEdit ? 'true' : 'false' }}">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Shade family key</label>
            <input type="text" name="variant_group" x-model="variantGroup"
                   class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                   placeholder="e.g. superstay-matte-ink">
            <p class="text-[11px] text-slate-400 mt-1">Shades with the same key share one store page and a swatch picker.</p>
        </div>
        @if(!$isEdit)
            <input type="hidden" name="variant_group" :value="variantGroup" :disabled="isMulti">
        @endif
    </section>

    {{-- Shade / size builder --}}
    @if(!$isEdit)
    <section class="rounded-xl border border-rose-100 bg-white overflow-hidden" x-show="isMulti" x-cloak>
        <div class="px-4 py-3 border-b border-rose-50 bg-gradient-to-r from-rose-50/90 to-white flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Variations</p>
                <h3 class="text-sm font-semibold text-slate-800">Shade &amp; size builder</h3>
                <p class="text-xs text-slate-500 mt-0.5">Select SKU-defining dimensions only (shade, volume…). Descriptive attrs stay on the Attributes section for simple products.</p>
            </div>
            <a href="{{ route('attributes.index') }}" class="shrink-0 text-xs font-semibold text-rose-600 hover:underline">Manage attributes</a>
        </div>
        <div class="p-4 space-y-4">
            <input type="hidden" name="variant_group" :value="variantGroup" :disabled="!isMulti">

            <div>
                <p class="text-xs font-semibold text-slate-600 mb-2">1. Variation dimensions</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="attr in variationCandidates" :key="attr.id">
                        <button type="button" @click="toggleAttr(attr.id)"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                :class="selectedAttrs.includes(attr.id) ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                x-text="attr.name"></button>
                    </template>
                </div>
                <details class="mt-2">
                    <summary class="cursor-pointer text-[11px] font-semibold text-slate-500 hover:text-slate-700">Show all attributes</summary>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="attr in attributes" :key="'all-' + attr.id">
                            <button type="button" @click="toggleAttr(attr.id)"
                                    class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                    :class="selectedAttrs.includes(attr.id) ? 'border-fuchsia-500 bg-fuchsia-50 text-fuchsia-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                                    x-text="attr.name"></button>
                        </template>
                    </div>
                </details>
            </div>

            <div x-show="colorAttrId && selectedAttrs.includes(colorAttrId)" class="rounded-xl border border-rose-100 bg-rose-50/40 p-3 space-y-2">
                <p class="text-xs font-semibold text-rose-800">Quick shade palette</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in shadePresets" :key="preset.value">
                        <button type="button" @click="togglePresetShade(preset)"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition"
                                :class="isPresetSelected(preset) ? 'border-rose-500 bg-white text-rose-700 ring-1 ring-rose-200' : 'border-white/80 bg-white/70 text-slate-600 hover:border-rose-200'">
                            <span class="h-3.5 w-3.5 rounded-full border border-black/10 shadow-sm" :style="'background:' + preset.hex"></span>
                            <span x-text="preset.value"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div x-show="chosenAttributes.length" class="space-y-2">
                <p class="text-xs font-semibold text-slate-600">2. Values (comma separated — or use the palette above)</p>
                <template x-for="attr in chosenAttributes" :key="'gen-' + attr.id">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="w-28 shrink-0 text-xs font-medium text-slate-500" x-text="attr.name"></span>
                        <input type="text" x-model="genValues[attr.id]"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2"
                               :placeholder="attr.type === 'color'
                                   ? 'e.g. Pioneer, Lover, Voyager'
                                   : (attr.values.length ? attr.values.slice(0, 4).map(v => v.value).join(', ') : 'e.g. 30ml, 50ml · Matte, Glossy')">
                    </div>
                </template>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" @click="generateVariants()"
                            class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700">Generate shades</button>
                    <button type="button" @click="addVariantRow()"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">+ Add one manually</button>
                </div>
            </div>

            <p x-show="!variants.length" class="rounded-lg border border-dashed border-rose-200 bg-rose-50/30 px-3 py-4 text-center text-xs text-slate-500">
                No SKUs yet — pick dimensions, enter values, then generate (max 100).
            </p>
            <p x-show="variants.length" class="text-[11px] font-semibold text-slate-500" x-cloak>
                Preview: <span class="text-rose-700" x-text="variants.length"></span> SKU(s) will be created under one family key
                <span class="font-mono text-slate-700" x-text="variantGroup || '—'"></span>
            </p>

            <template x-for="(row, index) in variants" :key="row._key">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-700" x-text="rowLabel(row)"></span>
                        <button type="button" @click="removeVariantRow(index)" class="text-[11px] font-medium text-red-600 hover:text-red-700">Remove</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        <template x-for="attr in chosenAttributes" :key="row._key + '-' + attr.id">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1" x-text="attr.name"></label>
                                <div class="flex items-center gap-1.5">
                                    <input type="text"
                                           :name="'variants[' + index + '][options][' + attr.id + '][value]'"
                                           :list="'attr-values-' + attr.id"
                                           :value="row.options?.[attr.id]?.value || ''"
                                           @input="row.options = { ...row.options, [attr.id]: { ...(row.options?.[attr.id] || {}), value: $event.target.value } }"
                                           class="block w-full rounded-md border-slate-200 text-sm py-2">
                                    <template x-if="attr.type === 'color'">
                                        <label class="relative inline-flex h-9 w-9 shrink-0 cursor-pointer overflow-hidden rounded-full border border-slate-200 shadow-sm ring-1 ring-black/5"
                                               :style="'background-color:' + (row.options?.[attr.id]?.hex || hexFor(attr, row.options?.[attr.id]?.value))"
                                               title="Shade swatch">
                                            <input type="color"
                                                   :name="'variants[' + index + '][options][' + attr.id + '][hex]'"
                                                   :value="row.options?.[attr.id]?.hex || hexFor(attr, row.options?.[attr.id]?.value)"
                                                   @input="row.options = { ...row.options, [attr.id]: { ...(row.options?.[attr.id] || {}), hex: $event.target.value } }"
                                                   class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                                   aria-label="Pick shade color">
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cost (Tk)</label>
                            <input type="number" step="0.01" min="0" :name="'variants[' + index + '][cost_price]'" x-model="row.cost_price"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="Default">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Selling (Tk)</label>
                            <input type="number" step="0.01" min="0" :name="'variants[' + index + '][selling_price]'" x-model="row.selling_price"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-medium text-blue-700"
                                   :placeholder="selling ? ('Tk ' + selling) : 'Default'">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Opening qty</label>
                            <input type="number" min="0" :name="'variants[' + index + '][stock_quantity]'" x-model="row.stock_quantity"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Barcode</label>
                            <input type="text" :name="'variants[' + index + '][barcode]'" x-model="row.barcode"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono" placeholder="Auto">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">SKU</label>
                            <input type="text" :name="'variants[' + index + '][sku]'" x-model="row.sku"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono" placeholder="Optional">
                        </div>
                    </div>
                    <details class="group">
                        <summary class="cursor-pointer text-[11px] font-semibold text-slate-500 hover:text-slate-700">Photos for this variant <span x-text="(row._files || []).length ? '(' + row._files.length + ')' : '(optional — reuses the previous variant\'s photos)'"></span></summary>
                        <div class="mt-2">
                            @include('products.partials.variant-image-uploads')
                        </div>
                    </details>
                    @if($showImei)
                        <div x-show="requiresImei">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">IMEI / serial numbers (one per line)</label>
                            <textarea :name="'variants[' + index + '][imei_list]'" x-model="row.imei_list" rows="2"
                                      class="block w-full rounded-md border-slate-200 text-sm font-mono"></textarea>
                        </div>
                    @endif
                </div>
            </template>
            @error('variants') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('variants.*.barcode') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

            <template x-if="isMulti">
                <div class="rounded-lg border border-dashed border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-semibold text-slate-600 mb-2">Shared photos (used for the first variant if it has none of its own)</p>
                    @include('products.partials.image-uploads', ['product' => null])
                </div>
            </template>
        </div>
    </section>
    @endif

    @if($showImei)
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Serial / IMEI tracking</h3>
            <p class="text-xs text-slate-500 mt-0.5">Legacy retail feature — only for items sold with a serial number.</p>
        </div>
        <div class="p-4 space-y-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="hidden" name="requires_imei" value="0">
                <input type="checkbox" name="requires_imei" value="1" x-model="requiresImei"
                       class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                Require an IMEI / serial when selling
            </label>
            <div x-show="requiresImei && isSimple" x-cloak>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Available serials (one per line)</label>
                <textarea name="imei_list" x-model="imeiText" rows="4" class="block w-full rounded-lg border-slate-200 text-sm font-mono"></textarea>
                @error('imei_list') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Details</p>
            <h3 class="text-sm font-semibold text-slate-800">Description, stock &amp; storefront</h3>
            <p class="text-xs text-slate-500 mt-0.5">Short summary near the price; full description on the Description tab. Stock is create-only.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Short summary</label>
                <textarea name="short_description" rows="2" maxlength="2000"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="One or two lines that sell the product.">{{ old('short_description', $prefill?->short_description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full description</label>
                <textarea name="description" rows="6"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="Key benefits, finish, how to use, skin type tips, ingredients highlight…">{{ old('description', $prefill?->description ?? '') }}</textarea>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                           @checked(old('is_published', $isEdit ? ($product?->is_published ?? true) : true))>
                    Publish on website
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_new_arrival" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                           @checked(old('is_new_arrival', $prefill?->is_new_arrival ?? false))>
                    New Arrival
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_best_seller" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                           @checked(old('is_best_seller', $prefill?->is_best_seller ?? false))>
                    Best seller
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer" title="Shown in the homepage Featured Products section">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                           @checked(old('is_featured', $prefill?->is_featured ?? false))>
                    Featured on homepage
                </label>
            </div>

            {{-- Combo configuration (text-based, not a BOM) --}}
            <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/40 p-3.5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 mb-1">Combo</p>
                <template x-if="isComboMode">
                    <div>
                        <input type="hidden" name="is_combo" value="1">
                        <p class="text-sm font-semibold text-amber-900">Combo configuration</p>
                        <p class="text-[12px] text-amber-800/80 mt-0.5 mb-3">Text-based contents only — not linked products. Stock is for this pack SKU alone.</p>
                    </div>
                </template>
                <template x-if="!isComboMode">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="is_combo" value="1" x-model="comboFlag" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        Enable combo pack
                        <span class="text-xs font-normal text-slate-400">Gift set / kit sold as one SKU</span>
                    </label>
                </template>
                <div x-show="comboFlag || isComboMode" x-cloak class="mt-3 space-y-2">
                    <label class="block text-xs font-semibold text-slate-600">
                        What’s included
                        <span class="font-normal text-slate-400">(one item per line, e.g. 1x Face Wash 100ml)</span>
                        <span x-show="isComboMode" class="text-rose-500">*</span>
                    </label>
                    <textarea name="combo_items" x-model="comboItems" rows="5"
                              class="block w-full rounded-lg border-slate-200 text-sm"
                              :required="isComboMode"
                              placeholder="1x Face Wash 100ml&#10;1x Moisturizer 50g&#10;1x Sunscreen SPF 50"></textarea>
                    <p class="text-[11px] text-slate-500">
                        <span class="font-semibold text-amber-800" x-text="comboLineCount"></span> item(s) will show on the storefront.
                        No automatic component stock deduction.
                    </p>
                    @error('combo_items') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @if(!$isEdit)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="isSimple">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Opening quantity</label>
                        <input type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', 0) }}"
                               :disabled="isMulti"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                        @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                               :disabled="isMulti"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
                <div x-show="isMulti" x-cloak class="rounded-lg border border-violet-100 bg-violet-50/50 px-3 py-2.5 text-xs text-slate-600">
                    Opening stock is set per variant. Low stock alert for each variant:
                    <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                           :disabled="!isMulti"
                           class="inline-block w-20 ml-1 rounded border-slate-200 text-sm py-1">
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Stock</label>
                        <div class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm py-2.5 px-3 font-medium text-slate-900">
                            {{ $product->stock_quantity ?? 0 }} on hand · {{ $product->reservedStock() }} reserved · {{ $product->availableStock() }} available
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Change stock via
                            @if(($product->stock_quantity ?? 0) === 0)
                                <a href="{{ route('supply.opening-inventory.index') }}" class="text-rose-600 underline">Opening Inventory</a> or
                            @endif
                            <a href="{{ route('supply.adjustments.index') }}" class="text-rose-600 underline">Stock Adjustment</a>.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 5) }}" min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button type="button" @click="seoOpen = !seoOpen" class="w-full px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3 text-left">
            <span>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">SEO</span>
                <span class="block text-sm font-semibold text-slate-800">SEO &amp; social sharing</span>
                <span class="block text-xs text-slate-500 mt-0.5">How this product looks on Google and when shared on Facebook, WhatsApp or Messenger.</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="seoOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="p-4 space-y-4" x-show="seoOpen" x-cloak>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[11px] text-slate-400">Search preview</p>
                <p class="mt-1 truncate text-[15px] font-medium text-rose-700" x-text="seoTitle || name || 'Product title'"></p>
                <p class="truncate text-[11px] text-emerald-700">{{ url('/product') }}/…</p>
                <p class="mt-0.5 line-clamp-2 text-xs text-slate-600" x-text="metaDescription || 'Add a meta description to control the text shown under the title.'"></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SEO title <span class="font-normal text-slate-400" x-text="'(' + (seoTitle || '').length + '/60)'"></span></label>
                    <input type="text" name="seo_title" x-model="seoTitle" maxlength="255"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5" placeholder="Defaults to the product name">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Meta description <span class="font-normal text-slate-400" x-text="'(' + (metaDescription || '').length + '/160)'"></span></label>
                    <textarea name="meta_description" x-model="metaDescription" rows="2" maxlength="500"
                              class="block w-full rounded-lg border-slate-200 text-sm" placeholder="Defaults to the short summary"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share title (OG)</label>
                    <input type="text" name="og_title" value="{{ old('og_title', $prefill?->og_title ?? '') }}" maxlength="255"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5" placeholder="Defaults to the SEO title">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share description (OG)</label>
                    <textarea name="og_description" rows="2" maxlength="500"
                              class="block w-full rounded-lg border-slate-200 text-sm" placeholder="Defaults to the meta description">{{ old('og_description', $prefill?->og_description ?? '') }}</textarea>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share image (OG)</label>
                <div class="flex flex-wrap items-center gap-3">
                    @if($product?->og_image)
                        <img src="{{ public_storage_url($product->og_image) }}" alt="" class="h-16 w-28 rounded-lg border border-slate-200 object-cover">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                            <input type="checkbox" name="remove_og_image" value="1" class="rounded border-slate-300 text-rose-600"> Remove
                        </label>
                    @endif
                    <input type="file" name="og_image_file" accept="image/jpeg,image/png,image/webp"
                           class="text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">1200×630 recommended. Defaults to the main product photo.</p>
                @error('og_image_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    </div>{{-- /hasMode --}}

    <template x-teleport="body">
    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-[100050] flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-slate-900/45" @click="closeQuickModals()"></div>
        <div class="relative flex w-full max-w-lg max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-rose-100 bg-white shadow-2xl" @click.stop>
            <div class="shrink-0 border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add category</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Same fields as Categories → Add New.</p>
                </div>
                <button type="button" @click="closeQuickModals()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 space-y-3.5">
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Category name <span class="text-rose-500">*</span></label>
                    <input type="text" x-ref="quickCategoryInput" x-model="quickName" @input="suggestCategoryIcon()"
                           placeholder="e.g. Makeup, Skincare, Hair Care"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Cover tagline</label>
                    <input type="text" x-model="quickDescription" maxlength="255"
                           placeholder="e.g. Soft colour for every look"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-100">
                    <p class="mt-1 text-[11px] text-slate-400">Shown on the homepage category cover.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Cover image</label>
                    <input type="file" x-ref="quickCategoryFile" accept=".png,.jpg,.jpeg,.webp,.gif,image/*"
                           @change="onQuickFile($event, 'category')"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    <p class="mt-1 text-[11px] text-slate-400" x-text="quickFileLabel || 'PNG, JPG, WEBP or GIF — optional'"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Category icon</label>
                    <div class="grid max-h-36 grid-cols-4 gap-2 overflow-y-auto rounded-xl border border-slate-100 bg-slate-50/70 p-2 sm:grid-cols-5">
                        <template x-for="item in categoryIcons" :key="item.key">
                            <button type="button" @click="quickIcon = item.key"
                                    class="flex flex-col items-center gap-1 rounded-xl border p-2 transition"
                                    :class="quickIcon === item.key ? 'border-rose-500 bg-white ring-2 ring-rose-200' : 'border-transparent bg-white/70 hover:border-slate-200'"
                                    :title="item.label">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[10px] font-bold"
                                      :style="'background:' + item.bg + ';color:' + item.color"
                                      x-text="(item.label || item.key).slice(0, 1)"></span>
                                <span class="truncate w-full text-center text-[9px] font-semibold text-slate-600" x-text="item.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p x-show="quickError" x-text="quickError" class="text-[12px] font-medium text-rose-600"></p>
            </div>
            <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-5 py-3 flex justify-end gap-2">
                <button type="button" @click="closeQuickModals()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickCategory()" :disabled="quickLoading"
                        class="rounded-xl bg-[#C2185B] px-4 py-2 text-sm font-bold text-white hover:bg-[#AD1457] disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add category'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="brandModal" x-cloak class="fixed inset-0 z-[100050] flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-slate-900/45" @click="closeQuickModals()"></div>
        <div class="relative flex w-full max-w-lg max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-rose-100 bg-white shadow-2xl" @click.stop>
            <div class="shrink-0 border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add brand</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Same fields as Brands → Add Brand.</p>
                </div>
                <button type="button" @click="closeQuickModals()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 space-y-3.5">
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Brand name <span class="text-rose-500">*</span></label>
                    <input type="text" x-ref="quickBrandInput" x-model="quickName"
                           placeholder="e.g. Maybelline, NYX, L'Oréal"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Homepage tagline</label>
                    <input type="text" x-model="quickTagline" maxlength="120"
                           placeholder="e.g. Lips · Eyes · Face"
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-100">
                    <p class="mt-1 text-[11px] text-slate-400">Shown under the brand name on the homepage strip.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Brand logo</label>
                    <input type="file" x-ref="quickBrandFile" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/*"
                           @change="onQuickFile($event, 'brand')"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    <p class="mt-1 text-[11px] text-slate-400" x-text="quickFileLabel || 'Optional — PNG, JPG, WEBP, GIF or SVG. A text mark is used if skipped.'"></p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" x-model="quickActive" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    Show on website storefront
                </label>
                <p x-show="quickError" x-text="quickError" class="text-[12px] font-medium text-rose-600"></p>
            </div>
            <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-5 py-3 flex justify-end gap-2">
                <button type="button" @click="closeQuickModals()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickBrand()" :disabled="quickLoading"
                        class="rounded-xl bg-[#C2185B] px-4 py-2 text-sm font-bold text-white hover:bg-[#AD1457] disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add brand'"></span>
                </button>
            </div>
        </div>
    </div>
    </template>
</div>
