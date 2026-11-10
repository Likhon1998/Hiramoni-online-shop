@php
    $s = $service;
    $isNew = $s === null;
    $driverStart = old('driver', $s?->driverKey() ?: 'manual');
    $connected = $s?->hasApiCredentials();
@endphp
<div class="px-5 py-5 sm:px-6" x-data="{ driver: @js($driverStart), more: false }">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <h3 class="text-base font-black text-slate-900">{{ $isNew ? 'New courier' : $s->name }}</h3>
            @unless($isNew)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $connected ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ $connected ? 'API ready' : 'Local booking' }}
                </span>
            @endunless
        </div>
        <p class="text-xs text-slate-500">
            <template x-if="driver === 'manual'"><span>Staff can type tracking when the parcel is handed over.</span></template>
            <template x-if="driver === 'steadfast'"><span>Paste API key &amp; secret from the Steadfast merchant panel.</span></template>
            <template x-if="driver === 'pathao'"><span>Pathao merchant API — client, store, and city / zone ids.</span></template>
            <template x-if="driver === 'redx'"><span>RedX access token plus the delivery area id.</span></template>
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Display name *</label>
            <input name="name" required value="{{ old('name', $s?->name) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Steadfast">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Contact phone</label>
            <input name="phone" value="{{ old('phone', $s?->phone) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Optional">
        </div>
    </div>

    <div class="mt-5">
        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">How parcels are booked</p>
        <input type="hidden" name="driver" :value="driver">
        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
            @foreach(['manual' => 'Manual', 'steadfast' => 'Steadfast', 'pathao' => 'Pathao', 'redx' => 'RedX'] as $key => $label)
                <button type="button" @click="driver = '{{ $key }}'"
                        :class="driver === '{{ $key }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-800 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        class="rounded-xl border px-3 py-2.5 text-xs font-bold">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2" x-show="driver !== 'manual'" x-cloak>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="driver === 'redx' ? 'API access token' : (driver === 'pathao' ? 'Client ID' : 'API key')"></label>
            <input name="api_key" value="" autocomplete="off" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="{{ $connected ? 'Saved — type to replace' : 'From courier panel' }}">
        </div>
        <div x-show="driver !== 'redx'">
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="driver === 'pathao' ? 'Client secret' : 'Secret key'"></label>
            <input name="api_secret" type="password" value="" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="{{ $connected ? 'Saved — type to replace' : '' }}">
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2" x-show="driver === 'pathao'" x-cloak>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Store ID</label>
            <input name="store_id" value="{{ old('store_id', $s?->extra('store_id')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Merchant username</label>
            <input name="username" value="{{ old('username', $s?->extra('username')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Merchant password</label>
            <input name="password" type="password" value="" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="{{ $s?->extra('password') ? 'Saved — type to replace' : '' }}">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">City ID</label>
                <input name="city_id" value="{{ old('city_id', $s?->extra('city_id')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Zone ID</label>
                <input name="zone_id" value="{{ old('zone_id', $s?->extra('zone_id')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2" x-show="driver === 'redx'" x-cloak>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Area ID</label>
            <input name="area_id" value="{{ old('area_id', $s?->extra('area_id')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Area name</label>
            <input name="area_name" value="{{ old('area_name', $s?->extra('area_name')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Dhanmondi">
        </div>
    </div>

    <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600" x-show="driver === 'manual'" x-cloak>
        No API keys needed. Shipping creates a local tracking ID, or you can type the courier’s own number.
    </div>

    <div class="mt-4">
        <button type="button" @click="more = !more" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
            <span x-text="more ? 'Hide extra settings' : 'Extra settings (webhook, URL, notes)'"></span>
        </button>
    </div>

    <div class="mt-3 grid gap-3 sm:grid-cols-2" x-show="more" x-cloak>
        <div class="sm:col-span-2">
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">API base URL</label>
            <input name="api_base_url" value="{{ old('api_base_url', $s?->api_base_url) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Blank = official live API">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Webhook secret</label>
            <input name="webhook_secret" value="{{ old('webhook_secret', $s?->webhook_secret) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Optional">
        </div>
        <div>
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $s?->sort_order ?? 0) }}" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Internal notes</label>
            <input name="notes" value="{{ old('notes', $s?->notes) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
        </div>
        <p class="sm:col-span-2 text-[11px] text-slate-400">Webhook: <code class="rounded bg-slate-50 px-1">{{ url('/webhooks/courier') }}/<span x-text="driver"></span></code></p>
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
        @unless($isNew)
            <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-indigo-600" @checked($s->is_active)>
                Active for shipping
            </label>
        @endunless
        <button class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
            {{ $isNew ? 'Add courier' : 'Save changes' }}
        </button>
        @unless($isNew)
            <button type="submit" form="test-{{ $s->id }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    x-show="driver === 'steadfast'" x-cloak>
                Test connection
            </button>
        @endunless
    </div>
</div>
