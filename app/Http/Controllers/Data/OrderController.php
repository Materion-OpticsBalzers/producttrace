<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Order $order) {
        if($order->mapping->init_block != '') {
            $init_block = Block::find($order->mapping->init_block);

            return redirect()->route('blocks.show', ['order' => $order->id, 'block' => $init_block->identifier]);
        }

        return view('content.data.orders.show', ['order' => $order->id]);
    }
}
