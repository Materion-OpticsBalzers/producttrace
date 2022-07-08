<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Order;
use App\Models\Data\Process;
use App\Models\Data\Serial;
use App\Models\Generic\Block;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class QualityControlLabels extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $selectedWafers = [];
    public $startPos = 0;

    public function getSelectedWafers() {
        $order = Order::find($this->orderId);

        $selectedWs = collect([])->pad(10, null);

        if($this->startPos + sizeof($this->selectedWafers) > 10) {
            $this->addError('print', 'Etikettenlimit für diese Seite überschritten!');
            return $selectedWs;
        }

        $lot = Process::select('lot')->where('order_id', $this->orderId)->where('block_id', 8)->groupBy('lot')->first()->lot;
        for($i = 0; $i < sizeof($this->selectedWafers); $i++) {
            $serials = Serial::where('order_id', $this->orderId)->with('wafer')->get();

            $wafer = (object) [];
            $wafer->date = $order->created_at;
            $wafer->article = $order->article;
            $wafer->format = $order->article_desc;
            $wafer->ar_lot = $lot;
            $wafer->article_cust = $order->article_cust;
            $wafer->serials = $i == 0 ? $serials->filter(function($value, $key) {
                return $key < 14;
            }) : $serials->filter(function($value, $key) {
               return $key >= 14;
            });
            $wafer->count = 0;
            $wafer->missingSerials = $wafer->serials->filter(function($value, $key) {
                return $value->wafer->rejected ?? false;
            });

            $selectedWs->put($i + $this->startPos, $wafer);
        }

        return $selectedWs;
    }

    public function clearTemp() {
        foreach(glob('tmp/*.*') as $v){
            unlink($v);
        }
    }

    public function print() {
        $wafers = $this->getSelectedWafers();

        if(!empty($wafers)) {
            $startPos = $this->startPos;
            $pdf = Pdf::loadView('content.print.microscope-labels', compact('wafers', 'startPos'));
            $filename = "tmp/{$this->orderId}-" . rand() . ".pdf";
            $pdf->save($filename);
            $this->dispatchBrowserEvent('printPdf', asset($filename));
        } else {
            $this->addError('print', "Es wurden keine Daten ausgeählt!");
        }
    }

    public function render()
    {
        $block = Block::find($this->blockId);
        $wafers = Process::where('block_id', 9)->with('wafer')->get();
        $order = Order::find($this->orderId);

        $blocks = ($wafers->count() / 14) <= 1 ? 1 : 2;

        $selectedWs = collect([]);
        if(!empty($this->selectedWafers))
            $selectedWs = $this->getSelectedWafers();

        return view('livewire.blocks.quality-control-labels', compact('block', 'blocks', 'selectedWs', 'order'));
    }
}
