<?php

namespace App\Http\Livewire\Backend;

use App\Models\Data\Order;
use App\Models\Data\Serial;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CoaShow extends Component
{
    public $order;

    public function mount(Order $order)
    {
        $this->order = $order;
    }

    public function render()
    {
        $serials = Serial::where('order_id', $this->order->id)->whereNotNull('wafer_id')
        ->with(['wafer','order', 'wafer.order', 'wafer.processes' => function($query) {
            $query->whereIn('block_id', [2, 4, 8]);
        }])
        ->whereHas('wafer', function($query) {
            $query->where('rejected', false);
        })->orderBy('id')->get();

        $chrom_lots = [];
        foreach($serials as $serial) {
            $chrom_info = $serial->wafer->processes->first() ?? null;
            if($chrom_info && !in_array($chrom_info->lot, $chrom_lots))
                $chrom_lots[] = $chrom_info->lot;
        }

        if(sizeof($chrom_lots) > 0) {
            foreach($chrom_lots as $lot) {
                DB::connection('sqlsrv_caq')->select("");
            }
        }

        return view('livewire.backend.coa-show', compact('serials'));
    }
}
