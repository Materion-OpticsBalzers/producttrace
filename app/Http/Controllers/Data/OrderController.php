<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;

class OrderController extends Controller
{
    public function show(Order $order) {
        if($order->mapping->init_block != '') {
            $init_block = Block::find($order->mapping->init_block);

            return redirect()->route('blocks.show', ['order' => $order->id, 'block' => $init_block->identifier]);
        }

        return view('content.data.orders.show', ['order' => $order->id]);
    }

    public function create() {
        $mappings = Mapping::with('product')->get();

        return view('content.data.orders.create', compact('mappings'));
    }

    public function store() {
        $data = \request()->validate([
           'id' => 'required|string|max:20|unique:orders',
           'mapping_id' => 'required'
        ]);

        Order::create($data);

        session()->flash('success');

        return back();
    }

    public function import() {


        return "import done!";
    }
}
