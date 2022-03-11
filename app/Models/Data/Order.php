<?php

namespace App\Models\Data;

use App\Models\Generic\Mapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function mapping() {
        return $this->belongsTo(Mapping::class);
    }

    public function processes() {
        return $this->hasMany(Process::class);
    }
}
