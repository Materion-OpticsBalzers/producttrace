<?php

namespace App\Models\Generic;

use App\Models\Data\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    use HasFactory;

    protected $casts = [
        'blocks' => 'object'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}
