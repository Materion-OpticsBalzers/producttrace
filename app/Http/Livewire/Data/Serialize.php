<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Order;
use App\Models\Data\Serial;
use App\Models\Data\SerialList;
use Livewire\Component;

class Serialize extends Component
{
    public $search = '';
    public $searchAb = '';
    public $showSet = false;

    public function setOrder($orders, $po, $pos) {
        if($po == '') {
            $this->addError('po', 'Auftrag darf nicht leer sein!');
            return false;
        }

        if($pos == '') {
            $this->addError('pos', 'Position darf nicht leer sein!');
            return false;
        }

        if(empty($orders)) {
            $this->addError('po', 'Es muss mindestens ein auftrag ausgewählt werden!');
            return false;
        }

        $poSearch = \DB::connection('oracle')->select("SELECT DOKNR, KUNDENDOKNR FROM PROD_ERP_001.DOK WHERE DOKNR = '{$po}'");

        if(empty($poSearch)) {
            $this->addError('po', 'Diese AB existiert nicht im ERP!');
            return false;
        }

        $orders = Order::find($orders)->lazy();

        $initPos = $pos;
        foreach($orders as $order) {
            $posSearch = \DB::connection('oracle')->select("SELECT DOKNR, POSNREXT, ARTNR FROM PROD_ERP_001.DOKPOS WHERE DOKNR = '{$po}' AND POSNREXT = {$pos} AND ROWNUM = 1");

            if(empty($posSearch)) {
                $this->addError('pos', "Die Position {$pos} wurde in der AB ({$po}) nicht gefunden!");
                return false;
            }

            if($posSearch[0]->artnr != $order->article) {
                $this->addError('pos', "Der Artikel ({$posSearch[0]->artnr}) auf der AB Position {$pos} stimmt nicht mit dem Artikel ({$order->article}) im Auftrag ({$order->id}) überein!");
                return false;
            }

            $poExists = Order::where('po', $po)->where('po_pos', $pos)->first();

            if($poExists != null) {
                $this->addError('pos', "Die Postion {$pos} wurde schon einem anderen Auftrag ({$order->id}) zugewiesen!");
                return false;
            }

            $pos += 10;
        }

        $pos = $initPos;
        foreach($orders as $order) {
            if($order->po == '') {
                $order->update([
                    'po' => $po,
                    'po_pos' => $pos,
                    'po_cust' => $poSearch[0]->kundendoknr
                ]);
                $pos += 10;
            }
        }

        SerialList::updateOrCreate([
            'id' => $po
        ], [
            'article' => $orders->first()->article,
            'article_cust' => $orders->first()->article_cust,
            'format' => $orders->first()->article_desc,
            'po_cust' => $poSearch[0]->kundendoknr
        ]);

        session()->flash('success');
    }

    public function unlink($order) {
        Order::find($order)->update([
            'po' => null,
            'po_pos' => null,
            'po_cust' => null
        ]);
    }

    public function render()
    {
        $orders = Order::orderBy('created_at', 'desc')->where('mapping_id', 4)->with('serials')->lazy();

        if(!$this->showSet)
            $orders = $orders->whereNull('po');

        if($this->search != '') {
            $orders = $orders->filter(function($value) {
               return stristr($value->article, $this->search);
            });
        }

        if($this->searchAb != '') {
            $orders = $orders->filter(function($value) {
                return stristr($value->po, $this->searchAb);
            });
        }

        $serialLists = SerialList::orderBy('created_at')->lazy();

        return view('livewire.data.serialize', compact('orders', 'serialLists'));
    }
}
