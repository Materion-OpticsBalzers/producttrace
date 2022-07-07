<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Order;
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

    public $selectedWafers = [];

    public function getSelectedWafers() {
        $order = Order::find($this->orderId);

        $selectedWs = collect([]);
        foreach($this->selectedWafers as $selectedWafer) {
            $wafersForBox = Process::where('ar_box', $selectedWafer)->where('block_id', 6)->with(['wafer', 'order'])->lazy();

            $wafer = (object) [];
            $wafer->ar_box = $selectedWafer;
            $wafer->date = $order->created_at;
            $wafer->article = $order->article;
            $wafer->format = $order->article_desc;
            $wafer->orders = collect();
            $wafer->count = 0;
            $wafer->lots = collect();
            foreach($wafersForBox as $waferForBox) {
                $wafer->lots = collect(array_merge($wafer->lots->unique()->toArray(), Process::select('lot')->where('order_id', $waferForBox->order_id)->where('block_id', 2)->groupBy('lot')->pluck('lot')->toArray()));
                $wafer->orders = collect(array_merge($wafer->orders->unique()->toArray(), [$waferForBox->order_id]));
                $wafer->count += 1;
            }

            $wafer->lots = $wafer->lots->unique();
            $wafer->orders = $wafer->orders->unique();

            $selectedWs->add($wafer);
        }

        return $selectedWs;
    }

    public function print() {
        $wafers = $this->getSelectedWafers();

        if(!empty($wafers)) {
            $pdf = Pdf::loadView('content.print.microscope-labels', compact('wafers'));
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
        $wafers = Process::select('ar_box')->where('order_id', $this->orderId)->where('block_id', 6)->whereNotNull('ar_box')->groupBy('ar_box')->get();
        $order = Order::find($this->orderId);

        $selectedWs = $this->getSelectedWafers();

        return view('livewire.blocks.microscope-labels', compact('block', 'wafers', 'selectedWs', 'order'));
    }
}
