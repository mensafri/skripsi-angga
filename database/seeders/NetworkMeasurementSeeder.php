<?php

namespace Database\Seeders;

use App\Models\NetworkMeasurement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NetworkMeasurementSeeder extends Seeder
{
    /**
     * Import the final labelled dataset (Hasil_Klasterisasi_KMeans_590_Data.csv)
     * produced by the Google Colab K-Means analysis.
     */
    public function run(): void
    {
        $path = database_path('data/hasil_klasterisasi.csv');

        if (! is_readable($path)) {
            $this->command->error("CSV not found: {$path}");
            return;
        }

        NetworkMeasurement::query()->truncate();

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);          // skip / capture header row
        $rows   = [];
        $now    = now();
        $count  = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 12 || $data[0] === '') {
                continue;
            }

            $rows[] = [
                'hari'                => $data[0],
                'measured_at'         => Carbon::parse($data[1]),
                'latency_ms'          => (float) $data[2],
                'packet_loss_percent' => (float) $data[3],
                'bits_sent_mbps'      => (float) $data[4],
                'bits_received_mbps'  => (float) $data[5],
                'total_traffic_mbps'  => (float) $data[6],
                'latency_z'           => (float) $data[7],
                'packet_loss_z'       => (float) $data[8],
                'total_traffic_z'     => (float) $data[9],
                'cluster_raw'         => (int) $data[10],
                'tingkat_gangguan'    => $data[11],
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
            $count++;

            if (count($rows) === 200) {
                NetworkMeasurement::insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            NetworkMeasurement::insert($rows);
        }

        fclose($handle);

        $this->command->info("Imported {$count} network measurements.");
    }
}
