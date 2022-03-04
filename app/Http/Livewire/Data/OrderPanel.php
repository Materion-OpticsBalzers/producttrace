<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use App\Models\Generic\Block;
use Livewire\Component;

class OrderPanel extends Component
{
    public $orderId;
    public $blockId;

    public function render()
    {
        $order = Order::find($this->orderId);

        if($order == null)
            abort(404);

        $blocks = Block::find(json_decode($order->mapping->blocks));

        if(!empty($blocks))
            $blocks = $blocks->sortBy('avo');

        return view('livewire.data.order-panel', compact('order', 'blocks'));
    }
}
