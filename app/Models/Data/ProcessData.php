<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessData extends Model
{
    use HasFactory;

    public function process() {
        return $this->belongsTo(Process::class);
    }
}
