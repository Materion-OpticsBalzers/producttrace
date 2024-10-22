<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialList extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $guarded = [];
}
