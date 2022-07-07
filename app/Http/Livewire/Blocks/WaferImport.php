<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use Livewire\Component;

class WaferImport extends Component
{
    public $blockId;
    public $orderId;

    public $search = '';

    public function importWafers($box) {
        if($box == '') {
            $this->addError('import', 'Die Box ID darf nicht leer sein!');
            return false;
        }

        $file = collect(\Storage::drive('s')->files('090 Produktion/10 Linie 1/20 Produktionsanlagen/200 PhotonEnergy/LaserMarkingDataManagementOutput'))->filter(function($value) use ($box) {
            return str_starts_with(basename($value), $box) && str_ends_with(basename($value), 'DMC.txt');
        })->first();

        if($file != null) {
            $values = explode(';', explode("\n", \Storage::disk('s')->get($file))[1]);
            $values = array_splice($values, 1);
            $values = array_filter($values, function ($value) {
                return $value != '';
            });
            $wafers = array_unique($values);

            foreach($wafers as $wafer) {
                Wafer::firstOrCreate([
                    'id' => $wafer,
                    'order_id' => $this->orderId,
                    'box' => $box
                ]);
            }

            session()->flash('success');
        } else {
            $this->addError('import', 'Es konnte keine passende Datei gefunden werden!');
        }
    }

    public function clear($order, $block) {
        $wafers = Process::where('order_id', $order)->where('block_id', $block)->with('wafer');

        foreach ($wafers->lazy() as $wafer) {
            if($wafer->rejection != null) {
                if ($wafer->wafer->rejected && $wafer->rejection->reject) {
                    Wafer::find($wafer->wafer_id)->update([
                        'rejected' => false,
                        'rejection_reason' => null,
                        'rejection_position' => null,
                        'rejection_avo' => null,
                        'rejection_order' => null
                    ]);
                }
            }
        }

        $wafers->delete();
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Wafer::where('order_id', $this->orderId)->orderBy('id')->lazy();

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        return view('livewire.blocks.wafer-import', compact('block', 'wafers'));
    }
}
