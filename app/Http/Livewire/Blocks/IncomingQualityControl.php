<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Livewire\Component;
use RealRashid\SweetAlert\Facades\Alert;

class IncomingQualityControl extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $searchField = 'wafer_id';

    public $selectedWafer = null;
    public $box = null;

    public function getListeners(): array
    {
        return [
            "echo:private-scanWafer.{$this->blockId},.wafer.scanned" => 'getScannedWafer'
        ];
    }

    public function getScannedWafer() {
        $scan = Scan::where('block_id', $this->blockId)->first();

        if ($scan != null) {
            $wafer = Wafer::find($scan->value);
            $this->updateWafer($scan->value, $wafer->box ?? '');
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

        if($this->box == '') {
            $this->addError('box', 'Die Box ID Darf nicht leer sein!');
            $error = true;
        }

        if($rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        if(!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

        $rejection = Rejection::find($rejection);

        Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $order,
            'block_id' => $block,
            'rejection_id' => $rejection->id,
            'operator' => $operator,
            'box' => $this->box,
            'date' => now()
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

        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
        $this->selectedWafer = '';
        $this->dispatchBrowserEvent('saved');
    }

    public function updateEntry($entryId, $operator, $box, $rejection) {
        if($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        if($box == '') {
            $this->addError('edit' . $entryId, 'Box darf nicht leer sein!');
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
        $wafer = Wafer::find($process->wafer_id);

        if ($process->rejection != null) {
            if ($wafer->rejected && $process->rejection->reject) {
                $wafer->update([
                    'rejected' => false,
                    'rejection_reason' => null,
                    'rejection_position' => null,
                    'rejection_avo' => null,
                    'rejection_order' => null
                ]);
            }
        }

        if($process->reworked) {
            $wafer->update(['reworked' => false]);
        }

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
        }

        $wafers->delete();
    }

    public function updateWafer($wafer, $isRework = false) {
        $this->selectedWafer = $wafer;
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->where('block_id', $this->blockId)->with('rejection')->with('wafer')->orderBy('wafer_id', 'asc')->lazy();

        if($this->search != '') {
            $searchField = $this->searchField;
            $wafers = $wafers->filter(function ($value, $key) use ($searchField) {
                return stristr($value->$searchField, $this->search);
            });
        }

        $rejections = Rejection::find($block->rejections);

        if(!empty($rejections))
            $rejections = $rejections->sortBy('number');

        if($this->selectedWafer == '') {
            $this->getScannedWafer();
        }

        if($this->selectedWafer != '') {
            $sWafers = Wafer::where('id', 'like', "%{$this->selectedWafer}%")->orderBy('id', 'desc')->limit(28)->get();

            if($sWafers->count() > 0) {
                $this->updateWafer($sWafers->get(0)->id);
            }
        } else
            $sWafers = [];

        return view('livewire.blocks.incoming-quality-control', compact('block', 'wafers', 'rejections', 'sWafers'));
    }
}
