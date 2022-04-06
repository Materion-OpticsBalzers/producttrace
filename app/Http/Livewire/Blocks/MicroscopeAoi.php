<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MicroscopeAoi extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $box = null;
    public $x = null;
    public $y = null;
    public $z = null;
    public $cdo = null;
    public $cdu = null;

    public $selectedWafer = null;

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

        if($wafer->reworks == 2) {
            $this->addError('wafer', 'Dieser Wafer darf nicht mehr verwendet werden.');
            return false;
        }

        if($this->prevBlock != null) {
            $prevWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->orderId)->where('block_id', $this->prevBlock)->first();
            if ($prevWafer == null) {
                $this->addError('wafer', 'Dieser Wafer existiert nicht im vorherigen Schritt!');
                return false;
            }
        }

        if(Process::where('wafer_id', $wafer->id)->where('order_id', $this->orderId)->where('block_id', $this->blockId)->exists()) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            return false;
        }

        return true;
    }

    public function addEntry($order, $block, $operator, $rejection) {
        $error = false;

        if(!$this->checkWafer($this->selectedWafer)) {
            $this->addError('response', 'Ein Fehler mit der Wafernummer hat das Speichern verhindert');
            $error = true;
        }

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

        $rejection = Rejection::find($rejection);

        Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $order,
            'block_id' => $block,
            'rejection_id' => $rejection->id,
            'operator' => $operator,
            'box' => $this->box,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'cd_ol' => $this->cdo,
            'cd_ur' => $this->cdu,
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
            $aoi_data_xyz = \DB::connection('sqlsrv_aoi')->select("SELECT TOP 3 pproductiondata.rid, Name, Tool, Distance FROM pproductiondata
            INNER JOIN pmaterialinfo ON pmaterialinfo.PId = pproductiondata.RId
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            WHERE NAME LIKE '%{$this->orderId}%' ORDER BY DestSlot");

            $aoi_cd = \DB::connection('sqlsrv_aoi')->select("SELECT max(pairwidth1) as cdo, max(pairwidth2) as cdu FROM pproductiondata
            INNER JOIN pmaterialinfo ON pmaterialinfo.PId = pproductiondata.RId
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            WHERE NAME LIKE '%{$this->orderId}%'
            GROUP BY destslot
            ORDER BY DestSlot");

            $this->cdo = $aoi_cd[0]->cdo;
            $this->cdu = $aoi_cd[0]->cdu;

            $this->x = $aoi_data_xyz[0]->Distance ?? null;
            $this->y = $aoi_data_xyz[1]->Distance ?? null;
            $this->z = $aoi_data_xyz[2]->Distance ?? null;
        }
    }

    public function updateWafer($wafer, $box) {
        $this->selectedWafer = $wafer;
        $this->box = $box;

        $this->updated('box');
    }

    public function render()
    {
        $block = Block::find($this->blockId);

        $wafers = Process::where('order_id', $this->orderId)->where('block_id', $this->blockId)->with('rejection')->orderBy('wafer_id', 'asc')->lazy();

        if($this->search != '') {
            $wafers = $wafers->filter(function ($value) {
                return stristr($value->wafer_id, $this->search);
            });
        }

        $rejections = Rejection::find($block->rejections);

        if(!empty($rejections))
            $rejections = $rejections->sortBy('number');

        if($this->selectedWafer == '') {
            $this->getScannedWafer();
        }

        if($this->selectedWafer != '')
            $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->orderId)->where('wafer_id', 'like', "%{$this->selectedWafer}%")->with('wafer')->lazy();
        else
            $sWafers = [];

        return view('livewire.blocks.microscope-aoi', compact('block', 'wafers', 'rejections', 'sWafers'));
    }
}
