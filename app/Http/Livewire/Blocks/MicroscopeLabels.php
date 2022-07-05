<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Generic\Block;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class MicroscopeLabels extends Component
{

    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public function print() {
        $wafers = Process::where('order_id', $this->orderId)->with('wafer')->limit(10)->get();
        $wafers = [];

        if(!empty($wafers)) {
            $pdf = Pdf::loadView('content.print.microscope-labels', compact('wafers'));
            $pdf->save("/tmp/{$this->orderId}-" . rand() . ".pdf");
        } else {
            $this->addError('print', "Es wurden keine Daten ausgeählt!");
        }
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        return view('livewire.blocks.microscope-labels', compact('block'));
    }
}
