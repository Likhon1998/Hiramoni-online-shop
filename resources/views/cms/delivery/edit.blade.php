<x-app-layout>
    <form method="POST" action="{{ route('cms.delivery.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Delivery settings</h1>
                <p class="mt-0.5 text-sm text-slate-500">Zones, fees, free delivery, COD, and confirmation — used live at checkout.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('cms.couriers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Couriers</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save settings</button>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif

        @php
            $zoneRows = old('zones', $zones->map(fn ($z) => [
                'id' => $z->id, 'name' => $z->name, 'code' => $z->code, 'fee' => (float) $z->fee,
                'note' => $z->note, 'is_active' => $z->is_active,
            ])->values()->all());
            $defaultZone = old('default_zone', optional($zones->firstWhere('is_default', true))->code ?? optional($zones->first())->code);
        @endphp

        <div class="grid gap-4 xl:grid-cols-12">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-7"
                 x-data="{ zones: @js(array_values($zoneRows)), defaultZone: @js($defaultZone), add() { this.zones.push({ id: null, name: '', code: '', fee: 0, note: '', is_active: true }); } }">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Delivery zones</h2>
                        <p class="text-[11px] text-slate-500">Shown at checkout. Codes lock after save.</p>
                    </div>
                    <button type="button" @click="add()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">+ Add zone</button>
                </div>
                <x-input-error class="px-4 pt-2" :messages="$errors->get('zones')" />
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Name</th>
                                <th class="px-3 py-2 text-left">Code</th>
                                <th class="px-3 py-2 text-left">Fee (৳)</th>
                                <th class="px-3 py-2 text-left">Note</th>
                                <th class="px-3 py-2 text-right">Flags</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(zone, i) in zones" :key="i">
                                <tr :class="zone.delete ? 'bg-rose-50/60 opacity-50' : 'bg-white'">
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="`zones[${i}][id]`" :value="zone.id ?? ''">
                                        <input type="hidden" :name="`zones[${i}][delete]`" :value="zone.delete ? 1 : 0">
                                        <input type="text" :name="`zones[${i}][name]`" x-model="zone.name" maxlength="80" required placeholder="e.g. Chattogram"
                                               class="w-full rounded-lg border-slate-200 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="`zones[${i}][code]`" x-model="zone.code" maxlength="40" placeholder="auto"
                                               class="w-full rounded-lg border-slate-200 font-mono text-xs" :readonly="!!zone.id">
                                    </td>
                                    <td class="px-3 py-2 w-28">
                                        <input type="number" step="0.01" min="0" :name="`zones[${i}][fee]`" x-model="zone.fee" required
                                               class="w-full rounded-lg border-slate-200 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="`zones[${i}][note]`" x-model="zone.note" maxlength="160" placeholder="1–2 days"
                                               class="w-full rounded-lg border-slate-200 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end gap-2.5 whitespace-nowrap">
                                            <label class="flex items-center gap-1 text-[11px] font-semibold text-slate-600">
                                                <input type="checkbox" :name="`zones[${i}][is_active]`" value="1" x-model="zone.is_active" class="rounded border-slate-300 text-indigo-600">
                                                On
                                            </label>
                                            <label class="flex items-center gap-1 text-[11px] font-semibold text-slate-600">
                                                <input type="radio" name="default_zone" :value="zone.code" x-model="defaultZone" :disabled="!zone.code" class="border-slate-300 text-indigo-600">
                                                Default
                                            </label>
                                            <button type="button" @click="zone.id ? (zone.delete = !zone.delete) : zones.splice(i, 1)"
                                                    class="text-[11px] font-bold text-rose-600" x-text="zone.delete ? 'Undo' : 'Remove'"></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col gap-4 xl:col-span-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Free delivery</h2>
                    <label class="mt-3 flex items-start gap-2.5">
                        <input type="checkbox" name="delivery_free_enabled" value="1"
                               class="mt-0.5 rounded border-slate-300 text-indigo-600"
                               @checked(old('delivery_free_enabled', $settings->delivery_free_enabled ?? true))>
                        <span class="text-sm font-semibold text-slate-800">Waive fee above a cart total</span>
                    </label>
                    <label class="mt-3 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Minimum (৳)</label>
                    <input type="number" step="0.01" min="0" name="delivery_free_min_amount"
                           value="{{ old('delivery_free_min_amount', $settings->delivery_free_min_amount ?? 10000) }}"
                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    <x-input-error class="mt-1" :messages="$errors->get('delivery_free_min_amount')" />
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Payment at checkout</h2>
                    <p class="text-[11px] text-slate-500">Keep at least one option on.</p>
                    <x-input-error class="mt-1" :messages="$errors->get('delivery_cod_enabled')" />
                    <label class="mt-3 flex items-start gap-2.5">
                        <input type="checkbox" name="delivery_cod_enabled" value="1"
                               class="mt-0.5 rounded border-slate-300 text-indigo-600"
                               @checked(old('delivery_cod_enabled', $settings->delivery_cod_enabled ?? true))>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Cash on delivery</span>
                            <span class="text-[11px] text-slate-500">Full bill collected with the parcel.</span>
                        </span>
                    </label>
                    <label class="mt-3 flex items-start gap-2.5">
                        <input type="checkbox" name="delivery_confirmation_enabled" value="1"
                               class="mt-0.5 rounded border-slate-300 text-indigo-600"
                               @checked(old('delivery_confirmation_enabled', $settings->delivery_confirmation_enabled ?? false))>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Confirmation / advance</span>
                            <span class="text-[11px] text-slate-500">Small payment up front; rest on delivery.</span>
                        </span>
                    </label>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Amount (৳)</label>
                            <input type="number" step="0.01" min="0" name="delivery_confirmation_amount"
                                   value="{{ old('delivery_confirmation_amount', $settings->delivery_confirmation_amount ?? 0) }}"
                                   class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </div>
                    </div>
                    <label class="mt-3 block text-[10px] font-bold uppercase tracking-wide text-slate-400">How to pay (checkout)</label>
                    <textarea name="delivery_confirmation_instructions" rows="3" maxlength="1000"
                              class="mt-1 w-full rounded-xl border-slate-200 text-sm"
                              placeholder="bKash / Nagad number and type">{{ old('delivery_confirmation_instructions', $settings->delivery_confirmation_instructions) }}</textarea>
                    <x-input-error class="mt-1" :messages="$errors->get('delivery_confirmation_amount')" />
                    <x-input-error class="mt-1" :messages="$errors->get('delivery_confirmation_instructions')" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-12">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Online payment gateways</h2>
                        <p class="text-[11px] text-slate-500">Stay off until credentials are in <code>.env</code>.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save settings</button>
                </div>
                <div class="grid gap-px bg-slate-100 sm:grid-cols-3">
                    @forelse(collect($paymentMethods)->where('type', 'gateway') as $method)
                        <div class="flex items-center justify-between gap-3 bg-white px-4 py-3">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $method['label'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $method['available'] ? 'Configured' : $method['reason'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $method['available'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $method['available'] ? 'Ready' : 'Not active' }}
                            </span>
                        </div>
                    @empty
                        <div class="bg-white px-4 py-3 text-sm text-slate-500 sm:col-span-3">No gateway methods configured.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
