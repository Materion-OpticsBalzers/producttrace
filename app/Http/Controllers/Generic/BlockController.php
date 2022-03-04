<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function show(Order $order, $blockSlug) {
        if(json_decode($order->mapping->blocks) == null)
            abort(404);

        $block = Block::where('identifier', $blockSlug)->first();

        if($block == null)
            abort(404);

        if(!in_array($block->id, json_decode($order->mapping->blocks)))
            abort(404);

        return view('content.generic.blocks.show', ['order' => $order->id, 'block' => $block]);
    }
}
