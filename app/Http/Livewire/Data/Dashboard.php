<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use App\Models\Data\Serial;
use App\Models\Data\Wafer;
use Livewire\Component;

class Dashboard extends Component
{
    public $search = '';
    public $searchType = 'wafer';
    public $mode = 1;

    public function render()
    {
        $orders = Order::with('mapping.product')->orderBy('created_at', 'desc')->limit(50)->get();
        $wafers = Wafer::orderBy('created_at', 'desc')->limit(50)->get();

        $foundOrders = [];
        if($this->mode == 1) {
            if($this->search != '') {
                $foundOrders = Order::where('id', 'like', "%{$this->search}%")->with('mapping.product')->limit(50)->lazy();
                if($foundOrders->count() == 1) {
                    if($foundOrders->first()->id == $this->search)
                        $this->redirect(route('orders.show', ['order' => $this->search]));
                }
            }
        }

        $foundWafers = [];
        if($this->mode == 2) {
            if ($this->search != '') {
                if($this->searchType == 'wafer') {
                    $foundWafers = Wafer::where('id', 'like', "%{$this->search}%")->limit(50)->lazy();
                    if ($foundWafers->count() == 1) {
                        if ($foundWafers->first()->id == $this->search)
                            $this->redirect(route('wafer.show', ['wafer' => $this->search]));
                    }
                }

                if($this->searchType == 'serial') {
                    $foundWafers = Serial::where('id', 'like', "%{$this->search}%")->whereNotNull('wafer_id')->with('wafer')->limit(50)->lazy();
                    if ($foundWafers->count() == 1) {
                        if ($foundWafers->first()->id == $this->search)
                            $this->redirect(route('wafer.show', ['wafer' => $foundWafers->first()->wafer_id]));
                    }
                }

                if($this->searchType == 'box') {
                    $foundWafers = Wafer::whereHas('processes', function($query) {
                        $query->where('box', $this->search);
                    })->limit(50)->lazy();
                }

                if($this->searchType == 'chrome') {
                    $foundWafers = Wafer::whereHas('processes', function($query) {
                        $query->where('lot', $this->search)->where('block_id', 2);
                    })->limit(50)->lazy();
                }

                if($this->searchType == 'ar') {
                    $foundWafers = Wafer::whereHas('processes', function($query) {
                        $query->where('lot', $this->search)->where('block_id', 8);
                    })->limit(50)->lazy();
                }
            }
        }

        return view('livewire.data.dashboard', compact('orders', 'foundOrders', 'wafers', 'foundWafers'));
    }
}
