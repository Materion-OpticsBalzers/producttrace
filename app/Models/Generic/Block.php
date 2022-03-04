<?php

namespace App\Models\Generic;

use App\Models\Data\Process;
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
}
