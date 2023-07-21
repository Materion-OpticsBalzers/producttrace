<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Coa;
use App\Models\Data\Order;
use App\Models\Data\Serial;
use App\Models\Data\SerialList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

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

            $coa = Coa::where('order_id', $order->id)->first();
            if($coa != null) {
                \CoaHelper::generateCoa($order, true, User::find($coa->user_id));
            }
        }

        $delivery_date = DB::connection('oracle')->select("SELECT BESTELLDATUM_C as datum FROM PROD_ERP_001.DOK
            LEFT JOIN PROD_ERP_001.CUST0011 ON CUST0011.DOKNR = DOK.DOKNR
            WHERE DOK.DOKNR = '{$po}'");

        if(!empty($delivery_date)) {
            $delivery_date = $delivery_date[0];
        }

        $sl = SerialList::updateOrCreate([
            'id' => $po
        ], [
            'article' => $orders->first()->article,
            'article_cust' => $orders->first()->article_cust,
            'format' => $orders->first()->article_desc,
            'po_cust' => $poSearch[0]->kundendoknr,
            'delivery_date' => $delivery_date->datum
        ]);

        $this->generate($sl);

        session()->flash('success');
    }

    public function generate($po) {
        $spreadsheet = IOFactory::load(public_path('media/template.xls'));
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('B3', $po->po_cust ?: '');
        $sheet->setCellValue('B4', $po->delivery_date ? date('d/m/Y', strtotime($po->delivery_date)) : '');
        $sheet->setCellValue('B5', $po->id);
        $sheet->setCellValue('B6', $po->article);
        $sheet->setCellValue('B8', $po->article_cust);
        $sheet->setCellValue('B9', $po->format);

        $orders = Order::where('po', $po->id)->with('serials')->orderBy('po_pos', 'asc')->lazy();

        $startIndex = 12;
        if($orders->count() > 0) {
            $firstPos = $orders->first()->po_pos / 10;
            $startIndex += $firstPos - 1;
        }

        foreach($orders as $order) {
            $sheet->setCellValue("B{$startIndex}", $order->serials->first()->id ?? '?');
            $sheet->setCellValue("C{$startIndex}", $order->serials->last()->id ?? '?');
            $sheet->setCellValue("D{$startIndex}", $order->serials->count());
            $sheet->setCellValue("E{$startIndex}", $order->serials->count() - $order->missingSerials()->count());
            $sheet->setCellValue("F{$startIndex}",  join(', ', $order->missingSerials()->pluck('id')->toArray()));

            $startIndex++;
        }

        $writer = new Xls($spreadsheet);
        $writer->save(public_path('tmp\sl_' . $po->id . '.xls'));
        $spreadsheet->disconnectWorksheets();

        if(!Storage::disk('s')->exists(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year))
            Storage::disk('s')->makeDirectory(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year);

        if(!Storage::disk('s')->exists(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust))
            Storage::disk('s')->makeDirectory(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust);

        File::move(public_path('tmp\sl_' . $po->id . '.xls'), '\\\\opticsbalzers.local\\data\\' . config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust . '\\' . $po->id . '_' . $po->po_cust .  '.xls');

        session()->flash('success');

        return back();
    }

    public function unlink($order) {
        $order = Order::find($order);
        $sl = SerialList::find($order->po);

        $order->update([
            'po' => null,
            'po_pos' => null,
            'po_cust' => null
        ]);

        if($sl != null) {
            $this->generate($sl);
        }
    }

    public function render()
    {
        $orders = Order::orderBy('created_at', 'asc')->where('mapping_id', 4)->has('coa')->with('serials')->lazy();

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
