<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\Process;
use App\Models\Data\Serial;
use App\Models\Data\Wafer;

class WaferController extends Controller
{
    public function show(Wafer $wafer) {
        if(str_ends_with($wafer->id, '-r'))
            $addtnlWafers = str_replace('-r', '', $wafer->id);
        else
            $addtnlWafers = $wafer->id.'-r';

        $waferData = Process::with(['block', 'rejection'])->whereIn('wafer_id', [$wafer->id, $addtnlWafers])->lazy();
        $waferOrders = Process::whereIn('wafer_id', [$wafer->id, $addtnlWafers])->select('order_id')->groupBy('order_id')->get();

        $serial = Serial::where('wafer_id', $wafer->id)->first();

        $infos = (object)[];
        $infos->crlot = Process::where('wafer_id', $wafer->id)->where('block_id', 2)->first()->lot ?? null;
        $infos->arlot = Process::where('wafer_id', $wafer->id)->where('block_id', 8)->first()->lot ?? null;

        return view('content.data.wafers.show', compact('wafer', 'waferData', 'waferOrders','serial', 'infos'));
    }
}
