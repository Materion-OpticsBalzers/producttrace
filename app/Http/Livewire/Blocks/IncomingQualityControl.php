<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use Livewire\Component;

class IncomingQualityControl extends Component
{
    public $blockId;

    public function checkWafer($wafer) {
        if($wafer == '') {
            $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
            return false;
        }

        $wafer = Wafer::find($wafer);

        if($wafer == null) {
            $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
            return false;
        }

        if($wafer->reworks == 2) {
            $this->addError('wafer', 'Dieser Wafer darf nicht mehr verwendet werden.');
            return false;
        }

        session()->flash('success', 'Wafernummer in Ordnung');
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        return view('livewire.blocks.incoming-quality-control', compact('block'));
    }
}
