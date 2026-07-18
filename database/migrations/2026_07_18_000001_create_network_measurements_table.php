<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row is one 3-minute network sample from the final 590-row dataset,
     * already labelled by the K-Means model (tingkat_gangguan).
     */
    public function up(): void
    {
        Schema::create('network_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('hari', 16)->index();          // day name: Senin..Kamis
            $table->dateTime('measured_at')->index();      // sample timestamp

            // Raw metrics (original scale).
            $table->double('latency_ms');
            $table->double('packet_loss_percent');
            $table->double('bits_sent_mbps');
            $table->double('bits_received_mbps');
            $table->double('total_traffic_mbps');

            // Standardized (Z-score) values fed to K-Means.
            $table->double('latency_z');
            $table->double('packet_loss_z');
            $table->double('total_traffic_z');

            // K-Means output.
            $table->unsignedTinyInteger('cluster_raw');            // raw cluster id (0/1/2)
            $table->string('tingkat_gangguan', 16)->index();       // Rendah / Sedang / Tinggi

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_measurements');
    }
};
