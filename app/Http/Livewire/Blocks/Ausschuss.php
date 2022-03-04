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

        $wafers = $wafers->sortBy('block.avo');

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        return view('livewire.blocks.ausschuss', compact('block', 'wafers'));
    }
}
