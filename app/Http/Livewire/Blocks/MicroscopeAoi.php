<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Format;
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
            $wafer = Process::where('block_id', $this->prevBlock)->where('order_id', $this->orderId)->where('wafer_id',  $scan->value)->where('reworked', false)->first();
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

        if($this->rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        if(!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

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

                $format = explode('REVIEW', $aoi_data_xyz[0]->programname)[0] ?? null;
                $rid = $aoi_data_xyz[0]->rid ?? null;

                if($aoi_data_xyz[1]->Distance == null && $aoi_data_xyz[0]->Distance == null && $aoi_data_xyz[2]->Distance == null) {
                    $this->addError('xyz', 'Bitte nachmessen!');
                } else {
                    $this->x = $aoi_data_xyz[1]->Distance ?? 0;
                    $this->y = $aoi_data_xyz[0]->Distance ?? 0;
                    $this->z = $aoi_data_xyz[2]->Distance ?? 0;

                    if($format != null) {
                        $limits = Format::where('name', $format)->first();

                        if($this->x < $limits->min || $this->x > $limits->max) {
                            $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                            return false;
                        }

                        if($this->y < $limits->min || $this->y > $limits->max) {
                            $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                            return false;
                        }

                        if($this->z < $limits->min || $this->z > $limits->max) {
                            $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                            return false;
                        }
                    } else {
                        $this->addError('xyz', 'Format konnte nicht in AOI Datenbank gefunden werden!');
                    }
                }

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

                if(!empty($zero_defects)) {
                    $this->rejection = Rejection::where('name', $zero_defects[0]->caqdefectname)->first()->id ?? 6;
                    return false;
                } else {
                    $aoi_class_ids_more = DB::connection('sqlsrv_aoi')->select("select fc.clsid,MaxDefect,fc.MaxForDublette,cls.indie,cls.outerdie, cls.inouterdie
                    from FormatClassId fc
                    inner join formate FM on FM.id=fc.formatid
                    inner join classid cls on cls.ClsId = fc.ClsId
                    where fc.maxdefect>0 and  fc.MaxDefect <1000 and fm.formatname='{$format}'
                    order by cls.inouterdie desc");

                    if(!empty($aoi_class_ids_more)) {
                        foreach($aoi_class_ids_more as $am) {
                            if($am->indie)
                                $this->calculateInDie();

                            if($am->outerdie)
                                $this->calculateOuterDie();

                            if($am->inouterdie)
                                if($this->calculateDefectsInAndOuterDie($rid, $wafer, $am))
                                    return false;
                        }
                    }
                }
            }

            $this->rejection = 6;
        }
    }

    public function calculateDefectsInDie($rid, $wafer, $cls) {
        $wafers_found = DB::connection('sqlsrv_aoi')->select("select * from
        (
        select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
        from PInspectionResult IR
        inner join PMaterialInfo MI on MI.rid=ir.pid
        Inner Join ClassID CI on CI.clsID=ir.classID
        where mi.pid={$rid} and mi.materialid = '{$wafer}' and ir.ClassId={$cls->clsid}
        group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
         ) Df
         where DefectCount > {$cls->MaxDefect}
         order by MaterialId");

        if(!empty($wafers_found)) {
            foreach($wafers_found as $w) {
                $points_found = DB::connection('sqlsrv_aoi')->select("Select * from (select dierow,diecol ,count(ir.rid) as defects
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and ir.dierow>-1 and  mi.MaterialId ='{$wafer}' and mi.pid={$rid}
                 group by dierow,DieCol ) T1 where T1.defects>{$cls->MaxDefect}");

                if(!empty($points_found)) {
                    foreach($points_found as $point) {
                        $dieX = $point->diecol;
                        $dieY = $point->dierow;

                        $dies = DB::connection('sqlsrv_aoi')->select("  select  ir.coordxrel as xRel,ir.coordyrel as yRel, coordx as Xabs,coordy as Yabs
                        from PInspectionResult IR
                        inner join PMaterialInfo MI on MI.rid=IR.pid
                        inner join classid cls on cls.ClsId = ir.ClassId
                         where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' and mi.pid={$rid} and ir.DieRow = {$dieY} and diecol= {$dieX}");

                        if(!empty($dies)) {
                            $relPoints = [];
                            $absPoints = [];
                            foreach($dies as $die) {
                                $relPoints[] = (object) [
                                    'x' => $die->xRel,
                                    'y' => $die->yRel
                                ];
                                $absPoints[] = (object) [
                                    'x' => $die->Xabs,
                                    'y' => $die->Yabs
                                ];
                            }

                            $restPointsRel = $this->removeDublettes($relPoints, $cls->MaxForDublette);

                            if(sizeof($restPointsRel) > $cls->MaxDefect) {
                                $restPointsAbs = $this->removeDublettes($absPoints, $cls->maxForDublettes);

                                if($restPointsAbs != $restPointsRel) {
                                    return false;
                                }

                                $structureDef = (object) [
                                    'maxDefects' => $cls->MaxDefect,
                                    'errorPointsCount' => 0,
                                    'errorPoints' => [],
                                    'defectStructureCount' => 0,
                                    'defectCount' => []
                                ];

                                $structureDef = $this->checkPoints(true, $relPoints, $absPoints, $structureDef);

                                if($structureDef->defectStructureCount > 0) {
                                    $this->rejection = Rejection::where('name', $w->caqdefectname)->first()->id ?? 6;
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function calculateDefectsOuterDie() {

    }

    public function calculateDefectsInAndOuterDie($rid, $wafer, $cls) {
        $wafers_found = DB::connection('sqlsrv_aoi')->select("select * from
        (
        select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
        from PInspectionResult IR
        inner join PMaterialInfo MI on MI.rid=ir.pid
        Inner Join ClassID CI on CI.clsID=ir.classID
        where mi.pid={$rid} and mi.materialid = '{$wafer}' and ir.ClassId={$cls->clsid}
        group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
         ) Df
         where DefectCount > {$cls->MaxDefect}
         order by MaterialId");

        if(!empty($wafers_found)) {
            foreach($wafers_found as $w) {
                $points_found = DB::connection('sqlsrv_aoi')->select("select ir.classid, ir.x,ir.y,ir.dierow,ir.diecol,mi.DestSlot ,cls.DefectName ,cls.CAQDefectName ,
                (case when dierow<0 then 0 else 1 end) as IsDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' and mi.pid={$rid} ");

                if(!empty($points_found)) {
                    $points = [];
                    foreach($points_found as $point) {
                        $points[] = (object) [
                            'x' => $point->x,
                            'y' => $point->y
                        ];
                    }

                    $rest_points = $this->removeDublettes($points, $cls->MaxForDublette);

                    if(sizeof($rest_points) > $cls->MaxDefect) {
                        $this->rejection = Rejection::where('name', $w->caqdefectname)->first()->id ?? 6;
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function checkPoints($polyRel, $relPoints, $absPoints, $structureDef) {
        if(sizeof($relPoints) > 1)
            return false;

        $polygons = [];
        /*if($polyRel)
            //rel
        else
            //abs*/

        $structureDef = $this->assignPolygons($polygons, $relPoints, $absPoints, $structureDef);

        return $structureDef;
    }

    public function assignPolygons($polygons, $relPoints, $absPoints, $structureDef) {
        $structsFound = [];


        $pointsCount = 0;
        foreach($relPoints as $point) {
            $structCount = 0;
            $found = false;

            do {
                if($this->pinPoly($polygons[$structCount], $point)) {
                    $structsFound[$structCount] = $structsFound[$structCount] + 1;
                    $found = true;
                }
            } while(!$found || $structCount > sizeof($polygons));

            $structureDef->errorPointsCount++;
            $structureDef->errorPoints[$structureDef->errorPointsCount] = $absPoints[$pointsCount];

            $pointsCount++;
        }

        foreach($structsFound as $struct) {
            if($struct > $structureDef->maxDefects) {
                $structureDef->defectStructureCount++;
                $structureDef->defectCount[$structureDef->defectStructureCount] = $struct;
            }
        }

        return $structureDef;
    }

    public function pinPoly($polygon, $point) {
        $coordCount = 0;
        $sidesCrossed = 0;
        foreach($polygon->coords as $coord) {
            if($coord->x > $point->x xor $polygon->coords[$coordCount + 1]->y > $point->y) {
                $m = ($polygon->coords[$coordCount + 1]-> y - $coord->y) / ($polygon->coords[$coordCount + 1]->x - $coord->x);
                $b = ($coord->y * $polygon->coords[$coordCount + 1]->x - $coord->x * $polygon->coords[$coordCount + 1]->y) / ($polygon->coords[$coordCount + 1]->x - $coord->x);

                if($m * $point->x + $b > $point->y)
                    $sidesCrossed++;
            }

            $coordCount++;
        }

        return $sidesCrossed % 2;
    }

    public function removeDublettes($points, $maxDist) {
        $matrix = [];
        $sumList = [];

        for($i = 0; $i < sizeof($points);$i++) {
            $tSum = 0;
            for($j = 0; $j < sizeof($points);$j++) {
                $matrix[$i][$j] = sqrt(($points[$i]->x - $points[$j]->x) ^ 2 + ($points[$i]->y - $points[$j]->y) ^2);
                $matrix[$i][$j] = $matrix[$i][$j] > $maxDist ? 0 : 1;
                if($i != $j && $matrix[$i][$j] > 0) {
                    $tSum += $matrix[$i][$j];
                }
            }
            $sumList[$i] = $tSum;
        }

        $p = 0;
        do {
            $p = max(array_keys($sumList));
            if($p > 0) {

            }
        } while($p < 1);
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
