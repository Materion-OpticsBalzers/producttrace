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

    protected $guarded = [];

    public function processes() {
        return $this->hasMany(Process::class);
    }
}
