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
        $blocks = Block::find($order->mapping->blocks)->sortBy('avo');

        return view('livewire.data.order-panel', compact('order', 'blocks'));
    }
}
