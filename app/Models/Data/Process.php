<?php

namespace App\Models\Data;

use App\Models\Generic\Block;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function block() {
        return $this->belongsTo(Block::class);
    }

    public function wafer() {
        return $this->belongsTo(Wafer::class);
    }

    public function data() {
        return $this->hasMany(ProcessData::class);
    }
}
