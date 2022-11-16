<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;

class OrderController extends Controller
{
    public function show(Order $order) {
        if($order->mapping->init_block != '') {
            $init_block = Block::find($order->mapping->init_block);

            return redirect()->route('blocks.show', ['order' => $order->id, 'block' => $init_block->identifier]);
        }

        return view('content.data.orders.show', ['order' => $order->id]);
    }

    public function create() {
        $mappings = Mapping::with('product')->get();

        return view('content.data.orders.create', compact('mappings'));
    }

    public function test() {

    }

    public function update() {
        $data = request()->validate([
            'id' => 'required|string|max:20'
        ]);

        $order = Order::find($data['id']);

        if($order == null)
            return back()->withErrors(['id' => 'Auftrag wurde nicht gefunden']);

        $result = \DB::connection('oracle')->select("SELECT PRDNR, PRD.ARTNR, PRD.KADRNR, KART.KNDARTNR, ART.KURZBEZ FROM PROD_ERP_001.PRD
                LEFT JOIN PROD_ERP_001.KART ON KART.ARTNR = PRD.ARTNR AND KART.KADRNR = PRD.KADRNR
                LEFT JOIN PROD_ERP_001.ART ON ART.ARTNR = PRD.ARTNR
                WHERE PRD.PRDNR = '{$order->id}'");

        if(empty($result))
            return back()->withErrors(['id' => 'Auftrag wurde im ERP nicht gefunden']);

        $result = $result[0];

        $order->update([
            'article' => $result->artnr,
            'article_desc' => $result->kurzbez,
            'article_cust' => $result->kndartnr,
            'customer' => $result->kadrnr
        ]);

        session()->flash('success.update');
        return back();
    }

    public function store() {
        $data = \request()->validate([
           'id' => 'required|string|max:20|unique:orders',
           'mapping_id' => 'required',
            'article' => 'required'
        ]);

        Order::create($data);

        session()->flash('success');

        return back();
    }
}
