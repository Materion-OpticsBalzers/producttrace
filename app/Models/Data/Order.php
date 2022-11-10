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

    public function missingSerials() {
        return $this->serials()->whereHas('wafer', function($query) {
            return $query->where('rejected', true);
        })->orWhereNull('wafer_id')->where('order_id', $this->id)->with('wafer')->get();
    }
}
