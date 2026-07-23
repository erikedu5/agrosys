<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineOperation extends Model
{
    protected $primaryKey = 'operation_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['operation_id', 'device_id', 'branch_id', 'user_id', 'aggregate_type', 'aggregate_id', 'event_type', 'sequence', 'status', 'payload', 'result', 'error_code', 'error_message', 'occurred_at'];
    protected $casts = ['payload' => 'array', 'result' => 'array', 'occurred_at' => 'datetime'];
}
