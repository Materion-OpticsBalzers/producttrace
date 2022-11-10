<?php

namespace App\Http\Livewire\Backend;

use App\Models\Data\Order;
use Livewire\Component;

class CoaList extends Component
{
    public $search = '';

    public function render()
    {
        if($this->search == '')
            $orders = Order::orderBy('created_at', 'desc')->where('mapping_id', 4)->with('mapping')->lazy();
        else
            $orders = Order::orderBy('created_at', 'desc')->where('mapping_id', 4)->where('id', 'like', "%$this->search%")->with('mapping')->lazy();

        return view('livewire.backend.coa-list', compact('orders'));
    }
}
