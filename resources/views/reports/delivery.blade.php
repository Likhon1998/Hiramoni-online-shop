<x-app-layout>
    <div class="pt-0 pb-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="relative bg-slate-900 rounded-2xl overflow-hidden">
                <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between px-6 py-5 gap-4">
                    <div>
                        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.15em]">Delivery</span>
                        <h2 class="text-lg font-bold text-white leading-tight">Success &amp; failure report</h2>
                        <p class="text-xs text-slate-300">Consignments booked in this date range — delivered vs returned/failed.</p>
                    </div>
                    <form action="{{ route('reports.delivery') }}" method="GET" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">From</label>
                            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 [color-scheme:dark]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">To</label>
                            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 [color-scheme:dark]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">Courier</label>
                            <select name="courier_id" class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2">
                                <option value="">All</option>
                                @foreach($couriers as $courier)
                                    <option value="{{ $courier->id }}" @selected((int) $courierId === (int) $courier->id)>{{ $courier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">Type</label>
                            <select name="type" class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2">
                                <option value="forward" @selected($type === 'forward')>Outbound</option>
                                <option value="return" @selected($type === 'return')>Returns</option>
                                <option value="all" @selected($type === 'all')>All</option>
                            </select>
                        </div>
                        <button class="rounded-xl bg-indigo-500 px-4 py-2 text-xs font-bold text-white">Apply</button>
                    </form>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Booked</p>
                    <p class="mt-1 text-xl font-black text-slate-950">{{ $booked }}</p>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-sky-600">In transit</p>
                    <p class="mt-1 text-xl font-black text-sky-950">{{ $inTransit }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-600">Delivered</p>
                    <p class="mt-1 text-xl font-black text-emerald-950">{{ $success }}</p>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-rose-600">Failed / returned</p>
                    <p class="mt-1 text-xl font-black text-rose-950">{{ $failed }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-600">Success rate</p>
                    <p class="mt-1 text-xl font-black text-indigo-950">{{ $rate }}%</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Invoice</th>
                            <th class="px-4 py-2.5 text-left">Courier</th>
                            <th class="px-4 py-2.5 text-left">Tracking</th>
                            <th class="px-4 py-2.5 text-left">Type</th>
                            <th class="px-4 py-2.5 text-left">Consignment</th>
                            <th class="px-4 py-2.5 text-left">Order</th>
                            <th class="px-4 py-2.5 text-left">Booked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    @if($row->order)
                                        <a href="{{ route('online-orders.show', $row->order) }}" class="font-semibold text-indigo-700 hover:underline">{{ $row->order->invoice_no }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $row->courierService?->name ?: '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row->tracking_number ?: '—' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $row->type }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold">{{ \App\Models\CourierConsignment::statusLabel($row->status) }}</span>
                                    @if($row->courier_status)
                                        <span class="block text-[10px] text-slate-400">{{ $row->courier_status }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $statusLabels[$row->order?->status] ?? $row->order?->status }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ optional($row->booked_at ?? $row->created_at)->format('d M, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">No consignments in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
