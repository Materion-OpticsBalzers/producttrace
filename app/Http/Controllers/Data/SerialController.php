<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\Serial;
use Illuminate\Http\Request;

class SerialController extends Controller
{
    public function index() {
        $orders = Order::orderBy('created_at')->where('mapping_id', 4)->with('serials')->paginate(20);

        return view('content.data.serials.index', compact('orders'));
    }

    public function search() {
        $search = \request()->input('search');
        $orders = Order::orderBy('created_at')->where('mapping_id', 4)->where('id', 'like', "%{$search}%")->with('serials')->paginate(20);

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
}
