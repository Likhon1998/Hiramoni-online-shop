<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Add beauty product') }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">Simple, variable shades, or combo kits — category attributes adapt automatically{{ retail_enabled() ? ' · store & POS' : '' }}.</p>
            </div>
            <a href="{{ $returnTo['url'] }}" class="text-sm font-medium text-slate-500 hover:text-rose-600 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ $returnTo['label'] }}
            </a>
        </div>
    </x-slot>

    <div class="py-5 max-w-4xl mx-auto sm:px-6 lg:px-8 pb-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-medium mb-1">Please fix the following:</p>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="product-create-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-0"
              x-data="{ submitting: false }"
              @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
            @csrf
            @if($returnTo['key'])
                <input type="hidden" name="return_to" value="{{ $returnTo['key'] }}">
            @endif

            @error('form')
                <div class="mb-4 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
            @enderror

            @include('products.partials.form-fields')

            <div class="mt-5 flex items-center justify-end gap-2 sticky bottom-0 z-20 bg-[#FFF8FB]/95 backdrop-blur py-3 border-t border-rose-100/80 shadow-[0_-8px_24px_rgba(233,30,99,0.06)]">
                <a href="{{ $returnTo['url'] }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-rose-50">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-[#C2185B] rounded-lg hover:bg-[#AD1457] inline-flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                        form="product-create-form">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span x-text="submitting ? 'Saving…' : 'Save product'"></span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
