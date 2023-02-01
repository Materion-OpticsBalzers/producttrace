<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use App\Models\Data\Serial;
use App\Models\Data\Wafer;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class SerialList extends Component
{
    public $po;

    public function mount(\App\Models\Data\SerialList $po) {
        $this->po = $po;
    }

    public function printOrders($orders) {
        $orders = collect($orders);

        foreach($orders as $orderId) {
            $order = Order::find($orderId);
            $selectedWs = collect([])->pad(10, null);
            $wafers = Serial::where('order_id', $orderId)->with('wafer')->get();
            $blocks = round($wafers->count() / 14);

            $count = 0;
            for($i = 1;$i <= $blocks; $i++) {
                $wafer = (object) [];
                $wafer->article = $order->article;
                $wafer->format = $order->article_desc;
                $wafer->po = $order->po;
                $wafer->po_cust = $order->po_cust;
                $wafer->article_cust = $order->article_cust;
                $wafer->serials = $wafers->filter(function($value, $key) use ($i) {
                    return $key >= (($i - 1) * 14) && $key < ($i * 14);
                });

                $selectedWs->put($count, $wafer);
                $count++;
            }

            $wafers = $selectedWs;

            if(!empty($wafers)) {
                $startPos = 0;
                $pdf = Pdf::loadView('content.print.shipment-labels', compact('wafers', 'startPos'));
                $filename = "tmp/{$orderId}-" . rand() . ".pdf";
                $pdf->save($filename);
                $this->dispatchBrowserEvent('printPdf', asset($filename));
            }
        }
    }

    public function clearTemp() {
        foreach(glob('tmp/*.*') as $v){
            unlink($v);
        }
    }

    public function render()
    {
        $orders = Order::where('po', $this->po->id)->with(['serials'])->orderBy('po_pos', 'asc')->lazy();

        return view('livewire.data.serial-list', compact('orders'));
    }
}
