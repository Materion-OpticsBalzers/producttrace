<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Livewire\Component;

class ChromiumCoating extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $machine = '';
    public $box = null;
    public $batch = '';
    public $calculatedPosition = 'Aussen';

    public string $selectedWafer = '';

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

        if ($this->prevBlock != null && !$wafer->is_rework) {
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

    public function addEntry($order, $block, $operator) {
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

        if($this->machine == '') {
            $this->addError('machine', 'Anlagennummer darf nicht leer sein!');
            $error = true;
        }

        if($this->batch == '') {
            $this->addError('lot', 'Die Chargennummer darf nicht leer sein!');
            $error = true;
        }

        if($error)
            return false;

        if(!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

        Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $order,
            'block_id' => $block,
            'operator' => $operator,
            'box' => $this->box,
            'machine' => $this->machine,
            'lot' => $this->batch,
            'position' => $this->calculatedPosition,
            'date' => now()
        ]);

        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
    }

    public function updateEntry($entryId, $operator, $box, $lot, $machine, $position) {
        $this->resetErrorBag();

        if($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        if($box == '') {
            $this->addError('edit' . $entryId, 'Box darf nicht leer sein!');
            return false;
        }

        if($lot == '') {
            $this->addError('edit' . $entryId, 'Die Charge darf nicht leer sein!');
            return false;
        }

        $process = Process::find($entryId);

        $process->update([
            'operator' => $operator,
            'box' => $box,
            'machine' => $machine,
            'lot' => $lot,
            'position' => $position
        ]);

        session()->flash('success' . $entryId);
    }

    public function removeEntry($entryId)
    {
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

        if($process->reworked) {
            Wafer::find($process->wafer_id)->update([
                'reworked' => false
            ]);
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

    public function updated($name) {
        if($name == 'box') {
            try {
                $data = DB::connection('sqlsrv_eng')->select("SELECT TOP 1 identifier, batch FROM BAKCr_chargenprotokoll
                LEFT JOIN machine ON machine.id = BAKCr_chargenprotokoll.machine_id
                WHERE order_id = '$this->orderId' AND box_id = '{$this->box}'");
            } catch(QueryException $ex) {
                $data = [];
                $this->addError('lot', 'Chromdaten konnten für diesen Wafer und die Box nicht gefunden werden!');
            }

            if(!empty($data)) {
                $this->machine = $data[0]->identifier;
                $this->batch = $data[0]->batch;
            } else {
                $this->machine = '';
                $this->batch = '';
                $this->addError('lot', 'Chromdaten konnten für diesen Wafer und die Box nicht gefunden werden!');
            }
        }
    }

    public function updateWafer($wafer, $box) {
        $this->selectedWafer = $wafer;
        $this->box = $box;

        $this->updated('box');
    }

    public function rework(Process $process) {
        $rWafer = Wafer::find($process->wafer_id . '-r');
        if($rWafer != null) {
            $process->update(['reworked' => true]);

            $wafer = Wafer::find($process->wafer_id);
            $wafer->update(['reworked' => true]);
        } else {
            $process->update(['reworked' => true]);

            $wafer = Wafer::find($process->wafer_id);
            $wafer->update(['reworked' => true]);

            $newWafer = $wafer->replicate();
            $newWafer->id = $wafer->id . '-r';
            $newWafer->reworked = false;
            $newWafer->is_rework = true;
            $newWafer->rejected = false;
            $newWafer->rejection_reason = null;
            $newWafer->rejection_position = null;
            $newWafer->rejection_avo = null;
            $newWafer->rejection_order = null;
            $newWafer->save();
        }
    }


    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->where('block_id', $this->blockId)->with('rejection')->with('wafer')->orderBy('wafer_id', 'asc')->lazy();

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        if($this->selectedWafer == '') {
            $this->getScannedWafer();
        }

        if($this->selectedWafer != '')
            $sWafers = Wafer::where('id', 'like', "%{$this->selectedWafer}%")->limit(28)->get();
        else
            $sWafers = [];

        $waferCount = $wafers->where('wafer.reworked', false)->count();

        if($waferCount >= 9 && $waferCount < 13)
            $this->calculatedPosition = 'Mitte';
        elseif($waferCount >= 13)
            $this->calculatedPosition = 'Zentrum';

        return view('livewire.blocks.chromium-coating', compact('block', 'wafers', 'sWafers'));
    }
}
