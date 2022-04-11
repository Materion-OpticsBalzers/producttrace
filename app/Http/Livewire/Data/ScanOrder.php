<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use Livewire\Component;

class ScanOrder extends Component
{
    public function scanOrder($orderNum) {
        if($orderNum == '') {
            $this->addError('order', 'Das Feld darf nicht leer sein!');
            return false;
        }

        if(Order::find($orderNum) != null) {
            $this->redirect(route('orders.show', ['order' => $orderNum]));
        } else {
            $this->addError('order', 'Auftrag wurde nicht gefunden!');
        }
    }

    public function render()
    {
        $orders = Order::with('mapping.product')->orderBy('created_at', 'desc')->limit(20)->get();

        return view('livewire.data.scan-order', compact('orders'));
    }
}
