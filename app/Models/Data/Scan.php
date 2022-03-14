<?php

namespace App\Models\Data;

use App\Models\Generic\Block;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function block() {
        return $this->belongsTo(Block::class);
    }
}
