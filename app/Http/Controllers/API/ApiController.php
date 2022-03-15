<?php

namespace App\Http\Controllers\API;

use App\Events\WaferScanned;
use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\Scan;
use App\Models\Generic\Block;
use Illuminate\Http\Request;
use Psy\Util\Json;
use function MongoDB\BSON\toJSON;

class ApiController extends Controller
{
    public function getOrder(Order $order) {
        return $order->toJson();
    }

    public function createToken() {
        $token = auth()->user()->createToken('default');

        session()->flash('token', $token->plainTextToken);

        return back();
    }

    public function scanWafer(Order $order, $blockSlug) {
        $block = Block::where('identifier', $blockSlug)->first();

        if($block == null) {
            return Json::encode([
                'code' => 404,
                'message' => "The block ($blockSlug) was not found on this order!"
            ]);
        }

        $value = \request()->get('value');

        if($value == '') {
            return Json::encode([
                'code' => 105,
                'message' => "Invalid form Data",
                'fields' => [
                    'value' => 'The value field is empty!'
                ]
            ]);
        }

        event(new WaferScanned($order, $block));

        return Scan::updateOrCreate([
            'order_id' => $order->id,
            'block_id' => $block->id,
            'value' => $value
        ],[
            'order_id' => $order->id,
            'block_id' => $block->id,
            'value' => $value
        ])->toJson();
    }
}
