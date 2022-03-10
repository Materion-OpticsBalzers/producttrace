<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use Livewire\Component;

class Serialize extends Component
{
    public $blockId;
    public $orderId;

    public $search = '';

    public function clear($order, $block) {
        $wafers = Process::where('order_id', $order)->where('block_id', $block)->with('wafer');

        foreach ($wafers->lazy() as $wafer) {
            if($wafer->rejection != null) {
                if ($wafer->wafer->rejected && $wafer->rejection->reject) {
                    Wafer::find($wafer->wafer_id)->update([
                        'rejected' => false,
                        'rejection_reason' => null,
                        'rejection_position' => null,
                        'rejection_avo' => null,
                        'rejection_order' => null
                    ]);
                }
            }
        }

        $wafers->delete();
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->with('rejection')->orderBy('wafer_id')->lazy();

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        return view('livewire.blocks.serialize', compact('block', 'wafers'));
    }
}
