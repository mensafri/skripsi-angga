@props(['chart'])

{{-- Always-visible zoom toolbar for a Chart.js chart (out / reset / in). --}}
<div data-zoom="{{ $chart }}" class="shrink-0 inline-flex items-center rounded-lg border border-slate-200 bg-white divide-x divide-slate-200 overflow-hidden">
    <button type="button" data-zoom-action="out" aria-label="Perkecil"
            class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 active:bg-slate-200 transition-colors text-base leading-none">−</button>
    <button type="button" data-zoom-action="reset"
            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 active:bg-slate-200 transition-colors">Reset</button>
    <button type="button" data-zoom-action="in" aria-label="Perbesar"
            class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 active:bg-slate-200 transition-colors text-base leading-none">+</button>
</div>
