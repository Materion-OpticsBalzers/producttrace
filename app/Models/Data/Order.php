<?php

namespace App\Models\Data;

use App\Models\Generic\Mapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $keyType = 'string';

    public function mapping() {
        return $this->belongsTo(Mapping::class);
    }

    public function processes() {
        return $this->hasMany(Process::class);
    }

    public function scans() {
        return $this->hasMany(Scan::class);
    }

    public function serials() {
        return $this->hasMany(Serial::class);
    }
}
