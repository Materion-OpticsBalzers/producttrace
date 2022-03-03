<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessData extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function process() {
        return $this->belongsTo(Process::class);
    }
}
