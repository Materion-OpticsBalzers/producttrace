<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function show(Order $order, $blockSlug) {
        abort_if(empty($order->mapping->blocks), 404);

        $block = Block::where('identifier', $blockSlug)->firstOrFail();

        abort_if(!in_array($block->id, $order->mapping->blocks), 404);

        return view('content.generic.blocks.show', ['order' => $order->id, 'block' => $block]);
    }
}
