<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use Livewire\Component;

class Serialize extends Component
{
    public $search = '';
    public $showSet = false;

    public function setOrder($orders, $po, $pos) {
        if($po == '') {
            $this->addError('po', 'Auftrag darf nicht leer sein!');
            return false;
        }

        if($pos == '') {
            $this->addError('pos', 'Position darf nicht leer sein!');
            return false;
        }

        if(empty($orders)) {
            $this->addError('po', 'Es muss mindestens ein auftrag ausgewählt werden!');
            return false;
        }

        foreach(Order::find($orders)->lazy() as $order) {
            $order->update([
                'po' => $po,
                'po_pos' => $pos
            ]);
            $pos += 10;
        }

        session()->flash('success');
    }

    public function unlink($order) {
        Order::find($order)->update([
           'po' => null,
           'po_pos' => null
        ]);
    }

    public function render()
    {
        $orders = Order::orderBy('created_at', 'desc')->where('mapping_id', 4)->with('serials')->lazy();

        if(!$this->showSet)
            $orders = $orders->whereNull('po');

        if($this->search != '') {
            $orders = $orders->filter(function($value) {
               return stristr($value, $this->search);
            });
        }

        return view('livewire.data.serialize', compact('orders'));
    }
}
