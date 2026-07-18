<?php

// Documented facts of the thesis (skripsi) K-Means study.
// Source: "Ringkasan_Hasil_Penelitian.txt" produced by the Google Colab analysis.
// These are the reported / authoritative values displayed on the dashboard next
// to figures re-computed live from the imported dataset.

return [
    'title'    => 'Klasterisasi Tingkat Gangguan Jaringan dengan K-Means',
    'subtitle' => 'Analisis latency, packet loss, dan throughput agregat pada interface bridge-10.5.0.1',

    // Method parameters (exactly as reported in the study).
    'method' => [
        'algorithm'      => 'K-Means',
        'dataset_size'   => 590,
        'features'       => ['latency_ms', 'packet_loss_percent', 'total_traffic_mbps'],
        'scaler'         => 'StandardScaler (Z-score)',
        'clusters'       => 3,
        'random_state'   => 42,
        'n_init'         => 10,
        'iterations'     => 10,
        'wcss'           => 752.3804,
    ],

    // Reported centroids per disturbance level (original scale).
    'centroids' => [
        'Rendah' => ['latency_ms' => 1.216, 'packet_loss_percent' => 0.0,  'total_traffic_mbps' => 10.496],
        'Sedang' => ['latency_ms' => 2.410, 'packet_loss_percent' => 0.0,  'total_traffic_mbps' => 38.477],
        'Tinggi' => ['latency_ms' => 2.615, 'packet_loss_percent' => 11.1, 'total_traffic_mbps' => 12.343],
    ],

    // Reported cluster distribution.
    'distribution' => [
        'Rendah' => ['count' => 523, 'percent' => 88.64],
        'Sedang' => ['count' => 58,  'percent' => 9.83],
        'Tinggi' => ['count' => 9,   'percent' => 1.53],
    ],

    // Display metadata for each disturbance level.
    'levels' => [
        'Rendah' => ['label' => 'Gangguan Rendah', 'color' => '#22c55e', 'order' => 1],
        'Sedang' => ['label' => 'Gangguan Sedang', 'color' => '#f59e0b', 'order' => 2],
        'Tinggi' => ['label' => 'Gangguan Tinggi', 'color' => '#ef4444', 'order' => 3],
    ],

    'note' => 'Throughput agregat merupakan hasil penjumlahan Bits sent dan Bits '
        . 'received pada interface bridge-10.5.0.1. Nilai tersebut menunjukkan trafik '
        . 'aktual dan bukan kapasitas maksimum jaringan.',

    'days' => ['Senin', 'Selasa', 'Rabu', 'Kamis'],
];
