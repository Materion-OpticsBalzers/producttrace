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

        $blocks = array_filter($order->mapping->blocks, function($value) use ($block) {
            return $value->id == $block->id;
        });

        if(sizeof($blocks) == 0)
            abort(404);

        $block->info = reset($blocks);

        if($block->admin_only && !auth()->user()->is_admin)
            abort(403);

        return view('content.generic.blocks.show', ['order' => $order->id, 'block' => $block]);
    }
}
