<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Serial;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Livewire\Component;

class IncomingQualityControlAr extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $searchField = 'wafer_id';

    public $selectedWafer = null;
    public $selectedRejection = 6;
    public $box = null;
    public $serial = '';

    public function getListeners(): array
    {
        return [
            "echo:scanWafer.{$this->blockId},WaferScanned" => 'getScannedWafer'
        ];
    }

    public function getScannedWafer() {
        $scan = Scan::where('block_id', $this->blockId)->first();

        if ($scan != null) {
            $this->updateSerial($scan->value);
            $scan->delete();
        }
    }

    public function checkWafer($waferId) {
        if($waferId == '') {
            $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
            return false;
        }

        $wafer = Wafer::find($waferId);

        if($wafer == null) {
            $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
            return false;
        }

        if($wafer->rejected){
            if($this->nextBlock != null) {
                $nextWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->orderId)->where('block_id', $this->nextBlock)->first();
                if($nextWafer == null) {
                    $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                    return false;
                }
            } else {
                $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                return false;
            }
        }

        if($wafer->reworked) {
            $this->addError('wafer', "Dieser Wafer wurde nachbearbeitet und kann nicht mehr verwendet werden!");
            return false;
        }

        if ($this->prevBlock != null) {
            $prevWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->orderId)->where('block_id', $this->prevBlock)->first();
            if ($prevWafer == null) {
                $this->addError('wafer', 'Dieser Wafer existiert nicht im vorherigen Schritt!');
                return false;
            }
        }

        if(!Process::where(function($query) use ($wafer) {
            $query->where('wafer_id', $wafer->id)->orWhere('wafer_id', "{$wafer->id}-r");
        })->where('block_id', 6)->exists()) {
            $this->addError('wafer', 'Dieser Wafer muss zuerst durch die Mikroskopkontrolle bevor dieser hier verwendet werden darf!');
            return false;
        }

        if (Process::where('wafer_id', $wafer->id)->where('order_id', $this->orderId)->where('block_id', $this->blockId)->exists()) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            return false;
        }

        return true;
    }

    public function addEntry($order, $block, $operator, $rejection) {
        $this->resetErrorBag();
        $error = false;

        if($operator == '') {
            $this->addError('operator', 'Der Operator darf nicht leer sein!');
            $error = true;
        }

        if($this->serial == '') {
            $this->addError('serial', 'Die Serial ID darf nicht leer sein!');
            $error = true;
        }

        if($rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        $serial = Serial::find($this->serial);
        if($serial == null) {
            $this->addError('serial', 'Diese Seriennummer ist nicht gültig für diesen Auftrag!');
            return false;
        }

        if($serial->wafer_id != '') {
            $this->addError('serial', 'Diese Seriennummer wurde schon zugewiesen!');
            return false;
        }

        if(!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

        $rejection = Rejection::find($rejection);

        Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $order,
            'block_id' => $block,
            'serial_id' => $this->serial,
            'rejection_id' => $rejection->id,
            'operator' => $operator,
            'box' => $this->box,
            'date' => now()
        ]);

        Serial::find($this->serial)->update([
            'wafer_id' => $this->selectedWafer
        ]);

        if($rejection->reject) {
            $blockQ = Block::find($block);

            Wafer::find($this->selectedWafer)->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $blockQ->name,
                'rejection_avo' => $blockQ->avo,
                'rejection_order' => $order
            ]);
        }

        $this->serial = '';
        $this->selectedWafer = '';
        $this->selectedRejection = 6;
        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
        $this->dispatchBrowserEvent('saved');
    }

    public function updateEntry($entryId, $operator, $box, $rejection) {
        if($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        $rejection = Rejection::find($rejection);
        $process = Process::find($entryId);
        $wafer = Wafer::find($process->wafer_id);

        if($wafer->rejected && $rejection->reject && $rejection->id != $process->rejection_id && !$process->rejection->reject){
            $this->addError('edit' . $entryId, "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
            return false;
        }

        if($rejection->reject) {
            $blockQ = Block::find($process->block_id);

            $wafer->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $blockQ->name,
                'rejection_avo' => $blockQ->avo,
                'rejection_order' => $process->order_id
            ]);
        } else {
            if($process->rejection->reject) {
                $wafer->update([
                    'rejected' => 0,
                    'rejection_reason' => null,
                    'rejection_position' => null,
                    'rejection_avo' => null,
                    'rejection_order' => null
                ]);
            }
        }

        $process->update([
            'operator' => $operator,
            'box' => $box,
            'rejection_id' => $rejection->id
        ]);

        session()->flash('success' . $entryId);
    }

    public function removeEntry($entryId) {
        $process = Process::find($entryId);

        if ($process->rejection != null) {
            if ($process->wafer->rejected && $process->rejection->reject) {
                Wafer::find($process->wafer_id)->update([
                    'rejected' => false,
                    'rejection_reason' => null,
                    'rejection_position' => null,
                    'rejection_avo' => null,
                    'rejection_order' => null
                ]);
            }
        }

        Serial::find($process->serial_id)->update([
            'wafer_id' => null
        ]);

        $process->delete();
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

            Serial::where('wafer_id', $wafer->wafer_id)->update([
                'wafer_id' => null
            ]);
        }

        $wafers->delete();
    }

    public function updateSerial($serial) {
        $this->serial = $serial;
    }

    public function updateWafer($wafer, $box) {
        $this->selectedWafer = $wafer;
        $this->box = $box;
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->where('block_id', $this->blockId)->with('rejection')->orderBy('wafer_id', 'asc')->lazy();

        if($this->search != '') {
            $searchField = $this->searchField;
            $wafers = $wafers->filter(function ($value, $key) use ($searchField) {
                return stristr($value->$searchField, $this->search);
            });
        }

        $rejections = Rejection::find($block->rejections);

        if(!empty($rejections))
            $rejections = $rejections->sortBy('number');

        if($this->selectedWafer == '')
            $this->getScannedWafer();

        if($this->selectedWafer != '') {
            $sWafers = Process::where('block_id', 6)->where(function ($query) {
                $query->where('wafer_id', $this->selectedWafer)->orWhere('wafer_id', $this->selectedWafer . '-r');
            })->orderBy('wafer_id', 'desc')->with('wafer')->get();

            if ($sWafers->count() > 0) {
                $this->updateWafer($sWafers->get(0)->wafer_id, $sWafers->get(0)->ar_box);
            }
        } else
            $sWafers = [];

        if($this->serial != '')
            $serials = Serial::where('order_id', $this->orderId)->where('id', 'like', "%{$this->serial}%")->where('rejected', false)->whereNull('wafer_id')->lazy();
        else
            $serials = Serial::where('order_id', $this->orderId)->where('rejected', false)->whereNull('wafer_id')->lazy();

        if($serials->count() > 0)
            $this->serial = $serials->first()->id;

        return view('livewire.blocks.incoming-quality-control-ar', compact('block', 'wafers', 'rejections', 'sWafers', 'serials'));
    }
}
