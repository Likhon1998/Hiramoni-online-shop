<x-app-layout>
    @php
        $inputCls = 'w-full h-9 rounded-md border border-slate-200 bg-white px-2.5 text-[12px] text-slate-800 focus:border-rose-400 focus:ring-1 focus:ring-rose-200';
        $labelCls = 'block text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1';
        $btnPrimary = 'inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-3.5 text-[12px] font-bold text-white shadow-sm hover:bg-indigo-700';
        $btnGhost = 'inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-[12px] font-medium text-slate-600 hover:bg-slate-50';
        $btnDanger = 'inline-flex h-9 items-center justify-center rounded-md border border-rose-300 bg-white px-3 text-[12px] font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40';
        $categoryList = ($categories ?? collect())->values();
        $oldCategoryIds = collect(old('category_ids', []))->map(fn ($id) => (string) $id);
    @endphp

    <div class="w-full min-w-0 pb-6 text-[12px] leading-snug text-slate-700"
         x-data="{
            editing: null,
            editingValue: null,
            editingValueAttr: null,
            lockScroll(on) {
                document.documentElement.classList.toggle('overflow-hidden', !!on);
                document.body.classList.toggle('overflow-hidden', !!on);
            },
            editAttr(id) {
                this.closeValue();
                this.editing = this.editing === id ? null : id;
            },
            editValue(attrId, valueId) {
                if (this.editingValue === valueId) {
                    this.closeValue();
                    return;
                }
                this.editingValue = valueId;
                this.editingValueAttr = attrId;
                this.editing = null;
                this.lockScroll(true);
            },
            closeValue() {
                this.editingValue = null;
                this.editingValueAttr = null;
                this.lockScroll(false);
            },
         }"
         @keydown.escape.window="if (editingValue) closeValue()">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[15px] font-semibold tracking-tight text-slate-900">Product Attributes</h1>
                <p class="text-[11px] text-slate-500">Reusable cosmetics attributes — assign them to categories and mark which ones create shades / sizes.</p>
            </div>
            <a href="{{ route('products.variants') }}" class="{{ $btnGhost }}">Variants</a>
            <a href="{{ route('products.index') }}" class="{{ $btnGhost }}">Products</a>
        </div>

        @if(session('success'))
            <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-700">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] text-rose-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- New attribute --}}
        <form method="POST" action="{{ route('attributes.store') }}" class="mb-3 rounded-xl border border-rose-100 bg-white p-3 shadow-sm"
              x-data="{
                  type: @js(old('type', 'select')),
                  isVariation: {{ old('is_variation', old('type') === 'color' ? '1' : '0') == '1' ? 'true' : 'false' }},
                  onTypeChange() { if (this.type === 'color') this.isVariation = true; },
              }">
            @csrf
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-[12px] font-semibold text-slate-900">New attribute</h2>
                <p class="text-[10px] text-slate-400">Leave categories empty = available on all products</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label class="{{ $labelCls }}">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="60" placeholder="e.g. Finish, SPF, Skin type" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Display as</label>
                    <select name="type" x-model="type" @change="onTypeChange()" class="{{ $inputCls }}">
                        @foreach(\App\Models\ProductAttribute::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelCls }}">Order</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order') }}" placeholder="0" class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2 lg:col-span-4" x-show="['select','color','multiselect'].includes(type)">
                    <label class="{{ $labelCls }}">
                        <span x-text="type === 'color' ? 'Shade names' : 'Values'"></span>
                        <span class="normal-case font-normal text-slate-400">(comma separated, optional)</span>
                    </label>
                    <input type="text" name="values" value="{{ old('values') }}"
                           :placeholder="type === 'color' ? 'Pioneer, Lover, Dreamer' : 'Matte, Dewy, Satin, Glossy'"
                           class="{{ $inputCls }}">
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="{{ $labelCls }}">Categories <span class="normal-case font-normal text-slate-400">(optional)</span></label>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($categoryList as $category)
                            <label class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50/80 px-2.5 py-1 text-[11px] font-medium text-slate-700 cursor-pointer hover:border-rose-300 hover:bg-rose-50 has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50 has-[:checked]:text-rose-800">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                       @checked($oldCategoryIds->contains((string) $category->id))
                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span>{{ $category->name }}</span>
                            </label>
                        @empty
                            <p class="text-[11px] text-slate-400">No categories yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="mt-2.5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-2.5">
                <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                    <input type="hidden" name="is_filterable" value="0">
                    <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable', '1') == '1') class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    Shop filter
                </label>
                <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                    <input type="hidden" name="is_variation" :value="isVariation ? 1 : 0">
                    <input type="checkbox" :checked="isVariation" @change="isVariation = $event.target.checked"
                           class="rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                    Variation dimension
                    <span class="text-[10px] text-slate-400" x-show="type === 'color'">(recommended for shades)</span>
                </label>
                <button type="submit" class="{{ $btnPrimary }} ml-auto">Create attribute</button>
            </div>
        </form>

        {{-- Attribute list --}}
        <div class="space-y-3">
            @forelse($productAttributes as $attribute)
                @php
                    $canPresets = $attribute->acceptsPresetOptions();
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-3 py-2.5">
                        <div class="mr-auto min-w-0">
                            <p class="text-[13px] font-semibold text-slate-900">
                                {{ $attribute->name }}
                                <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ \App\Models\ProductAttribute::TYPES[$attribute->type] ?? $attribute->type }}</span>
                                @if($attribute->is_filterable)
                                    <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Filter</span>
                                @endif
                                @if($attribute->is_variation)
                                    <span class="ml-1 rounded bg-fuchsia-50 px-1.5 py-0.5 text-[10px] font-medium text-fuchsia-700">Variation</span>
                                @endif
                            </p>
                            <p class="text-[10px] text-slate-400">
                                {{ $attribute->values->count() }} value{{ $attribute->values->count() === 1 ? '' : 's' }}
                                &middot; used by {{ $attribute->variant_values_count }} product(s)
                                @if($attribute->categories->isNotEmpty())
                                    &middot; {{ $attribute->categories->pluck('name')->join(', ') }}
                                @else
                                    &middot; all categories
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button type="button"
                                    @click="editAttr({{ $attribute->id }})"
                                    class="{{ $btnGhost }}"
                                    :class="editing === {{ $attribute->id }} && '!border-rose-300 !bg-rose-50 !text-rose-700'">
                                <span x-text="editing === {{ $attribute->id }} ? 'Close' : 'Edit'"></span>
                            </button>
                            <form method="POST" action="{{ route('attributes.destroy', $attribute) }}"
                                  data-confirm="Delete the {{ $attribute->name }} attribute and its unused values?"
                                  data-confirm-title="Delete attribute?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" @disabled($attribute->variant_values_count > 0)
                                        title="{{ $attribute->variant_values_count > 0 ? 'In use by products' : 'Delete' }}"
                                        class="{{ $btnDanger }}">Delete</button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('attributes.update', $attribute) }}"
                          x-show="editing === {{ $attribute->id }}" x-cloak
                          class="space-y-2 border-b border-slate-100 bg-slate-50/60 px-3 py-2.5">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-2 sm:grid-cols-[1fr_160px_80px] sm:items-end">
                            <div>
                                <label class="{{ $labelCls }}">Name</label>
                                <input type="text" name="name" value="{{ $attribute->name }}" required maxlength="60" class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Display as</label>
                                <select name="type" class="{{ $inputCls }}">
                                    @foreach(\App\Models\ProductAttribute::TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($attribute->type === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Order</label>
                                <input type="number" name="sort_order" min="0" value="{{ $attribute->sort_order }}" class="{{ $inputCls }}">
                            </div>
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Categories</label>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($categoryList as $category)
                                    <label class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 cursor-pointer hover:border-rose-300 hover:bg-rose-50 has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50 has-[:checked]:text-rose-800">
                                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                               @checked($attribute->categories->contains('id', $category->id))
                                               class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                        <span>{{ $category->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-[11px] text-slate-400">No categories yet.</p>
                                @endforelse
                            </div>
                            <p class="mt-1 text-[10px] text-slate-400">Untick all to make this attribute available on every category.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                                <input type="hidden" name="is_filterable" value="0">
                                <input type="checkbox" name="is_filterable" value="1" @checked($attribute->is_filterable) class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                Shop filter
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                                <input type="hidden" name="is_variation" value="0">
                                <input type="checkbox" name="is_variation" value="1" @checked($attribute->is_variation) class="rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                                Variation dimension
                            </label>
                            <button type="submit" class="{{ $btnPrimary }} ml-auto">Save</button>
                        </div>
                    </form>

                    <div class="px-3 py-2.5">
                        @if($canPresets)
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($attribute->values as $value)
                                    <button type="button"
                                            @click="editValue({{ $attribute->id }}, {{ $value->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white py-0.5 pl-1.5 pr-2 text-[11px] text-slate-700 hover:border-rose-300"
                                            :class="editingValue === {{ $value->id }} && 'border-rose-400 bg-rose-50 ring-1 ring-rose-200'">
                                        @if($attribute->isColor())
                                            <span class="h-3.5 w-3.5 shrink-0 rounded-full border border-black/10" style="background: {{ $value->swatchHex() }}"></span>
                                        @endif
                                        <span>{{ $value->value }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $value->variant_values_count }}</span>
                                    </button>
                                @empty
                                    <p class="text-[11px] text-slate-400">No values yet. Add some below, or they appear when you save a product.</p>
                                @endforelse
                            </div>

                            @foreach($attribute->values as $value)
                                <template x-teleport="body">
                                <div x-show="editingValue === {{ $value->id }}" x-cloak
                                     class="fixed inset-0 z-[100050] flex items-center justify-center p-4" x-transition.opacity
                                     @if($attribute->isColor()) x-data="{ editHex: @js($value->swatchHex()) }" @endif>
                                    <div class="absolute inset-0 bg-slate-900/45" @click="closeValue()"></div>
                                    <div class="relative flex w-full max-w-md max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-rose-100 bg-white shadow-2xl" @click.stop>
                                        <div class="shrink-0 border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-[15px] font-bold text-slate-900">Edit {{ $attribute->isColor() ? 'shade' : 'value' }}</h3>
                                                <p class="mt-0.5 text-[12px] text-slate-500">
                                                    {{ $attribute->name }}
                                                    @if($value->variant_values_count > 0)
                                                        · used by {{ $value->variant_values_count }} product{{ $value->variant_values_count === 1 ? '' : 's' }}
                                                    @endif
                                                </p>
                                            </div>
                                            <button type="button" @click="closeValue()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <form method="POST" action="{{ route('attributes.values.update', $value) }}" class="flex min-h-0 flex-1 flex-col">
                                            @csrf
                                            @method('PATCH')
                                            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 space-y-3.5">
                                                @if($attribute->isColor())
                                                    <div class="flex justify-center">
                                                        <label class="relative block h-16 w-16 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-md ring-1 ring-rose-200"
                                                               :style="'background-color:' + editHex"
                                                               title="Pick shade colour">
                                                            <input type="color" name="color_hex" x-model="editHex"
                                                                   class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                                                   aria-label="Shade swatch">
                                                        </label>
                                                    </div>
                                                    <p class="text-center font-mono text-[11px] font-semibold text-slate-500" x-text="editHex"></p>
                                                @endif
                                                <div>
                                                    <label class="{{ $labelCls }}">{{ $attribute->isColor() ? 'Shade name' : 'Value' }}</label>
                                                    <input type="text" name="value" value="{{ $value->value }}" required maxlength="80" class="{{ $inputCls }}" autofocus>
                                                </div>
                                                <div>
                                                    <label class="{{ $labelCls }}">Display order</label>
                                                    <input type="number" name="sort_order" min="0" value="{{ $value->sort_order }}" class="{{ $inputCls }}">
                                                </div>
                                            </div>
                                            <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-5 py-3 flex items-center gap-2">
                                                <button type="submit" class="{{ $btnPrimary }}">Save</button>
                                                <button type="button" @click="closeValue()" class="{{ $btnGhost }}">Cancel</button>
                                            </div>
                                        </form>
                                        @if($value->variant_values_count === 0)
                                            <form method="POST" action="{{ route('attributes.values.destroy', $value) }}"
                                                  class="shrink-0 border-t border-slate-100 px-5 py-3"
                                                  data-confirm="Delete value {{ $value->value }}?"
                                                  data-confirm-title="Delete value?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="{{ $btnDanger }} w-full">Delete</button>
                                            </form>
                                        @else
                                            <p class="shrink-0 border-t border-slate-100 px-5 py-3 text-[11px] text-slate-400">In use on products — cannot delete.</p>
                                        @endif
                                    </div>
                                </div>
                                </template>
                            @endforeach

                            <template x-if="editingValueAttr !== {{ $attribute->id }}">
                                <div>
                                @if($attribute->isColor())
                                    <form method="POST" action="{{ route('attributes.values.store', $attribute) }}"
                                          class="mt-2.5 flex items-center gap-2 rounded-lg border border-rose-100 bg-rose-50/40 px-2.5 py-2"
                                          x-data="{ pickedHex: '#E91E63', shadeName: '' }">
                                        @csrf
                                        <input type="hidden" name="color_hex" :value="pickedHex">
                                        <label class="relative block h-9 w-9 shrink-0 cursor-pointer overflow-hidden rounded-full border-2 border-white shadow ring-1 ring-rose-200"
                                               :style="'background-color:' + pickedHex"
                                               :title="'Pick colour ' + pickedHex">
                                            <input type="color" x-model="pickedHex"
                                                   class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                                   aria-label="Pick shade colour">
                                        </label>
                                        <input type="text" name="values" x-model="shadeName" maxlength="80"
                                               placeholder="Shade name (optional)"
                                               class="h-9 min-w-0 flex-1 rounded-md border border-slate-200 bg-white px-2.5 text-[12px] text-slate-800 focus:border-rose-400 focus:ring-1 focus:ring-rose-200">
                                        <button type="submit" class="{{ $btnPrimary }} w-auto shrink-0 px-4">Add shade</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('attributes.values.store', $attribute) }}"
                                          class="mt-2.5 flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="values" required placeholder="Add values, comma separated"
                                               class="h-9 min-w-0 flex-1 rounded-md border border-slate-200 bg-white px-2.5 text-[12px] text-slate-800 focus:border-rose-400 focus:ring-1 focus:ring-rose-200">
                                        <button type="submit" class="{{ $btnPrimary }} w-auto shrink-0 px-4">Add</button>
                                    </form>
                                @endif
                                </div>
                            </template>
                        @else
                            <p class="text-[11px] text-slate-500">
                                This type is entered freely on each product
                                @if($attribute->isBoolean())
                                    (Yes / No)
                                @endif
                                — no preset list needed.
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">No attributes yet.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
