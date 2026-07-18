@extends('layouts.app')

@section('title', $research['title'])

@section('content')
@php $method = $research['method']; @endphp

{{-- Top bar --}}
<div class="h-1 bg-gradient-to-r from-sky-500 via-indigo-500 to-sky-500"></div>
<header class="bg-slate-900 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <p class="text-sky-400 font-semibold text-sm">Dashboard Hasil Penelitian</p>
        <h1 class="mt-2 text-2xl sm:text-4xl font-bold leading-tight text-balance max-w-3xl">{{ $research['title'] }}</h1>
        <p class="mt-3 text-slate-300 max-w-2xl leading-relaxed">{{ $research['subtitle'] }}</p>
        <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
            @foreach ([
                ['Algoritma', $method['algorithm']],
                ['Sampel data', number_format($method['dataset_size'])],
                ['Jumlah klaster', $method['clusters']],
                ['Hari observasi', count($research['days'])],
            ] as [$k, $v])
                <div>
                    <dt class="text-slate-400 text-xs">{{ $k }}</dt>
                    <dd class="mt-0.5 font-semibold">{{ $v }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</header>

@if (! $seeded)
    <main class="max-w-2xl mx-auto px-4 py-20">
        <div class="card p-8 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-xl">!</div>
            <h2 class="mt-4 text-xl font-bold text-slate-900">Database belum berisi data</h2>
            <p class="mt-2 text-slate-600">Impor dataset hasil klasterisasi dengan menjalankan migrasi &amp; seeder:</p>
            <pre class="mt-4 text-left bg-slate-900 text-slate-100 rounded-xl p-4 overflow-x-auto text-sm">./vendor/bin/sail artisan migrate --seed</pre>
        </div>
    </main>
@else

@php
    $payload = [
        'distribution' => $distribution,
        'timeseries'   => $timeseries,
        'scatter'      => $scatter,
        'perDay'       => $perDay,
        'levelColors'  => collect($research['levels'])->map(fn ($v) => $v['color']),
    ];
@endphp

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-6">

    {{-- Cluster legend --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-600">
        <span class="font-medium text-slate-500">Tingkat gangguan:</span>
        @foreach ($distribution as $d)
            <span class="inline-flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $d['color'] }}"></span>
                {{ $d['label'] }}
                <span class="text-slate-500">· {{ $d['percent'] }}%</span>
            </span>
        @endforeach
    </div>

    {{-- What the study does --}}
    <section class="card p-6 sm:p-7">
        <h2 class="text-lg font-semibold text-slate-900">Apa yang dilakukan pada penelitian ini</h2>
        <p class="mt-2 text-slate-600 leading-relaxed max-w-3xl">
            Trafik jaringan dimonitor melalui SNMP selama {{ count($research['days']) }} hari kerja — merekam
            <span class="text-slate-800 font-medium">latency</span>,
            <span class="text-slate-800 font-medium">packet loss</span>, dan
            <span class="text-slate-800 font-medium">throughput agregat</span> setiap 3 menit. Data dibersihkan
            menjadi {{ number_format($method['dataset_size']) }} sampel, distandarisasi dengan
            {{ $method['scaler'] }}, lalu dikelompokkan memakai algoritma <span class="font-semibold text-slate-900">{{ $method['algorithm'] }}</span>
            ke dalam {{ $method['clusters'] }} tingkat gangguan.
        </p>
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-px bg-slate-200 rounded-xl overflow-hidden border border-slate-200">
            @foreach ([
                ['Fitur K-Means', count($method['features']) . ' fitur'],
                ['Standarisasi', 'Z-score'],
                ['Jumlah klaster', $method['clusters']],
                ['Random state', $method['random_state']],
                ['N init', $method['n_init']],
                ['WCSS / SSE', number_format($method['wcss'], 2)],
            ] as [$k, $v])
                <div class="bg-white px-4 py-3">
                    <dt class="text-xs text-slate-500">{{ $k }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900 tabular-nums">{{ $v }}</dd>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-xs text-slate-500">Fitur:</span>
            @foreach ($method['features'] as $f)
                <code class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-mono">{{ $f }}</code>
            @endforeach
        </div>
    </section>

    {{-- Headline results --}}
    @php
        $kpis = [
            ['label' => 'Total sampel',           'value' => $stats['total'],          'decimals' => 0, 'suffix' => '',    'sub' => $stats['days'] . ' hari observasi',                        'rose' => false],
            ['label' => 'Rata-rata latency',      'value' => $stats['avg_latency'],    'decimals' => 3, 'suffix' => ' ms', 'sub' => 'puncak ' . $stats['max_latency'] . ' ms',                'rose' => false],
            ['label' => 'Rata-rata packet loss',  'value' => $stats['avg_packetloss'], 'decimals' => 2, 'suffix' => '%',   'sub' => 'puncak ' . $stats['max_packetloss'] . '%',               'rose' => false],
            ['label' => 'Sampel gangguan tinggi', 'value' => $stats['high_count'],     'decimals' => 0, 'suffix' => '',    'sub' => 'dari ' . number_format($stats['total']) . ' sampel',     'rose' => true],
        ];
    @endphp
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($kpis as $kpi)
            <div class="card reveal p-5">
                <p class="text-sm text-slate-500">{{ $kpi['label'] }}</p>
                <p class="mt-1.5 text-2xl font-semibold num {{ $kpi['rose'] ? 'text-rose-600' : 'text-slate-900' }}"
                   data-countup="{{ $kpi['value'] }}" data-decimals="{{ $kpi['decimals'] }}" data-suffix="{{ $kpi['suffix'] }}">{{ number_format($kpi['value'], $kpi['decimals']) . $kpi['suffix'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- Distribution + centroid --}}
    <section class="grid lg:grid-cols-5 gap-6">
        <div class="card p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold text-slate-900">Distribusi tingkat gangguan</h2>
            <p class="text-sm text-slate-500 mt-1">Proporsi sampel tiap klaster, dihitung dari data.</p>
            <div class="mt-5 relative mx-auto" style="max-width:200px">
                <canvas id="distChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-semibold text-slate-900 num">{{ number_format($stats['total']) }}</span>
                    <span class="text-xs text-slate-500">sampel</span>
                </div>
            </div>
            <div class="mt-5 space-y-1" data-legend-group="distChart">
                @foreach ($distribution as $d)
                    <button type="button" data-legend="{{ $loop->index }}"
                            class="w-full flex items-center justify-between text-sm rounded-lg px-2 py-1.5 -mx-2 hover:bg-slate-50 transition-colors">
                        <span class="inline-flex items-center gap-2 font-medium text-slate-700">
                            <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $d['color'] }}"></span>
                            {{ $d['label'] }}
                        </span>
                        <span class="num text-slate-900 font-semibold">{{ number_format($d['count']) }}
                            <span class="text-slate-500 font-normal">· {{ $d['percent'] }}%</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="card p-6 lg:col-span-3">
            <h2 class="text-lg font-semibold text-slate-900">Centroid per klaster</h2>
            <p class="text-sm text-slate-500 mt-1">Rata-rata tiap fitur — ciri khas masing-masing tingkat gangguan.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium text-left">Tingkat</th>
                            <th class="py-2 px-3 font-medium text-right">Latency (ms)</th>
                            <th class="py-2 px-3 font-medium text-right">Packet loss (%)</th>
                            <th class="py-2 pl-3 font-medium text-right">Throughput (Mbps)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($centroids as $c)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-3">
                                    <span class="inline-flex items-center gap-2 font-medium text-slate-800">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $c['color'] }}"></span>
                                        {{ $c['label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right tabular-nums text-slate-700">{{ $c['latency_ms'] }}</td>
                                <td class="py-3 px-3 text-right tabular-nums text-slate-700">{{ $c['packet_loss_percent'] }}</td>
                                <td class="py-3 pl-3 text-right tabular-nums text-slate-700">{{ $c['total_traffic_mbps'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">Dihitung ulang dari dataset; dapat sedikit berbeda dari laporan karena pembulatan.</p>
        </div>
    </section>

    {{-- Latency over time --}}
    <section class="card p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Latency dari waktu ke waktu</h2>
                <p class="text-sm text-slate-500 mt-1">Tiap titik diwarnai sesuai tingkat gangguan. Scroll untuk zoom, seret untuk menggeser.</p>
            </div>
            <button type="button" data-reset-zoom="latencyChart"
                    class="hidden shrink-0 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                Reset zoom
            </button>
        </div>
        <div class="mt-4"><canvas id="latencyChart" height="88"></canvas></div>
    </section>

    {{-- Throughput over time --}}
    <section class="card p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Throughput agregat dari waktu ke waktu</h2>
                <p class="text-sm text-slate-500 mt-1">Total trafik (bits sent + received) pada interface bridge-10.5.0.1. Scroll untuk zoom, seret untuk menggeser.</p>
            </div>
            <button type="button" data-reset-zoom="trafficChart"
                    class="hidden shrink-0 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                Reset zoom
            </button>
        </div>
        <div class="mt-4"><canvas id="trafficChart" height="88"></canvas></div>
    </section>

    {{-- Scatter + per-day --}}
    <section class="grid lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Sebaran klaster</h2>
                    <p class="text-sm text-slate-500 mt-1">Latency vs throughput. Scroll untuk zoom, klik legenda untuk sembunyikan.</p>
                </div>
                <button type="button" data-reset-zoom="scatterChart"
                        class="hidden shrink-0 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                    Reset zoom
                </button>
            </div>
            <div class="mt-4"><canvas id="scatterChart" height="112"></canvas></div>
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-900">Komposisi gangguan per hari</h2>
            <p class="text-sm text-slate-500 mt-1">Persentase tiap tingkat gangguan pada tiap hari observasi.</p>
            <div class="mt-4"><canvas id="perDayChart" height="112"></canvas></div>
        </div>
    </section>

    {{-- High-disturbance samples --}}
    <section class="card p-6">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            <h2 class="text-lg font-semibold text-slate-900">Sampel gangguan tinggi</h2>
            <span class="text-sm text-slate-500">({{ $highRows->count() }})</span>
        </div>
        <p class="text-sm text-slate-500 mt-1">Kejadian dengan packet loss / latency ekstrem yang perlu perhatian.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3 font-medium text-left">Waktu</th>
                        <th class="py-2 px-3 font-medium text-left">Hari</th>
                        <th class="py-2 px-3 font-medium text-right">Latency (ms)</th>
                        <th class="py-2 px-3 font-medium text-right">Packet loss (%)</th>
                        <th class="py-2 pl-3 font-medium text-right">Throughput (Mbps)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($highRows as $r)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 pr-3 tabular-nums whitespace-nowrap text-slate-700">{{ $r->measured_at->format('d M Y H:i') }}</td>
                            <td class="py-2.5 px-3 text-slate-700">{{ $r->hari }}</td>
                            <td class="py-2.5 px-3 text-right tabular-nums text-slate-700">{{ round($r->latency_ms, 3) }}</td>
                            <td class="py-2.5 px-3 text-right tabular-nums font-semibold text-rose-600">{{ round($r->packet_loss_percent, 2) }}</td>
                            <td class="py-2.5 pl-3 text-right tabular-nums text-slate-700">{{ round($r->total_traffic_mbps, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Full data table --}}
    <section class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900">Data lengkap</h2>
        <p class="text-sm text-slate-500 mt-1">{{ number_format($table->total()) }} sampel hasil klasterisasi.</p>
        {{-- #data-table is swapped in place on pagination (no full reload / no scroll jump). --}}
        <div id="data-table" aria-live="polite" class="transition-opacity duration-200">
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3 font-medium text-left">Waktu</th>
                        <th class="py-2 px-3 font-medium text-left">Hari</th>
                        <th class="py-2 px-3 font-medium text-right">Latency</th>
                        <th class="py-2 px-3 font-medium text-right">Packet loss</th>
                        <th class="py-2 px-3 font-medium text-right">Throughput</th>
                        <th class="py-2 pl-3 font-medium text-left">Klaster</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($table as $r)
                        @php $lv = $research['levels'][$r->tingkat_gangguan] ?? null; @endphp
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                            <td class="py-2 pr-3 tabular-nums whitespace-nowrap text-slate-700">{{ $r->measured_at->format('d M H:i') }}</td>
                            <td class="py-2 px-3 text-slate-700">{{ $r->hari }}</td>
                            <td class="py-2 px-3 text-right tabular-nums text-slate-700">{{ round($r->latency_ms, 3) }}</td>
                            <td class="py-2 px-3 text-right tabular-nums text-slate-700">{{ round($r->packet_loss_percent, 2) }}</td>
                            <td class="py-2 px-3 text-right tabular-nums text-slate-700">{{ round($r->total_traffic_mbps, 2) }}</td>
                            <td class="py-2 pl-3">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                                      style="background: {{ ($lv['color'] ?? '#64748b') }}1a; color: {{ $lv['color'] ?? '#475569' }}">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $lv['color'] ?? '#475569' }}"></span>
                                    {{ $r->tingkat_gangguan }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $table->links() }}</div>
        </div>{{-- /#data-table --}}
    </section>

    <footer class="text-sm text-slate-500 border-t border-slate-200 pt-6 pb-4">
        <p class="max-w-3xl leading-relaxed"><span class="font-medium text-slate-700">Catatan:</span> {{ $research['note'] }}</p>
    </footer>
</main>

@push('scripts')
    <script type="application/json" id="dashboard-data">@json($payload)</script>
@endpush
@endif
@endsection
