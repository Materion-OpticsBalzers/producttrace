<?php

class WaferHelper {

    public static function waferWithBoxRejected($wafer_id, $ar_box) {
        $wafers = \App\Models\Data\Process::where('wafer_id', $wafer_id)
            ->where(function($query) use ($ar_box) {
                return $query->where('ar_box', $ar_box)->orWhere('box', $ar_box);
            })
            ->whereHas('rejection', function($query) {
                return $query->where('reject', true);
            })->get();

        return $wafers->count() > 0;
    }

    public static function waferDuplicatedCheck($wafer_id, $order_id) {
        $order = \App\Models\Data\Order::find($order_id);

        $wafers = \App\Models\Data\Process::with('order')->where('wafer_id', $wafer_id)
            ->whereRelation('order', 'mapping_id', $order->mapping_id)->where('order_id', '!=', $order_id)->get();

        return $wafers->count() > 0;
    }

    public static function checkIfBoxHasCDValues($box, $order_id) : bool {
        if($box) {
            $wafers = \App\Models\Data\Process::where('ar_box', $box)->where('order_id', '!=', $order_id)
                ->where('block_id', BlockHelper::BLOCK_MICROSCOPE_AOI)->where('cd_ur', '>', 0)
                ->where('cd_ol', '>', 0)->limit(2)
                ->get(['cd_ol', 'cd_ur']);

            return $wafers->count() >= 2;
        } else {
            return true;
        }
    }
}
