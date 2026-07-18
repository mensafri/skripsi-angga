<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkMeasurement extends Model
{
    protected $fillable = [
        'hari',
        'measured_at',
        'latency_ms',
        'packet_loss_percent',
        'bits_sent_mbps',
        'bits_received_mbps',
        'total_traffic_mbps',
        'latency_z',
        'packet_loss_z',
        'total_traffic_z',
        'cluster_raw',
        'tingkat_gangguan',
    ];

    protected $casts = [
        'measured_at'          => 'datetime',
        'latency_ms'           => 'float',
        'packet_loss_percent'  => 'float',
        'bits_sent_mbps'       => 'float',
        'bits_received_mbps'   => 'float',
        'total_traffic_mbps'   => 'float',
        'latency_z'            => 'float',
        'packet_loss_z'        => 'float',
        'total_traffic_z'      => 'float',
        'cluster_raw'          => 'integer',
    ];
}
