<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'encrypted_payload',
        'iv',
        'meta',
        'expires_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
