@php
    $tab = (string) request('tab', $activeTab ?? ($services->first()?->id ?? 'new'));
    if ($errors->any() && ! $services->contains(fn ($s) => (string) $s->id === $tab)) {
        $tab = 'new';
    }
    $connectedCount = $services->filter->hasApiCredentials()->count();
    $activeCount = $services->where('is_active', true)->count();
@endphp
<x-app-layout>
    <div class="w-full pb-6">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Courier services</h1>
                <p class="mt-0.5 text-sm text-slate-500">Connect an API or keep booking local. Shipping still creates tracking.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">{{ $services->count() }} couriers</span>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $activeCount }} active</span>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-bold text-indigo-700 ring-1 ring-indigo-100">{{ $connectedCount }} API</span>
                <a href="{{ route('cms.delivery.edit') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Delivery settings</a>
            </div>
        </div>

        @if (session('success'))
            <div data-admin-flash-banner class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div data-admin-flash-banner class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
             x-data="{ tab: @js($tab) }">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50 px-3 pt-2">
                @foreach($services as $service)
                    <button type="button" @click="tab = '{{ $service->id }}'"
                            :class="tab === '{{ $service->id }}' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            class="mb-[-1px] flex items-center gap-1.5 whitespace-nowrap rounded-t-xl px-4 py-2.5 text-sm font-bold">
                        {{ $service->name }}
                        @if(! $service->is_active)
                            <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[9px] font-bold uppercase text-slate-500">Off</span>
                        @elseif($service->hasApiCredentials())
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        @endif
                    </button>
                @endforeach
                <button type="button" @click="tab = 'new'"
                        :class="tab === 'new' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="mb-[-1px] whitespace-nowrap rounded-t-xl px-4 py-2.5 text-sm font-bold">+ Add</button>
            </div>

            @foreach($services as $service)
                <div x-show="tab === '{{ $service->id }}'" x-cloak>
                    <form method="POST" action="{{ route('cms.couriers.update', $service) }}">
                        @csrf
                        @method('PUT')
                        @include('cms.couriers._panel', ['service' => $service, 'drivers' => $drivers])
                    </form>
                    <form id="test-{{ $service->id }}" method="POST" action="{{ route('cms.couriers.test', $service) }}" class="hidden">@csrf</form>
                    <form method="POST" action="{{ route('cms.couriers.destroy', $service) }}" class="px-5 pb-5 text-center" data-confirm="Remove {{ $service->name }}?" data-confirm-title="Remove courier?" data-confirm-ok="Remove" data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-semibold text-rose-400 hover:text-rose-600">Remove this courier</button>
                    </form>
                </div>
            @endforeach

            <div x-show="tab === 'new'" x-cloak>
                <form method="POST" action="{{ route('cms.couriers.store') }}">
                    @csrf
                    @include('cms.couriers._panel', ['service' => null, 'drivers' => $drivers])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
