<?php

namespace App\Models\Generic;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $casts = [
        'rejections' => 'array'
    ];

    public function processes() {
        return $this->hasMany(Process::class);
    }

    public function scans() {
        return $this->hasMany(Scan::class);
    }
}
