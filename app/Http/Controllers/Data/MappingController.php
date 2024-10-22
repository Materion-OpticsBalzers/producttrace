<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use App\Models\Generic\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MappingController extends Controller
{
    public function index() {
        $mappings = Mapping::with('product')->lazy();

        return view('content.data.mappings.index', compact('mappings'));
    }

    public function store() {
        $data = \request()->validate([
            'name' => 'required|unique:products'
        ]);

        $product = Product::create([
            'name' => $data["name"],
            'identifier' => Str::slug($data["name"])
        ]);

        Mapping::create([
            'product_id' => $product->id,
            'blocks' => []
        ]);

        return back();
    }

    public function destroy(Mapping $mapping) {
        $mapping->product()->delete();

        return back();
    }
}
