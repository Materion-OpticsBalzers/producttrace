<?php

namespace App\Models\Generic;

use App\Models\Data\Process;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rejection extends Model
{
    use HasFactory;

    public function process() {
        return $this->belongsTo(Process::class);
    }
}
