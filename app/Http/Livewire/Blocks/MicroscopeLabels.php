<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Generic\Block;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Response;
use Knp\Snappy\Pdf;
use Livewire\Component;

class MicroscopeLabels extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public function print() {
        $snappy = \App::make('snappy.pdf');
        /*$pdf = SnappyPdf::loadView('content.print.microscope-labels')->setPaper('a4')
        ->setOptions([
            'margin-top' => 0,
            'margin-bottom' => 0,
            'margin-right' => 0,
            'margin-left' => 0,
            'dpi' => 96
        ]);*/
        $snappy->generateFromHtml(view('content.print.microscope-labels')->render(), "/tmp/{$this->orderId}-" . rand() . ".pdf");
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        return view('livewire.blocks.microscope-labels', compact('block'));
    }
}
