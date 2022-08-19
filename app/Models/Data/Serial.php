<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serial extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'string',
    ];

    protected $guarded = [];

    public function wafer() {
        return $this->belongsTo(Wafer::class);
    }
}
