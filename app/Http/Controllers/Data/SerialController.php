<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\Serial;
use Illuminate\Http\Request;

class SerialController extends Controller
{
    public function index() {
        $orders = Order::orderBy('created_at')->where('mapping_id', 4)->paginate(20);

        return view('content.data.serials.index', compact('orders'));
    }

    public function show(Order $order) {
        $serials = Serial::where('order_id', $order->id)->lazy();

        return view('content.data.serials.show', compact('order', 'serials'));
    }
}
