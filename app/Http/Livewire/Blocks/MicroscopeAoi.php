<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Order;
use App\Models\Data\Process;
use App\Models\Data\Scan;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Format;
use App\Models\Generic\Rejection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\Pure;
use Livewire\Component;

class MicroscopeAoi extends Component
{
    public $blockId;
    public $orderId;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $searchField = 'wafer_id';
    public $ar_box = null;
    public $box = null;
    public $x = null;
    public $y = null;
    public $z = null;
    public $cdo = null;
    public $cdu = null;
    public $format = null;

    public $aoi_type = 'sqlsrv_aoi2';

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

        $box = Process::where('block_id', $this->blockId)->where('ar_box', $this->ar_box)->with('order')->limit(1)->first();
        if($box != null) {
            $order = Order::find($this->orderId);
            if($order->article != $box->order->article) {
                $this->addError('ar_box', 'In dieser Box wurden Wafer mit einer anderen Artikelnummer verwendet!');
                return false;
            }
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

        if($this->ar_box == '') {
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
            'ar_box' => $this->ar_box,
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

        $this->selectedWafer = '';
        $this->x = '';
        $this->y = '';
        $this->z = '';
        $this->cdo = '';
        $this->cdu = '';
        $this->rejection = 6;
        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
        $this->dispatchBrowserEvent('saved');
    }

    public function updateEntry($entryId, $operator, $ar_box, $rejection, $x, $y, $z, $cdo, $cdu) {
        if($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        if($ar_box == '') {
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
            'ar_box' => $ar_box,
            'rejection_id' => $rejection->id,
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'cd_ol' => $cdo,
            'cd_ur' => $cdu
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

            $aoi_cd = \DB::connection($this->aoi_type)->select("SELECT max(pairwidth1) as cdo, max(pairwidth2) as cdu FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' AND Tool LIKE ('critical dimension')
            GROUP BY destslot
            ORDER BY DestSlot");

            if(empty($aoi_cd)) {
                $this->aoi_type = 'sqlsrv_aoi';
                $aoi_cd = \DB::connection($this->aoi_type)->select("SELECT max(pairwidth1) as cdo, max(pairwidth2) as cdu FROM pmaterialinfo
                INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
                INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
                WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' AND Tool LIKE ('critical dimension')
                GROUP BY destslot
                ORDER BY DestSlot");
            }

            $aoi_data_xyz = \DB::connection($this->aoi_type)->select("SELECT TOP 3 pproductiondata.rid, Distance, pproductiondata.name, pproductiondata.programname FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' ORDER BY RId DESC");



            if(!empty($aoi_data_xyz)) {
                $this->cdo = $aoi_cd[0]->cdo ?? null;
                $this->cdu = $aoi_cd[0]->cdu ?? null;

                $format = explode('REVIEW', $aoi_data_xyz[0]->programname)[0] ?? null;
                $this->format = $format;
                $rid = $aoi_data_xyz[0]->rid ?? null;

                if($aoi_data_xyz[1]->Distance == null && $aoi_data_xyz[0]->Distance == null && $aoi_data_xyz[2]->Distance == null) {
                    $this->addError('xyz', 'Bitte nachmessen!');
                    return false;
                } else {
                    if($this->aoi_type == 'sqlsrv_aoi') {
                        $this->x = $aoi_data_xyz[1]->Distance ?? 0;
                        $this->y = $aoi_data_xyz[2]->Distance ?? 0;
                        $this->z = $aoi_data_xyz[0]->Distance ?? 0;
                    } else {
                        $this->x = $aoi_data_xyz[1]->Distance ?? 0;
                        $this->y = $aoi_data_xyz[0]->Distance ?? 0;
                        $this->z = $aoi_data_xyz[2]->Distance ?? 0;
                    }

                    if($format != null) {
                        $limits = Format::where('name', $format)->first();

                        if($limits != null) {
                            if($this->x <= $limits->min || $this->x >= $limits->max) {
                                $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                                return false;
                            }

                            if($this->y <= $limits->min || $this->y >= $limits->max) {
                                $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                                return false;
                            }

                            if($this->aoi_type == 'sqlsrv_aoi2' && ($this->z <= $limits->min || $this->z >= $limits->max)) {
                                $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                                return false;
                            }
                        } else {
                            $this->addError('xyz', 'Format konnte nicht in der Datenbank gefunden werden!');
                            return false;
                        }
                    } else {
                        $this->addError('xyz', 'Format konnte nicht in AOI Datenbank gefunden werden!');
                        return false;
                    }
                }

                $aoi_class_ids_zero = DB::connection($this->aoi_type)->select("SELECT clsid from formatclassid
                INNER JOIN formate on formatclassid.formatid = formate.id
                WHERE formatclassid.maxdefect = 0 AND formate.formatname = '{$format}'");

                $zero_defects = DB::connection($this->aoi_type)->select("select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,DieRow ,diecol,ci.defectname,ci.caqdefectname,
                (case when DieRow<0 then 0 else 1 end) as isDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=ir.pid
                Inner Join ClassID CI on CI.clsID=ir.classID
                where mi.pid = {$rid} AND mi.materialid = '{$wafer}' AND mi.LotId = '{$this->box}' and ir.ClassId in (" . join(',', collect($aoi_class_ids_zero)->pluck('clsid')->toArray()) . ")
                group by  mi.materialid , ir.ClassId , DieRow ,diecol,ci.defectname,ci.caqdefectname ,mi.destslot
                order by mi.MaterialId ");

                if(!empty($zero_defects)) {
                    $this->rejection = Rejection::where('name', $zero_defects[0]->caqdefectname)->first()->id ?? 6;
                    return false;
                } else {
                    $aoi_class_ids_more = DB::connection($this->aoi_type)->select("select fc.clsid,MaxDefect,fc.MaxForDublette,cls.indie,cls.outerdie, cls.inouterdie
                    from FormatClassId fc
                    inner join formate FM on FM.id=fc.formatid
                    inner join classid cls on cls.ClsId = fc.ClsId
                    where fc.maxdefect>0 and  fc.MaxDefect <1000 and fm.formatname='{$format}'
                    order by cls.inouterdie desc");

                    if(!empty($aoi_class_ids_more)) {
                        foreach($aoi_class_ids_more as $am) {
                            if($am->indie)
                                if($this->calculateDefectsInDie($rid, $wafer, $am, $format))
                                    return false;

                            if($am->outerdie)
                                if($this->calculateDefectsOuterDie($rid, $wafer, $am, $format))
                                    return false;

                            if($am->inouterdie)
                                if($this->calculateDefectsInAndOuterDie($rid, $wafer, $am))
                                    return false;
                        }
                    }
                }
            } else {
                $this->x = 0;
                $this->y = 0;
                $this->z = 0;
                $this->addError('xyz', "Konnte keine XYZ Daten in AOI Datenbank finden");
                return false;
            }

            $this->rejection = 6;
        }
    }

    public function calculateDefectsInDie(int $rid, string $wafer, object $cls, string $format) : bool {
        $wafers_found = DB::connection($this->aoi_type)->select("select * from
        (
        select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
        from PInspectionResult IR
        inner join PMaterialInfo MI on MI.rid=ir.pid
        Inner Join ClassID CI on CI.clsID=ir.classID
        where mi.pid={$rid} and mi.materialid = '{$wafer}' AND mi.LotId = '{$this->box}' and ir.ClassId={$cls->clsid}
        group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
         ) Df
         where DefectCount > {$cls->MaxDefect}
         order by MaterialId");

        if(!empty($wafers_found)) {
            foreach($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("Select * from (select dierow,diecol ,count(ir.rid) as defects
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and ir.dierow>-1 and  mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid}
                 group by dierow,DieCol ) T1 where T1.defects>{$cls->MaxDefect}");

                if(!empty($points_found)) {
                    foreach($points_found as $point) {
                        $dieX = $point->diecol;
                        $dieY = $point->dierow;

                        $dies = DB::connection($this->aoi_type)->select("  select  ir.coordxrel as xRel,ir.coordyrel as yRel, coordx as Xabs,coordy as Yabs
                        from PInspectionResult IR
                        inner join PMaterialInfo MI on MI.rid=IR.pid
                        inner join classid cls on cls.ClsId = ir.ClassId
                         where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} and ir.DieRow = {$dieY} and diecol= {$dieX}");

                        if(!empty($dies)) {
                            $relPoints = [ (object) [
                                    'x' => 0,
                                    'y' => 0
                                ]
                            ];
                            $absPoints = [ (object) [
                                    'x' => 0,
                                    'y' => 0
                                ]
                            ];
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

                            $restPoints = (object) [
                                'rel' => [],
                                'abs' => []
                            ];
                            $restPoints->rel = $this->removeDublettes($relPoints, $cls->MaxForDublette);

                            if(sizeof($restPoints->rel) > $cls->MaxDefect) {
                                $restPoints->abs = $this->removeDublettes($absPoints, $cls->MaxForDublette);

                                if(sizeof($restPoints->abs) != sizeof($restPoints->rel))
                                    return false;

                                $structureDef = (object) [
                                    'maxDefects' => $cls->MaxDefect,
                                    'errorPointsCount' => 0,
                                    'errorPoints' => [],
                                    'defectStructureCount' => 0,
                                    'defectStructureName' => [],
                                    'defectCount' => []
                                ];

                                $structureDef = $this->checkPoints(true, $restPoints, $structureDef, $format);

                                if($structureDef == null) {
                                    $this->addError('xyz', 'Format Strukturen konnten nicht gefunden werden!');
                                    return false;
                                }

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

    public function calculateDefectsOuterDie(int $rid, string $wafer, object $cls, string $format) {
        $wafers_found = DB::connection($this->aoi_type)->select("select * from
        (
        select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
        from PInspectionResult IR
        inner join PMaterialInfo MI on MI.rid=ir.pid
        Inner Join ClassID CI on CI.clsID=ir.classID
        where mi.pid={$rid} and mi.materialid = '{$wafer}' AND mi.LotId = '{$this->box}' and ir.ClassId={$cls->clsid} and ir.dierow < 0
        group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
         ) Df
         where DefectCount > {$cls->MaxDefect}
         order by MaterialId");

        if(!empty($wafers_found)) {
            foreach($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("select ir.classid, ir.x,ir.y,ir.dierow,ir.diecol,mi.DestSlot ,cls.DefectName ,cls.CAQDefectName ,
                (case when dierow<0 then 0 else 1 end) as IsDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and ir.dierow < 0 and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} ");

                if(!empty($points_found)) {
                    $points = [];
                    foreach($points_found as $point) {
                        $points[] = (object) [
                            'x' => $point->x,
                            'y' => $point->y
                        ];

                        $restPoints = (object) [
                            'rel' => [],
                            'abs' => []
                        ];
                        $restPoints->abs = $this->removeDublettes($points, $cls->MaxForDublette);

                        if(sizeof($restPoints->abs) > $cls->MaxDefect) {
                            $structureDef = (object) [
                                'maxDefects' => $cls->MaxDefect,
                                'errorPointsCount' => 0,
                                'errorPoints' => [],
                                'defectStructureCount' => 0,
                                'defectStructureName' => [],
                                'defectCount' => []
                            ];

                            $structureDef = $this->checkPoints(true, $restPoints, $structureDef, $format);

                            if($structureDef->defectStructureCount > 0) {
                                $this->rejection = Rejection::where('name', $w->caqdefectname)->first()->id ?? 6;
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function calculateDefectsInAndOuterDie(int $rid, string $wafer, object $cls) :bool {
        $wafers_found = DB::connection($this->aoi_type)->select("select * from
        (
        select mi.materialid ,mi.destslot, ir.ClassId , count(ir.rid) DefectCount,ci.defectname,ci.caqdefectname
        from PInspectionResult IR
        inner join PMaterialInfo MI on MI.rid=ir.pid
        Inner Join ClassID CI on CI.clsID=ir.classID
        where mi.pid={$rid} and mi.materialid = '{$wafer}' AND mi.LotId = '{$this->box}' and ir.ClassId={$cls->clsid}
        group by  mi.materialid , ir.ClassId , ci.defectname,ci.caqdefectname ,mi.destslot
         ) Df
         where DefectCount > {$cls->MaxDefect}
         order by MaterialId");

        if(!empty($wafers_found)) {
            foreach($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("select ir.classid, ir.x,ir.y,ir.dierow,ir.diecol,mi.DestSlot ,cls.DefectName ,cls.CAQDefectName ,
                (case when dierow<0 then 0 else 1 end) as IsDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} ");

                if(!empty($points_found)) {
                    $points = [];
                    foreach($points_found as $point) {
                        $points[] = (object) [
                            'x' => $point->x,
                            'y' => $point->y
                        ];
                    }

                    $restPoints = $this->removeDublettes($points, $cls->MaxForDublette);

                    if(sizeof($restPoints) > $cls->MaxDefect) {
                        $this->rejection = Rejection::where('name', $w->caqdefectname)->first()->id ?? 6;
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function loadFormat(string $format) : object {
        $structuresRel = DB::connection($this->aoi_type)->select("select ST.id, st.StructurName ,x,y from Structure ST inner join Formate FM on FM.id=st.FormatID
        where fm.FormatName ='{$format}' and relabs='rel'");

        $relStructures = [];
        if(!empty($structuresRel)) {
            for($i = 0; $i < sizeof($structuresRel); $i++) {
                $relStructures[] = (object) [
                    'id' => $structuresRel[$i]->id,
                    'structureName' => $structuresRel[$i]->StructurName,
                    'coords' => []
                ];

                $xList = explode(';', $structuresRel[$i]->x);
                $yList = explode(';', $structuresRel[$i]->y);
                $relStructures[$i]->points = sizeof($xList);


                for($j = 0; $j < sizeof($xList); $j++) {
                    $relStructures[$i]->coords[] = (object) [
                        'x' => $xList[$j],
                        'y' => $yList[$j]
                    ];
                }
                $relStructures[$i]->coords[] = $relStructures[$i]->coords[0];
            }
        }

        $structuresAbs = DB::connection($this->aoi_type)->select("select st.id, st.StructurName ,x,y from Structure ST inner join Formate FM on FM.id=st.FormatID
        where fm.FormatName ='{$format}' and relabs='abs'");

        $absStructures = [];
        if(!empty($structuresAbs)) {
            for($i = 0; $i < sizeof($structuresAbs); $i++) {
                $absStructures[] = (object) [

                    'id' => $structuresAbs[$i]->id,
                    'structureName' => $structuresAbs[$i]->StructurName,
                    'coords' => []
                ];

                $xList = explode(';', $structuresAbs[$i]->x);
                $yList = explode(';', $structuresAbs[$i]->y);
                $absStructures[$i]->points = sizeof($xList);

                $j = 0;
                for($j; $j < sizeof($xList); $j++) {
                    $absStructures[$i]->coords[$j] = (object) [
                        'x' => $xList[$j],
                        'y' => $yList[$j]
                    ];
                }
                $absStructures[$i]->coords[] = $absStructures[$i]->coords[0];
            }
        }

        return (object) [
            'rel' => $relStructures,
            'abs' => $absStructures
        ];
    }

    public function checkPoints(bool $polyRel, object $points, object $structureDef, string $format) {
        if(sizeof($points->rel) < 1 && sizeof($points->abs) < 1)
            return null;

        $formatStructures = $this->loadFormat($format);

        if($polyRel)
            $structures = $formatStructures->rel;
        else
            $structures = $formatStructures->abs;

        $structureDef = $this->assignPolygons($structures, $points, $structureDef);

        if($polyRel) {
            if($structureDef->errorPointsCount > $structureDef->maxDefects) {
                $structures = $formatStructures->abs;
                $points->abs = array_fill(1, $structureDef->errorPointsCount, 0);
                foreach($structureDef->errorPoints as $key => $errorPoint) {
                    $points->abs[$key] = $structureDef->errorPoints[$key];
                }
                $structureDef->errorPoints = [];
                $structureDef->errorPointsCount = 0;
                $structureDef = $this->assignPolygons($structures, $points, $structureDef);
            }
        }

        return $structureDef;
    }

    public function assignPolygons(array $polygons, object $points, object $structureDef) {
        $structsFound = array_fill(0, sizeof($polygons), 0);
        foreach($points->rel as $key => $point) {
            $structCount = 0;
            $found = false;

            for($i = 0; $i < sizeof($polygons); $i++) {
                if ($this->pinPoly($polygons[$i], $point)) {
                    $structsFound[$i] = $structsFound[$i] + 1;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $structureDef->errorPointsCount++;
                $structureDef->errorPoints[$structureDef->errorPointsCount] = $points->abs[$key];
            }
        }

        foreach($structsFound as $key => $struct) {
            if($struct > $structureDef->maxDefects) {
                $structureDef->defectStructureCount++;
                $structureDef->defectStructureName[$structureDef->defectStructureCount] = $polygons[$key]->structureName;
                $structureDef->defectCount[$structureDef->defectStructureCount] = $struct;
            }
        }

        return $structureDef;
    }

    public function pinPoly(object $polygon, object $point) : int {
        $sidesCrossed = 0;
        for($i = 0; $i < sizeof($polygon->coords) - 1;$i++) {
            if($polygon->coords[$i]->x > $point->x xor $polygon->coords[$i + 1]->x > $point->x) {
                $m = ($polygon->coords[$i + 1]->y - $polygon->coords[$i]->y) / ($polygon->coords[$i + 1]->x - $polygon->coords[$i]->x);
                $b = ($polygon->coords[$i]->y * $polygon->coords[$i + 1]->x - $polygon->coords[$i]->x * $polygon->coords[$i + 1]->y) / ($polygon->coords[$i + 1]->x - $polygon->coords[$i]->x);

                if($m * $point->x + $b > $point->y)
                    $sidesCrossed++;
            }
        }

        return $sidesCrossed % 2;
    }

    public function findMaxKeyInArray(array $array) : int {
        $max = 0;
        $retKey = 0;
        foreach($array as $key => $value) {
            if($value > $max) {
                $max = $value;
                if($max > 0) $retKey = $key;
            }
        }

        return $retKey;
    }

    public function removeDublettes(array $points, float $maxDist) : array {
        $matrix = [];
        $sumList = [];
        $tmpList = [];

        for($i = 1; $i < sizeof($points);$i++) {
            $tSum = 0;
            $tmp = '';
            $add = '';
            for($j = 1; $j < sizeof($points);$j++) {
                $matrix[$i][$j] = sqrt(pow(($points[$i]->x - $points[$j]->x), 2) + pow(($points[$i]->y - $points[$j]->y), 2));
                $matrix[$i][$j] = $matrix[$i][$j] > $maxDist ? 0 : 1;

                if($i != $j && $matrix[$i][$j] > 0) {
                    $tSum += $matrix[$i][$j];
                    $tmp = $tmp . $add . $j;
                    $add = ';';
                }
            }
            $sumList[$i] = $tSum;
            $tmpList[$i] = $tmp;
        }

        do {
            $p = $this->findMaxKeyInArray($sumList);

            if($p > 0) {
                $cols = explode(';', $tmpList[$p]);
                for($i = 0; $i < sizeof($cols); $i++)
                    $sumList[$cols[$i]] = -$p;
                $sumList[$p] = 0;
            }

        } while($p >= 1);

        $restCount = 0;
        $tmpPoints = [];
        for($i = 1; $i <= sizeof($sumList); $i++) {
            if($sumList[$i] == 0) {
                $restCount++;
                $tmpPoints[$restCount] = $points[$i];
            }
        }

        return $tmpPoints;
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
            $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->orderId)->where(function ($query) {
                $query->where('wafer_id', $this->selectedWafer)->orWhere('wafer_id', $this->selectedWafer . '-r');
            })->orderBy('wafer_id', 'desc')->with('wafer')->get();

            if($sWafers->count() > 0) {
                $this->updateWafer($sWafers->get(0)->wafer_id, $sWafers->get(0)->box);
            }
        } else
            $sWafers = collect([]);

        return view('livewire.blocks.microscope-aoi', compact('block', 'wafers', 'rejections', 'sWafers'));
    }
}
