<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Generic\Block;
use Livewire\Component;

class Ausschuss extends Component
{
    public $blockId;
    public $orderId;

    public $search = '';

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->with('rejection')->with('block')->whereHas('rejection', function($query) {
            return $query->where('reject', true);
        })->lazy();

        $waferCount = count(Process::where('order_id', $this->orderId)->select('wafer_id')->groupBy('wafer_id')->get());

        $wafers = $wafers->sortBy('block.avo');

        if($wafers->count() > 0)
            $calculatedRejections = ($wafers->count() / $waferCount) * 100;
        else
            $calculatedRejections = 0;

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        return view('livewire.blocks.ausschuss', compact('block', 'wafers', 'waferCount', 'calculatedRejections'));
    }
}
