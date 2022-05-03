<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\SerialList;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SerialController extends Controller
{
    public function index()
    {
        return view('content.data.serials.index');
    }

    public function list(SerialList $po)
    {
        $orders = Order::where('po', $po->id)->with('serials')->orderBy('po_pos', 'asc')->lazy();

        return view('content.data.serials.list', compact('po', 'orders'));
    }

    public function generate(SerialList $po) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', "Serialization Scheme for Optics Balzers");

        $writer = new Xlsx($spreadsheet);
        $writer->save("\\\\opticsbalzers.local\\data2\\050 IT\\81 Dokus Elias\\Tests\\test.xlsx");

        return back();
    }

    public function search() {
        $search = \request()->input('search');
        $orders = Order::orderBy('created_at')->where('mapping_id', 4)->where('article', 'like', "%{$search}%")->with('serials')->paginate(20);

        return view('content.data.serials.index', compact('orders', 'search'));
    }

    public function store(Order $order) {
        $data = \request()->validate([
            'po' => 'required|max:30',
            'po_pos' => 'required|integer'
        ]);

        $order->update([
            'po' => $data["po"],
            'po_pos' => $data["po_pos"]
        ]);

        return back();
    }

    public function destroy(Order $order) {
        $order->update([
            'po' => null,
            'po_pos' => null
        ]);

        return back();
    }
}
