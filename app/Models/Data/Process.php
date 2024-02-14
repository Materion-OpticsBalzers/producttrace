<?php

namespace App\Models\Data;

use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Process extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected $casts = [
        'box' => 'string',
        'wafer_id' => 'string',
        'serial_id' => 'string'
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function block() {
        return $this->belongsTo(Block::class);
    }

    public function wafer() {
        return $this->belongsTo(Wafer::class);
    }

    public function rejection() {
        return $this->belongsTo(Rejection::class);
    }

    public function data() {
        return $this->hasMany(ProcessData::class);
    }

    public function searchableAs() {
        return 'id';
    }
}
