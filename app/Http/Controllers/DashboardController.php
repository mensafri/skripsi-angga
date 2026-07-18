<?php

namespace App\Http\Controllers;

use App\Models\NetworkMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $research = config('research');
        $levels   = $research['levels'];
        $days     = $research['days'];

        $all = NetworkMeasurement::query()->orderBy('measured_at')->get();

        // If the database has not been seeded yet, show an empty-state prompt.
        if ($all->isEmpty()) {
            return view('dashboard', [
                'seeded'   => false,
                'research' => $research,
            ]);
        }

        return view('dashboard', [
            'seeded'       => true,
            'research'     => $research,
            'stats'        => $this->stats($all),
            'distribution' => $this->distribution($all, $levels),
            'centroids'    => $this->liveCentroids($all, $levels),
            'perDay'       => $this->perDay($all, $days, $levels),
            'timeseries'   => $this->timeseries($all),
            'scatter'      => $this->scatter($all, $levels),
            'highRows'     => $all->where('tingkat_gangguan', 'Tinggi')->values(),
            'table'        => NetworkMeasurement::query()
                ->orderBy('measured_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /** Headline numbers computed live from the imported dataset. */
    private function stats(Collection $all): array
    {
        return [
            'total'          => $all->count(),
            'days'           => $all->pluck('hari')->unique()->count(),
            'avg_latency'    => round($all->avg('latency_ms'), 3),
            'max_latency'    => round($all->max('latency_ms'), 3),
            'avg_packetloss' => round($all->avg('packet_loss_percent'), 2),
            'max_packetloss' => round($all->max('packet_loss_percent'), 2),
            'avg_traffic'    => round($all->avg('total_traffic_mbps'), 2),
            'max_traffic'    => round($all->max('total_traffic_mbps'), 2),
            'high_count'     => $all->where('tingkat_gangguan', 'Tinggi')->count(),
            'first_at'       => $all->first()->measured_at,
            'last_at'        => $all->last()->measured_at,
        ];
    }

    /** Cluster distribution (count + percent) computed live. */
    private function distribution(Collection $all, array $levels): array
    {
        $total = $all->count();
        $out   = [];

        foreach ($levels as $key => $meta) {
            $count = $all->where('tingkat_gangguan', $key)->count();
            $out[] = [
                'key'     => $key,
                'label'   => $meta['label'],
                'color'   => $meta['color'],
                'count'   => $count,
                'percent' => $total ? round($count / $total * 100, 2) : 0,
            ];
        }

        return $out;
    }

    /** Centroids re-computed live as the mean of each cluster's raw metrics. */
    private function liveCentroids(Collection $all, array $levels): array
    {
        $out = [];

        foreach ($levels as $key => $meta) {
            $group = $all->where('tingkat_gangguan', $key);
            $out[$key] = [
                'label'               => $meta['label'],
                'color'               => $meta['color'],
                'latency_ms'          => $group->count() ? round($group->avg('latency_ms'), 3) : 0,
                'packet_loss_percent' => $group->count() ? round($group->avg('packet_loss_percent'), 2) : 0,
                'total_traffic_mbps'  => $group->count() ? round($group->avg('total_traffic_mbps'), 3) : 0,
            ];
        }

        return $out;
    }

    /** Per-day breakdown of disturbance levels (counts + percentages). */
    private function perDay(Collection $all, array $days, array $levels): array
    {
        $out = [];

        foreach ($days as $day) {
            $group = $all->where('hari', $day);
            $total = $group->count();
            $row   = ['hari' => $day, 'total' => $total];

            foreach (array_keys($levels) as $key) {
                $count = $group->where('tingkat_gangguan', $key)->count();
                $row[$key] = [
                    'count'   => $count,
                    'percent' => $total ? round($count / $total * 100, 2) : 0,
                ];
            }

            $out[] = $row;
        }

        return $out;
    }

    /** Ordered time series for latency / throughput / packet loss line charts. */
    private function timeseries(Collection $all): array
    {
        return [
            'labels'      => $all->map(fn ($m) => $m->measured_at->format('D H:i'))->values(),
            'latency'     => $all->map(fn ($m) => round($m->latency_ms, 3))->values(),
            'traffic'     => $all->map(fn ($m) => round($m->total_traffic_mbps, 2))->values(),
            'packet_loss' => $all->map(fn ($m) => round($m->packet_loss_percent, 2))->values(),
            'levels'      => $all->pluck('tingkat_gangguan')->values(),
        ];
    }

    /** Scatter points (latency vs throughput) grouped per cluster. */
    private function scatter(Collection $all, array $levels): array
    {
        $out = [];

        foreach ($levels as $key => $meta) {
            $points = $all->where('tingkat_gangguan', $key)->map(fn ($m) => [
                'x' => round($m->latency_ms, 3),
                'y' => round($m->total_traffic_mbps, 2),
            ])->values();

            $out[] = [
                'label'  => $meta['label'],
                'color'  => $meta['color'],
                'points' => $points,
            ];
        }

        return $out;
    }
}
