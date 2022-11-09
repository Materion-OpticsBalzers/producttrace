<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wafer extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'string'
    ];
    protected $keyType = 'string';
    protected $guarded = [];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function processes() {
        return $this->hasMany(Process::class);
    }
}
