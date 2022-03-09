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

    public function importWafers() {
        $file = collect(\Storage::drive('s')->files('050 IT/81 Dokus Elias/Tests'))->filter(function($value) {
            return str_starts_with(basename($value), $this->orderId) && str_ends_with(basename($value), 'DMC.txt');
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
                ]);

                Process::create([
                    'wafer_id' => $wafer,
                    'order_id' => $this->orderId,
                    'block_id' => $this->blockId,
                    'operator' => auth()->user()->personnel_number,
                    'date' => now()
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

        $wafers = Process::where('order_id', $this->orderId)->with('rejection')->orderBy('wafer_id')->lazy();

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        return view('livewire.blocks.wafer-import', compact('block', 'wafers'));
    }
}
