<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use Illuminate\Http\Request;

class MappingController extends Controller
{
    public function index() {
        $mappings = Mapping::with('product')->lazy();

        return view('content.data.mappings.index', compact('mappings'));
    }

    public function show(Mapping $mapping) {
        return view('content.data.mappings.show', compact('mapping'));
    }
}
