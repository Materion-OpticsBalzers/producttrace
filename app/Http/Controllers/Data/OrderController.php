<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Order $order) {
        return view('content.data.orders.show', ['order' => $order->id]);
    }
}
