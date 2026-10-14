<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineDevice extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'user_id', 'branch_id', 'authorized', 'last_sequence', 'last_seen_at', 'offline_expires_at', 'client_kind', 'revoked_at'];
    protected $casts = ['authorized' => 'boolean', 'last_seen_at' => 'datetime', 'offline_expires_at' => 'datetime', 'revoked_at' => 'datetime'];
}
