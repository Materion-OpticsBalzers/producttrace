<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Link;
use App\Models\Data\Order;
use App\Models\Data\Process;
use App\Models\Generic\Block;
use App\Models\Generic\Product;
use Livewire\Component;

class Manage extends Component
{
    public $blockId;
    public $orderId;

    public function render()
    {
        $block = Block::find($this->blockId);
        $orderInfo = Order::find($this->orderId);

        $orderTrace = Link::where('orders', 'like', "%$this->orderId%")->first();

        $orders = array();
        if($orderTrace != null) {
            foreach($orderTrace->orders as $o) {
                $orders[] = Order::find($o);
            }
        }

        $products = Product::all();

        return view('livewire.blocks.manage', compact('block', 'orders', 'orderInfo', 'products'));
    }
}
