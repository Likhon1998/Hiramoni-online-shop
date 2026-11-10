<x-app-layout>
@php
    $segmentLabels = \App\Services\CustomerSegmentService::SEGMENTS;
    $segmentBadges = \App\Services\CustomerSegmentService::BADGES;
    $badgeClasses = \App\Support\OrderStatus::badgeClasses();
@endphp

<div class="space-y-4 text-[12px] text-slate-700">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('customers.index') }}" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700">← Back to customers</a>
            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $customer->name }}</h1>
                <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $customer->user_id ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">{{ $customer->user_id ? 'Online account' : 'Guest (no account)' }}</span>
                @foreach($segments as $segment)
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $segmentBadges[$segment] }}">{{ $segmentLabels[$segment] }}</span>
                @endforeach
            </div>
            <p class="mt-0.5 text-[12px] text-slate-500">{{ $customer->phone ?: 'No phone' }}{{ $customer->email ? ' · '.$customer->email : '' }} · customer since {{ $customer->created_at?->format('d M Y') }}</p>
            @if($customer->address)<p class="text-[12px] text-slate-500">{{ $customer->address }}</p>@endif
            @if($stats['is_repeat'])
                <p class="mt-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700">Repeat customer</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($customer->is_blocked)
                <form method="POST" action="{{ route('customers.unblock', $customer) }}">
                    @csrf
                    <button class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12px] font-bold text-emerald-800 hover:bg-emerald-100">Unblock</button>
                </form>
            @else
                <form method="POST" action="{{ route('customers.block', $customer) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="reason" maxlength="255" placeholder="Block reason (optional)" class="w-44 rounded-xl border-slate-200 text-[12px]">
                    <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] font-bold text-rose-800 hover:bg-rose-100">Block</button>
                </form>
            @endif
            <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[12px] font-bold text-slate-700 hover:bg-slate-50">Edit</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12px] font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] font-semibold text-rose-800">{{ session('error') }}</div>
    @endif
    @if($customer->is_blocked)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] text-rose-800">
            <span class="font-bold">Blocked</span>
            {{ $customer->blocked_at?->format('d M Y') }}
            @if($customer->blocked_reason) — {{ $customer->blocked_reason }}@endif
            · cannot place new orders or sign in.
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Orders</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stats['orders'] }}</p>
            <p class="text-[11px] text-slate-500">Excludes cancelled / returned / refunded</p>
            @if($stats['returns'] > 0)
                <p class="text-[11px] font-semibold text-rose-600">{{ $stats['returns'] }} returned / refused</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total purchase</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ format_taka($stats['spent']) }}</p>
            <p class="text-[11px] text-slate-500">Products only (no delivery)</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Customer lifetime value</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-800">{{ format_taka($stats['clv']) }}</p>
            <p class="text-[11px] text-emerald-700/80">All paid orders including delivery</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Average order</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ format_taka($stats['average']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Last order</p>
            <p class="mt-1 text-[15px] font-extrabold text-slate-900">{{ $stats['last_order_at']?->format('d M Y') ?? '—' }}</p>
            <p class="text-[11px] text-slate-500">{{ $stats['last_order_at']?->diffForHumans() ?? 'No orders yet' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <h2 class="border-b border-slate-100 px-4 py-3 text-[14px] font-bold text-slate-900">Complete order history</h2>
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Channel</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $isOnline = $order->counter_id === null && str_starts_with((string) $order->invoice_no, 'WEB-');
                            $status = \App\Support\OrderStatus::normalize($order->status);
                        @endphp
                        <tr>
                            <td class="px-3 py-2">
                                @if($isOnline && auth()->user()->can('manage orders'))
                                    <a href="{{ route('online-orders.show', $order) }}" class="font-semibold text-indigo-600 hover:underline">{{ $order->invoice_no }}</a>
                                @else
                                    <span class="font-semibold text-slate-800">{{ $order->invoice_no }}</span>
                                @endif
                                <p class="text-[10px] text-slate-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                            </td>
                            <td class="px-3 py-2">{{ $isOnline ? 'Online' : 'In-store (legacy)' }}{{ $order->lead_id ? ' · lead' : '' }}{{ $order->utm_source ? ' · '.$order->utm_source : '' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $badgeClasses[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">{{ $statusLabels[$status] ?? ucfirst((string) $order->status) }}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">{{ format_taka((float) $order->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($orders->hasPages())
                <div class="border-t border-slate-100 px-3 py-2">{{ $orders->links() }}</div>
            @endif
        </div>

        <div class="space-y-4">
            @can('manage leads')
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-[14px] font-bold text-slate-900">Leads</h2>
                        <a href="{{ route('leads.create', ['name' => $customer->name, 'phone' => $customer->phone]) }}" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ New lead</a>
                    </div>
                    @forelse($leads as $lead)
                        <a href="{{ route('leads.show', $lead) }}" class="flex items-center justify-between border-t border-slate-100 py-1.5 first:border-0 hover:text-indigo-700">
                            <span>{{ $lead->sourceLabel() }} · {{ $lead->created_at->format('d M Y') }}</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ \App\Models\Lead::statusBadge($lead->status) }}">{{ $lead->statusLabel() }}</span>
                        </a>
                    @empty
                        <p class="text-[12px] text-slate-400">No leads for this customer.</p>
                    @endforelse
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-2 text-[14px] font-bold text-slate-900">Carts</h2>
                    @forelse($carts as $cart)
                        <a href="{{ route('abandoned-carts.show', $cart) }}" class="flex items-center justify-between border-t border-slate-100 py-1.5 text-[12px] first:border-0 hover:text-indigo-700">
                            <span>{{ $cart->item_count }} {{ \Illuminate\Support\Str::plural('item', $cart->item_count) }} · {{ format_taka((float) $cart->subtotal) }} · {{ $cart->last_activity_at?->format('d M Y') }}</span>
                            <span class="text-[10px] font-bold text-slate-500">{{ $cart->displayStatus() }}</span>
                        </a>
                    @empty
                        <p class="text-[12px] text-slate-400">No saved or abandoned carts.</p>
                    @endforelse
                </div>
            @endcan

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-[14px] font-bold text-slate-900">Delivery addresses</h2>
                <ul class="space-y-2">
                    @forelse($customer->addresses as $address)
                        <li class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-[12px] font-bold text-slate-800">{{ $address->label }} @if($address->is_default)<span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-emerald-800">Default</span>@endif</p>
                                    <p class="text-[12px] text-slate-600">{{ $address->address }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $address->name }} {{ $address->phone }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col gap-1">
                                    @unless($address->is_default)
                                        <form method="POST" action="{{ route('customers.addresses.default', [$customer, $address]) }}">@csrf<button class="text-[10px] font-bold text-indigo-600">Default</button></form>
                                    @endunless
                                    <form method="POST" action="{{ route('customers.addresses.destroy', [$customer, $address]) }}" onsubmit="return confirm('Remove this address?')">@csrf @method('DELETE')<button class="text-[10px] font-bold text-rose-600">Remove</button></form>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-[12px] text-slate-400">No saved addresses yet.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('customers.addresses.store', $customer) }}" class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                    @csrf
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Add address</p>
                    <input name="label" placeholder="Home, Office…" class="w-full rounded-lg border-slate-200 text-[12px]">
                    <textarea name="address" rows="2" required placeholder="Full delivery address" class="w-full rounded-lg border-slate-200 text-[12px]"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="name" placeholder="Recipient name" class="rounded-lg border-slate-200 text-[12px]">
                        <input name="phone" placeholder="Phone" class="rounded-lg border-slate-200 text-[12px]">
                    </div>
                    <label class="flex items-center gap-2 text-[11px] text-slate-600"><input type="checkbox" name="is_default" value="1" class="rounded border-slate-300"> Set as default</label>
                    <button class="rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white">Save address</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-[14px] font-bold text-slate-900">Customer notes</h2>
                <form method="POST" action="{{ route('customers.notes.store', $customer) }}" class="mb-3">
                    @csrf
                    <textarea name="body" rows="3" required maxlength="2000" placeholder="Internal note (not shown to the customer)" class="w-full rounded-lg border-slate-200 text-[12px]"></textarea>
                    <button class="mt-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white">Add note</button>
                </form>
                <ul class="space-y-2">
                    @forelse($customer->notes as $note)
                        <li class="rounded-xl border border-slate-100 px-3 py-2">
                            <p class="whitespace-pre-line text-[12px] text-slate-700">{{ $note->body }}</p>
                            <div class="mt-1 flex items-center justify-between text-[10px] text-slate-400">
                                <span>{{ $note->author?->name ?? 'Staff' }} · {{ $note->created_at->format('d M Y, h:i A') }}</span>
                                <form method="POST" action="{{ route('customers.notes.destroy', [$customer, $note]) }}" onsubmit="return confirm('Delete this note?')">@csrf @method('DELETE')<button class="font-bold text-rose-600">Delete</button></form>
                            </div>
                        </li>
                    @empty
                        <li class="text-[12px] text-slate-400">No notes yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-[14px] font-bold text-slate-900">Segment rules</h2>
                @php $rules = config('commerce.segments'); @endphp
                <ul class="space-y-1 text-[11px] text-slate-500">
                    <li><b class="text-slate-700">New</b> — first order (or sign-up) in the last {{ $rules['new_days'] }} days</li>
                    <li><b class="text-slate-700">Returning</b> — 2 or more orders</li>
                    <li><b class="text-slate-700">Frequent buyer</b> — {{ $rules['frequent_orders'] }}+ orders</li>
                    <li><b class="text-slate-700">High value</b> — spent {{ format_taka($rules['high_value_spend']) }}+</li>
                    <li><b class="text-slate-700">VIP</b> — spent {{ format_taka($rules['vip_spend']) }}+</li>
                    <li><b class="text-slate-700">Inactive</b> — no order for {{ $rules['inactive_days'] }} days</li>
                    <li><b class="text-slate-700">COD risk</b> — {{ $rules['cod_risk_returns'] ?? 2 }}+ returned / refused orders</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
