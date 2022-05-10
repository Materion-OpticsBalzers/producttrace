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
    public $rejection = 6;

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

        if($this->rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        $rejection = Rejection::find($this->rejection);

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

    public function rework(Process $process) {
        if(Wafer::find($process->wafer_id . '-r') != null) {
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
            $newWafer->save();
        }
    }

    public function updated($name) {
        if($name == 'box') {
            if(str_ends_with($this->selectedWafer, '-r'))
                $wafer = str_replace('-r', '', $this->selectedWafer);
            else
                $wafer = $this->selectedWafer;

            $aoi_data_xyz = \DB::connection('sqlsrv_aoi')->select("SELECT TOP 3 pproductiondata.rid, Distance, pproductiondata.name, pproductiondata.programname FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' ORDER BY DestSlot");

            $aoi_cd = \DB::connection('sqlsrv_aoi')->select("SELECT max(pairwidth1) as cdo, max(pairwidth2) as cdu FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' AND Tool LIKE ('critical dimension')
            GROUP BY destslot
            ORDER BY DestSlot");

            if(!empty($aoi_data_xyz)) {
                $this->cdo = $aoi_cd[0]->cdo ?? null;
                $this->cdu = $aoi_cd[0]->cdu ?? null;

                $this->x = $aoi_data_xyz[1]->Distance ?? null;
                $this->y = $aoi_data_xyz[0]->Distance ?? null;
                $this->z = $aoi_data_xyz[2]->Distance ?? null;

                $format = explode('REVIEW', $aoi_data_xyz[0]->programname)[0];
                $rid = $aoi_data_xyz[0]->rid ?? null;

                $aoi_class_ids_zero = DB::connection('sqlsrv_aoi')->select("SELECT clsid from formatclassid
                INNER JOIN formate on formatclassid.formatid = formate.id
                WHERE formatclassid.maxdefect = 0 AND formate.formatname = '{$format}'");

                $zero_defects = DB::connection('sqlsrv_aoi')->select("select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,DieRow ,diecol,ci.defectname,ci.caqdefectname,
                (case when DieRow<0 then 0 else 1 end) as isDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=ir.pid
                Inner Join ClassID CI on CI.clsID=ir.classID
                where mi.pid = {$rid} AND mi.materialid = '{$wafer}' and ir.ClassId in (" . join(',', collect($aoi_class_ids_zero)->pluck('clsid')->toArray()) . ")
                group by  mi.materialid , ir.ClassId , DieRow ,diecol,ci.defectname,ci.caqdefectname ,mi.destslot
                order by mi.MaterialId ");

                if(empty($zero_defects)) {
                    $this->rejection = Rejection::where('name', $zero_defects[0]->caqdefectname)->first()->id ?? 6;
                } else {
                    /*** TODO: finish max defects > 0 ***/
                    $aoi_class_ids_more = DB::connection('sqlsrv_aoi')->select("select fc.clsid,MaxDefect,fc.MaxForDublette,cls.indie,cls.outerdie, cls.inouterdie
                    from FormatClassId fc
                    inner join formate FM on FM.id=fc.formatid
                    inner join classid cls on cls.ClsId = fc.ClsId
                    where fc.maxdefect>0 and  fc.MaxDefect <1000 and fm.formatname='{$format}'
                    order by cls.inouterdie desc");

                    if(!empty($aoi_class_ids_more)) {
                        foreach($aoi_class_ids_more as $am) {
                            $wafers_found_num = DB::connection('sqlsrv_aoi')->select("select * from
                            (
                            select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
                            from PInspectionResult IR
                            inner join PMaterialInfo MI on MI.rid=ir.pid
                            Inner Join ClassID CI on CI.clsID=ir.classID
                            where mi.pid={$rid} and mi.materialid = '{$wafer}' and ir.ClassId={$am->clsid}
                            group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
                             ) Df
                             where DefectCount > {$am->MaxDefect}
                             order by MaterialId");

                            dd($wafers_found_num);

                            if(!empty($wafers_found)) {

                            }
                        }
                    }
                }
            }
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
            $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->orderId)->where('wafer_id', 'like', "%{$this->selectedWafer}%")->where('reworked', false)->with('wafer')->lazy();
        else
            $sWafers = [];

        return view('livewire.blocks.microscope-aoi', compact('block', 'wafers', 'rejections', 'sWafers'));
    }
}
