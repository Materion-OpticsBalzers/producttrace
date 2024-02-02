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
}
