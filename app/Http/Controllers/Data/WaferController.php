<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Process;
use App\Models\Data\Wafer;

class WaferController extends Controller
{
    public function show(Wafer $wafer) {
        $waferData = Process::with(['block', 'rejection'])->where('wafer_id', $wafer->id)->lazy();

        $waferOrders = Process::where('wafer_id', $wafer->id)->select('order_id')->groupBy('order_id')->get();

        return view('content.data.wafers.show', compact('wafer', 'waferData', 'waferOrders'));
    }
}
