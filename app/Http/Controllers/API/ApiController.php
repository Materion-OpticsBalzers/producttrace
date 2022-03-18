<?php

namespace App\Http\Controllers\API;

use App\Events\WaferScanned;
use App\Http\Controllers\Controller;
use App\Models\Data\Order;
use App\Models\Data\Scan;
use App\Models\Generic\Block;
use App\Models\Generic\Mapping;
use Psy\Util\Json;

class ApiController extends Controller
{
    public function getOrder(Order $order) {
        return $order->toJson();
    }

    public function getBlock(Order $order, $block) {
        $block = Block::where('identifier', $block)->first();

        return $block->toJson();
    }

    public function createToken() {
        $token = auth()->user()->createToken('default');

        session()->flash('token', $token->plainTextToken);

        return back();
    }

    public function getMappings() {
        return Mapping::with('product')->get()->toJson();
    }

    public function createOrder() {
        $data = \request()->all();

        $missing_fields = [];

        if(!isset($data["order"]) || $data["order"] == '') {
            $missing_fields[] = ["order" => "The Order field is missing"];
        }

        if(!isset($data["mapping"]) || $data["mapping"] == '') {
            $missing_fields[] = ["mapping" => "The Mapping ID is missing"];
        }

        if(!empty($missing_fields)) {
            return Json::encode([
                'code' => 105,
                'message' => "Invalid form Data",
                'fields' => $missing_fields
            ]);
        }

        if(Mapping::find($data["mapping"]) == null) {
            return Json::encode([
                'code' => 404,
                'message' => "Ivalid Mapping ID, this Mapping was not found!",
            ]);
        }

        return Order::updateOrCreate([
            'id' => $data["order"]
        ], [
           'id' => $data["order"],
           'mapping_id' => $data["mapping"]
        ]);
    }

    public function scanWafer($blockSlug) {
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

        event(new WaferScanned($block));

        return Scan::updateOrCreate([
            'block_id' => $block->id,
            'value' => $value
        ],[
            'block_id' => $block->id,
            'value' => $value
        ])->toJson();
    }
}
