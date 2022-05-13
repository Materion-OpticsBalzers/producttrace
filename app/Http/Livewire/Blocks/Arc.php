<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Arc extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $machine = '';
    public $box = null;
    public $lot = '';
    public $calculatedPosition = 'Zentrum';

    public $selectedWafer = null;
    public $initiated = false;

    public function getListeners(): array
    {
        return [
            "echo:private-scanWafer.{$this->blockId},.wafer.scanned" => 'getScannedWafer'
        ];
    }

    public function getScannedWafer() {
        $scan = Scan::where('block_id', $this->blockId)->first();

        if ($scan != null) {
            $this->selectedWafer = $scan->value;
            session()->flash('waferScanned');
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
            $this->addError('machine', 'Die Anlage darf nicht leer sein!');
            $error = true;
        }

        if($this->lot == '') {
            $this->addError('lot', 'Die Charge Darf nicht leer sein!');
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
            'lot' => $this->lot,
            'position' => $this->calculatedPosition,
            'date' => now()
        ]);

        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
    }

    public function removeEntry($entryId) {
        $process = Process::find($entryId);

        $process->delete();
    }

    public function clear($order, $block) {
        $wafers = Process::where('order_id', $order)->where('block_id', $block)->with('wafer');

        $wafers->delete();
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
            $wafers = $wafers->filter(function ($value, $key) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        if($this->selectedWafer == '') {
            $this->getScannedWafer();
        }

        if($this->selectedWafer != '')
            $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->orderId)->where('wafer_id', 'like', "%{$this->selectedWafer}%")->with('wafer')->lazy();
        else
            $sWafers = [];

        if(!$this->initiated) {
            $data = DB::connection('sqlsrv_eng')->select("SELECT TOP 1 identifier, batch FROM LEY_chargenprotokoll
                LEFT JOIN machine ON machine.id = LEY_chargenprotokoll.machine_id
                WHERE order_id = '$this->orderId'");

            if (!empty($data)) {
                $this->machine = $data[0]->identifier;
                $this->lot = $data[0]->batch;
            } else {
                $this->machine = '';
                $this->lot = '';
                $this->addError('lot', 'AR Daten konnten für diesen Auftrag nicht gefunden werden!');
            }
            $this->initiated = true;
        }

        $waferCount = $wafers->count();
        $zentrumSlots = [1, 8, 15, 22];
        $mitteSlots = [2, 3, 9, 10, 16, 17, 23, 24];
        $aussenSlots = [4, 5, 6, 7, 11, 12, 13, 14, 18, 19, 20, 21, 25, 26, 27, 28];

        if(in_array($waferCount, $zentrumSlots))
            $this->calculatedPosition = 'Zentrum';

        if(in_array($waferCount, $mitteSlots))
            $this->calculatedPosition = 'Mitte';

        if(in_array($waferCount, $aussenSlots))
            $this->calculatedPosition = 'Aussen';

        return view('livewire.blocks.arc', compact('block', 'wafers', 'sWafers'));
    }
}
