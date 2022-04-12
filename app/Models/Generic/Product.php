<?php

namespace App\Models\Generic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function mappings() {
        return $this->hasMany(Mapping::class);
    }
}
