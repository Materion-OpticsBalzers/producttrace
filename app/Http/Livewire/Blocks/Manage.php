<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Link;
use App\Models\Data\Order;
use App\Models\Data\Process;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use App\Models\Generic\Product;
use Livewire\Component;

class Manage extends Component
{
    public $blockId;
    public $orderId;

    public function changeProduct($mappingId) {
        Order::find($this->orderId)->update([
            'mapping_id' => $mappingId
        ]);

        return redirect(request()->header('Referer'));
    }

    public function removeLinkedOrder($orderId) {
        $orderTrace = Link::where('orders', 'like', "%$orderId%")->first();

        $newOrders = array_diff($orderTrace->value('orders'), [$orderId]);

        $orderTrace->update([
            'orders' => $newOrders
        ]);

        return redirect(request()->header('Referer'));
    }

    public function addLinkedOrder($afterOrder, $orderId) {
        if(Order::find($orderId) == null) {
            $this->addError('order', 'Auftrag wurde nicht gefunden!');
            return false;
        }

        $orderTrace = Link::where('orders', 'like', "%$afterOrder%")->first();

        if($afterOrder == '') {
            $newOrders = collect($orderTrace->value('orders'))->prepend($orderId)->toArray();
        } else {
            $newOrders = $this->array_insert_after($orderTrace->value('orders'), $afterOrder, [$orderId]);
        }

        $orderTrace->update([
            'orders' => $newOrders
        ]);

        return redirect(request()->header('Referer'));
    }

    public function removeLink() {
        Link::where('orders', 'like', "%$this->orderId%")->first()->delete();

        return redirect(request()->header('Referer'));
    }

    public function addLink() {
        Link::create([
            'orders' => [(string) $this->orderId]
        ]);

        return redirect(request()->header('Referer'));
    }

    public function removeAllData() {
        Process::where('order_id', $this->orderId)->delete();

        session()->flash('success');
    }

    public function array_insert_after($array, $key, $new) {
        $index = array_search( $key, $array );
        $pos = false === $index ? count( $array ) : $index + 1;

        return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
    }

    public function render()
    {
        $block = Block::find($this->blockId);
        $orderInfo = Order::find($this->orderId);

        $orderTrace = Link::where('orders', 'like', "%$this->orderId%")->first();

        $orders = array();
        if($orderTrace != null) {
            foreach($orderTrace->orders as $o) {
                $f = Order::find($o);

                if($f != null)
                    $orders[] = Order::find($o);
            }
        }

        $products = Mapping::all();

        return view('livewire.blocks.manage', compact('block', 'orders', 'orderInfo', 'products'));
    }
}
