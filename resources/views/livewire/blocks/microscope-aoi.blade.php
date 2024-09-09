<?php

use App\Models\Data\Wafer;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use App\Models\Data\Process;
use App\Models\Generic\Rejection;
use App\Models\Data\Serial;
use App\Models\Generic\Format;
use Illuminate\Support\Facades\DB;

new class extends \Livewire\Volt\Component {
    public $block;
    public $order;
    public $prevBlock;
    public $nextBlock;

    // Form Values
    public $search = '';
    public $searchField = 'wafer_id';
    public $ar_box = null;
    public string $box = '';
    public $x = null;
    public $y = null;
    public $z = null;
    public $cdo = null;
    public $cdu = null;
    public $format = null;

    public $aoi_type = 'sqlsrv_aoi2';
    public $machine = '';

    public $selectedWafer = null;
    public $rejection = 6;

    public $saved = false;

    public function mount()
    {
        $blockInfo = BlockHelper::getPrevAndNextBlock($this->order, $this->block->id);
        $this->prevBlock = $blockInfo->prev;
        $this->nextBlock = $blockInfo->next;
    }

    public function checkWafer($waferId)
    {
        if ($waferId == '') {
            $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
            return false;
        }

        $wafer = Wafer::find($waferId);

        if ($wafer == null) {
            $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
            return false;
        }

        if ($wafer->rejected) {
            if ($this->nextBlock != null) {
                $nextWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->nextBlock)->first();
                if ($nextWafer == null) {
                    $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                    return false;
                }
            } else {
                $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                return false;
            }
        }

        if ($wafer->reworked) {
            $this->addError('wafer', "Dieser Wafer wurde nachbearbeitet und kann nicht mehr verwendet werden!");
            return false;
        }

        if ($this->prevBlock != null) {
            $prevWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->prevBlock)->first();
            if ($prevWafer == null) {
                $this->addError('wafer', 'Dieser Wafer existiert nicht im vorherigen Schritt!');
                return false;
            }
        }

        if (Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->block->id)->exists()) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            return false;
        }

        $box = Process::where('block_id', $this->block->id)->where('ar_box', $this->ar_box)->with('order')->limit(1)->first();
        if ($box != null) {
            if ($this->order->article != $box->order->article) {
                $this->addError('ar_box', 'In dieser Box wurden Wafer mit einer anderen Artikelnummer verwendet!');
                return false;
            }
        }

        return true;
    }

    public function addEntry($operator, $rework = false)
    {
        $this->resetErrorBag(['ar_box', 'operator', 'rejection', 'wafer']);
        $this->saved = true;
        $error = false;

        if ($operator == '') {
            $this->addError('operator', 'Der Operator darf nicht leer sein!');
            $error = true;
        }

        if ($this->ar_box == '') {
            $this->addError('ar_box', 'Die Box ID Darf nicht leer sein!');
            $error = true;
        }

        if ($this->rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund angegeben werden!');
            $error = true;
        }

        $cdNio = Rejection::where('name', 'CD n.i.O')->first()->id;

        if(($this->cdo > 0 && $this->cdo < 4) || $this->cdo > 6) {
            $this->rejection = $cdNio ?? 6;
        }

        if(($this->cdu > 0 && $this->cdu < 4) || $this->cdu > 6) {
            $this->rejection = $cdNio ?? 6;
        }

        if ($error)
            return false;

        if (!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

        $rejection = Rejection::find($this->rejection);

        $process = Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $this->order->id,
            'block_id' => $this->block->id,
            'rejection_id' => $rejection->id,
            'operator' => $operator,
            'ar_box' => $this->ar_box,
            'box' => $this->box,
            'machine' => $this->machine,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'cd_ol' => $this->cdo,
            'cd_ur' => $this->cdu,
            'date' => now()
        ]);

        if ($rejection->reject) {
            Wafer::find($this->selectedWafer)->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $this->block->name,
                'rejection_avo' => $this->block->avo,
                'rejection_order' => $this->order->id
            ]);
        }

        if ($rework)
            $this->rework($process);

        $this->selectedWafer = '';
        $this->x = '';
        $this->y = '';
        $this->z = '';
        $this->cdo = '';
        $this->cdu = '';
        $this->machine = '';
        $this->rejection = 6;
        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
        $this->dispatch('saved');
    }

    public function updateEntry($entryId, $operator, $ar_box, $rejection, $x, $y, $z, $cdo, $cdu)
    {
        if ($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        if ($ar_box == '') {
            $this->addError('edit' . $entryId, 'Box darf nicht leer sein!');
            return false;
        }

        $rejection = Rejection::find($rejection);
        $process = Process::find($entryId);
        $wafer = Wafer::find($process->wafer_id);

        if ($wafer->rejected && $rejection->reject && $rejection->id != $process->rejection_id && !$process->rejection->reject) {
            $this->addError('edit' . $entryId, "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
            return false;
        }

        if ($rejection->reject) {
            $wafer->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $this->block->name,
                'rejection_avo' => $this->block->avo,
                'rejection_order' => $this->order->id
            ]);
        } else {
            if($wafer->rejection_avo == $this->block->avo && $wafer->rejection_order == $this->order->id) {
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

        if ($process->reworked) {
            Wafer::find($process->wafer_id)->update([
                'reworked' => false
            ]);
        }

        $process->delete();
    }

    public function clear()
    {
        $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('wafer');

        foreach ($wafers->lazy() as $wafer) {
            if ($wafer->rejection != null) {
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

    public function rework(Process $process)
    {
        $rWafer = Wafer::find($process->wafer_id . '-r');
        if ($rWafer != null) {
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

    public function updated($name)
    {
        if ($name == 'box') {
            if (str_ends_with($this->selectedWafer, '-r'))
                $wafer = str_replace('-r', '', $this->selectedWafer);
            else
                $wafer = $this->selectedWafer;

            $aoi_cd = \DB::connection($this->aoi_type)->select("SELECT pairwidth1, pairwidth2 FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' AND Tool LIKE ('critical dimension')
            ORDER BY DestSlot");

            if (empty($aoi_cd)) {
                $this->aoi_type = 'sqlsrv_aoi';
                $aoi_cd = \DB::connection($this->aoi_type)->select("SELECT pairwidth1, pairwidth2 FROM pmaterialinfo
                INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
                INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
                WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' AND Tool LIKE ('critical dimension')
                ORDER BY DestSlot");

                if (!empty($aoi_cd)) {
                    $this->machine = 1;
                } else {
                    $this->machine = 'Manuell';
                }
            } else {
                $this->machine = 2;
            }

            $aoi_data_xyz = \DB::connection($this->aoi_type)->select("SELECT TOP 3 pproductiondata.rid, Distance, pproductiondata.name, pproductiondata.programname FROM pmaterialinfo
            INNER JOIN pinspectionresult ON pinspectionresult.PId = pmaterialinfo.RId
            INNER JOIN pproductiondata ON pproductiondata.RId = pmaterialinfo.PId
            WHERE MaterialId = '{$wafer}' AND LotId = '{$this->box}' ORDER BY RId DESC");

            if($this->machine != 'Manuell') {
                $hasCDError = false;
                if (!empty($aoi_data_xyz)) {
                    if (!empty($aoi_cd)) {
                        if (count($aoi_cd) >= 2 && (isset($aoi_cd[0]->pairwidth1) && isset($aoi_cd[0]->pairwidth2)
                                && isset($aoi_cd[1]->pairwidth1) && isset($aoi_cd[1]->pairwidth2))) {
                            $this->cdo = collect([$aoi_cd[0]->pairwidth1, $aoi_cd[0]->pairwidth2])->avg();
                            $this->cdu = collect([$aoi_cd[1]->pairwidth1, $aoi_cd[1]->pairwidth2])->avg();

                            if (($this->cdo > 0 && $this->cdo < 4) || $this->cdo > 6) {
                                $this->addError('cdo', 'CD OL Ausserhalb Toleranzgrenzen');
                                $this->rejection = Rejection::where('name', 'CD n.i.O')->first()->id ?? 6;
                                return false;
                            }

                            if (($this->cdu > 0 && $this->cdu < 4) || $this->cdu > 6) {
                                $this->addError('cdu', 'CD UR Ausserhalb Toleranzgrenzen');
                                $this->rejection = Rejection::where('name', 'CD n.i.O')->first()->id ?? 6;
                                return false;
                            }
                        } else {
                            $this->addError('cdo', 'Fehlerhafte CD Werte aus AOI');
                            $this->addError('cdu', 'Fehlerhafte CD Werte aus AOI');
                        }
                    }

                    $format = explode('REVIEW', $aoi_data_xyz[0]->programname)[0] ?? null;
                    $this->format = $format;
                    $rid = $aoi_data_xyz[0]->rid ?? null;

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

                    if (!empty($zero_defects)) {
                        $this->rejection = Rejection::where('name', $zero_defects[0]->caqdefectname)->first()->id ?? 6;
                        return false;
                    } else {
                        $aoi_class_ids_more = DB::connection($this->aoi_type)->select("select fc.clsid,MaxDefect,fc.MaxForDublette,cls.indie,cls.outerdie, cls.inouterdie
                    from FormatClassId fc
                    inner join formate FM on FM.id=fc.formatid
                    inner join classid cls on cls.ClsId = fc.ClsId
                    where fc.maxdefect>0 and  fc.MaxDefect <1000 and fm.formatname='{$format}'
                    order by cls.inouterdie desc");

                        if (!empty($aoi_class_ids_more)) {
                            foreach ($aoi_class_ids_more as $am) {
                                if ($am->indie)
                                    if ($this->calculateDefectsInDie($rid, $wafer, $am, $format))
                                        return false;

                                if ($am->outerdie)
                                    if ($this->calculateDefectsOuterDie($rid, $wafer, $am, $format))
                                        return false;

                                if ($am->inouterdie)
                                    if ($this->calculateDefectsInAndOuterDie($rid, $wafer, $am))
                                        return false;
                            }
                        }
                    }

                    $formatObj = Format::where('name', $format)->first();

                    if (!$formatObj->ignore_xyz) {
                        if ($aoi_data_xyz[1]->Distance == null && $aoi_data_xyz[0]->Distance == null && $aoi_data_xyz[2]->Distance == null) {
                            $this->addError('xyz', 'Bitte nachmessen!');
                            return false;
                        } else {
                            if ($this->aoi_type == 'sqlsrv_aoi') {
                                $this->x = $aoi_data_xyz[1]->Distance ?? 0;
                                $this->y = $aoi_data_xyz[2]->Distance ?? 0;
                                $this->z = $aoi_data_xyz[0]->Distance ?? 0;
                            } else {
                                $this->x = $aoi_data_xyz[1]->Distance ?? 0;
                                $this->y = $aoi_data_xyz[0]->Distance ?? 0;
                                $this->z = $aoi_data_xyz[2]->Distance ?? 0;
                            }

                            if ($format != null) {
                                $limits = $formatObj;

                                if ($limits != null) {
                                    if ($this->x > 0 && ($this->x <= $limits->min || $this->x >= $limits->max)) {
                                        $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                                        return false;
                                    }

                                    if ($this->y > 0 && ($this->y <= $limits->min || $this->y >= $limits->max)) {
                                        $this->rejection = Rejection::where('name', 'XYZ Ausschuss')->first()->id ?? 6;
                                        return false;
                                    }

                                    if ($this->aoi_type == 'sqlsrv_aoi2' && $this->z > 0 && ($this->z <= $limits->min || $this->z >= $limits->max)) {
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
                    } else {
                        $this->addError('xyz_not_calculated', "XYZ Daten werden für das Format {$formatObj->name} nicht geladen");
                    }
                } else {
                    $this->x = 0;
                    $this->y = 0;
                    $this->z = 0;
                    $this->addError('xyz', "Konnte keine XYZ Daten in AOI Datenbank finden");
                    return false;
                }
            } else {
                $this->x = 0;
                $this->y = 0;
                $this->z = 0;
            }

            $this->rejection = 6;
        }
    }

    public function calculateDefectsInDie(int $rid, string $wafer, object $cls, string $format): bool
    {
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

        if (!empty($wafers_found)) {
            foreach ($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("Select * from (select dierow,diecol ,count(ir.rid) as defects
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and ir.dierow>-1 and  mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid}
                 group by dierow,DieCol ) T1 where T1.defects>{$cls->MaxDefect}");

                if (!empty($points_found)) {
                    foreach ($points_found as $point) {
                        $dieX = $point->diecol;
                        $dieY = $point->dierow;

                        $dies = DB::connection($this->aoi_type)->select("  select  ir.coordxrel as xRel,ir.coordyrel as yRel, coordx as Xabs,coordy as Yabs
                        from PInspectionResult IR
                        inner join PMaterialInfo MI on MI.rid=IR.pid
                        inner join classid cls on cls.ClsId = ir.ClassId
                         where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} and ir.DieRow = {$dieY} and diecol= {$dieX}");

                        if (!empty($dies)) {
                            $relPoints = [(object)[
                                'x' => 0,
                                'y' => 0
                            ]
                            ];
                            $absPoints = [(object)[
                                'x' => 0,
                                'y' => 0
                            ]
                            ];
                            foreach ($dies as $die) {
                                $relPoints[] = (object)[
                                    'x' => $die->xRel,
                                    'y' => $die->yRel
                                ];
                                $absPoints[] = (object)[
                                    'x' => $die->Xabs,
                                    'y' => $die->Yabs
                                ];
                            }

                            $restPoints = (object)[
                                'rel' => [],
                                'abs' => []
                            ];
                            $restPoints->rel = $this->removeDublettes($relPoints, $cls->MaxForDublette);

                            if (sizeof($restPoints->rel) > $cls->MaxDefect) {
                                $restPoints->abs = $this->removeDublettes($absPoints, $cls->MaxForDublette);

                                if (sizeof($restPoints->abs) != sizeof($restPoints->rel))
                                    return false;

                                $structureDef = (object)[
                                    'maxDefects' => $cls->MaxDefect,
                                    'errorPointsCount' => 0,
                                    'errorPoints' => [],
                                    'defectStructureCount' => 0,
                                    'defectStructureName' => [],
                                    'defectCount' => []
                                ];

                                $structureDef = $this->checkPoints(true, $restPoints, $structureDef, $format);

                                if ($structureDef == null) {
                                    $this->addError('xyz', 'Format Strukturen konnten nicht gefunden werden!');
                                    return false;
                                }

                                if ($structureDef->defectStructureCount > 0) {
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

    public function calculateDefectsOuterDie(int $rid, string $wafer, object $cls, string $format)
    {
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

        if (!empty($wafers_found)) {
            foreach ($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("select ir.classid, ir.x,ir.y,ir.dierow,ir.diecol,mi.DestSlot ,cls.DefectName ,cls.CAQDefectName ,
                (case when dierow<0 then 0 else 1 end) as IsDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and ir.dierow < 0 and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} ");

                if (!empty($points_found)) {
                    $points = [];
                    foreach ($points_found as $point) {
                        $points[] = (object)[
                            'x' => $point->x,
                            'y' => $point->y
                        ];

                        $restPoints = (object)[
                            'rel' => [],
                            'abs' => []
                        ];
                        $restPoints->abs = $this->removeDublettes($points, $cls->MaxForDublette);

                        if (sizeof($restPoints->abs) > $cls->MaxDefect) {
                            $structureDef = (object)[
                                'maxDefects' => $cls->MaxDefect,
                                'errorPointsCount' => 0,
                                'errorPoints' => [],
                                'defectStructureCount' => 0,
                                'defectStructureName' => [],
                                'defectCount' => []
                            ];

                            $structureDef = $this->checkPoints(true, $restPoints, $structureDef, $format);

                            if ($structureDef->defectStructureCount > 0) {
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

    public function calculateDefectsInAndOuterDie(int $rid, string $wafer, object $cls): bool
    {
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

        if (!empty($wafers_found)) {
            foreach ($wafers_found as $w) {
                $points_found = DB::connection($this->aoi_type)->select("select ir.classid, ir.x,ir.y,ir.dierow,ir.diecol,mi.DestSlot ,cls.DefectName ,cls.CAQDefectName ,
                (case when dierow<0 then 0 else 1 end) as IsDie
                from PInspectionResult IR
                inner join PMaterialInfo MI on MI.rid=IR.pid
                inner join classid cls on cls.ClsId = ir.ClassId
                 where  ir.classid={$cls->clsid} and mi.MaterialId ='{$wafer}' AND mi.LotId = '{$this->box}' and mi.pid={$rid} ");

                if (!empty($points_found)) {
                    $points = [];
                    foreach ($points_found as $point) {
                        $points[] = (object)[
                            'x' => $point->x,
                            'y' => $point->y
                        ];
                    }

                    $restPoints = $this->removeDublettes($points, $cls->MaxForDublette);

                    if (sizeof($restPoints) > $cls->MaxDefect) {
                        $this->rejection = Rejection::where('name', $w->caqdefectname)->first()->id ?? 6;
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function loadFormat(string $format): object
    {
        $structuresRel = DB::connection($this->aoi_type)->select("select ST.id, st.StructurName ,x,y from Structure ST inner join Formate FM on FM.id=st.FormatID
        where fm.FormatName ='{$format}' and relabs='rel'");

        $relStructures = [];
        if (!empty($structuresRel)) {
            for ($i = 0; $i < sizeof($structuresRel); $i++) {
                $relStructures[] = (object)[
                    'id' => $structuresRel[$i]->id,
                    'structureName' => $structuresRel[$i]->StructurName,
                    'coords' => []
                ];

                $xList = explode(';', $structuresRel[$i]->x);
                $yList = explode(';', $structuresRel[$i]->y);
                $relStructures[$i]->points = sizeof($xList);


                for ($j = 0; $j < sizeof($xList); $j++) {
                    $relStructures[$i]->coords[] = (object)[
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
        if (!empty($structuresAbs)) {
            for ($i = 0; $i < sizeof($structuresAbs); $i++) {
                $absStructures[] = (object)[

                    'id' => $structuresAbs[$i]->id,
                    'structureName' => $structuresAbs[$i]->StructurName,
                    'coords' => []
                ];

                $xList = explode(';', $structuresAbs[$i]->x);
                $yList = explode(';', $structuresAbs[$i]->y);
                $absStructures[$i]->points = sizeof($xList);

                $j = 0;
                for ($j; $j < sizeof($xList); $j++) {
                    $absStructures[$i]->coords[$j] = (object)[
                        'x' => $xList[$j],
                        'y' => $yList[$j]
                    ];
                }
                $absStructures[$i]->coords[] = $absStructures[$i]->coords[0];
            }
        }

        return (object)[
            'rel' => $relStructures,
            'abs' => $absStructures
        ];
    }

    public function checkPoints(bool $polyRel, object $points, object $structureDef, string $format)
    {
        if (sizeof($points->rel) < 1 && sizeof($points->abs) < 1)
            return null;

        $formatStructures = $this->loadFormat($format);

        if ($polyRel)
            $structures = $formatStructures->rel;
        else
            $structures = $formatStructures->abs;

        $structureDef = $this->assignPolygons($structures, $points, $structureDef);

        if ($polyRel) {
            if ($structureDef->errorPointsCount > $structureDef->maxDefects) {
                $structures = $formatStructures->abs;
                $points->abs = array_fill(1, $structureDef->errorPointsCount, 0);
                foreach ($structureDef->errorPoints as $key => $errorPoint) {
                    $points->abs[$key] = $structureDef->errorPoints[$key];
                }
                $structureDef->errorPoints = [];
                $structureDef->errorPointsCount = 0;
                $structureDef = $this->assignPolygons($structures, $points, $structureDef);
            }
        }

        return $structureDef;
    }

    public function assignPolygons(array $polygons, object $points, object $structureDef)
    {
        $structsFound = array_fill(0, sizeof($polygons), 0);
        foreach ($points->rel as $key => $point) {
            $structCount = 0;
            $found = false;

            for ($i = 0; $i < sizeof($polygons); $i++) {
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

        foreach ($structsFound as $key => $struct) {
            if ($struct > $structureDef->maxDefects) {
                $structureDef->defectStructureCount++;
                $structureDef->defectStructureName[$structureDef->defectStructureCount] = $polygons[$key]->structureName;
                $structureDef->defectCount[$structureDef->defectStructureCount] = $struct;
            }
        }

        return $structureDef;
    }

    public function pinPoly(object $polygon, object $point): int
    {
        $sidesCrossed = 0;
        for ($i = 0; $i < sizeof($polygon->coords) - 1; $i++) {
            if ($polygon->coords[$i]->x > $point->x xor $polygon->coords[$i + 1]->x > $point->x) {
                $m = ($polygon->coords[$i + 1]->y - $polygon->coords[$i]->y) / ($polygon->coords[$i + 1]->x - $polygon->coords[$i]->x);
                $b = ($polygon->coords[$i]->y * $polygon->coords[$i + 1]->x - $polygon->coords[$i]->x * $polygon->coords[$i + 1]->y) / ($polygon->coords[$i + 1]->x - $polygon->coords[$i]->x);

                if ($m * $point->x + $b > $point->y)
                    $sidesCrossed++;
            }
        }

        return $sidesCrossed % 2;
    }

    public function findMaxKeyInArray(array $array): int
    {
        $max = 0;
        $retKey = 0;
        foreach ($array as $key => $value) {
            if ($value > $max) {
                $max = $value;
                if ($max > 0) $retKey = $key;
            }
        }

        return $retKey;
    }

    public function removeDublettes(array $points, float $maxDist): array
    {
        $matrix = [];
        $sumList = [];
        $tmpList = [];

        for ($i = 1; $i < sizeof($points); $i++) {
            $tSum = 0;
            $tmp = '';
            $add = '';
            for ($j = 1; $j < sizeof($points); $j++) {
                $matrix[$i][$j] = sqrt(pow(($points[$i]->x - $points[$j]->x), 2) + pow(($points[$i]->y - $points[$j]->y), 2));
                $matrix[$i][$j] = $matrix[$i][$j] > $maxDist ? 0 : 1;

                if ($i != $j && $matrix[$i][$j] > 0) {
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

            if ($p > 0) {
                $cols = explode(';', $tmpList[$p]);
                for ($i = 0; $i < sizeof($cols); $i++)
                    $sumList[$cols[$i]] = -$p;
                $sumList[$p] = 0;
            }

        } while ($p >= 1);

        $restCount = 0;
        $tmpPoints = [];
        for ($i = 1; $i <= sizeof($sumList); $i++) {
            if ($sumList[$i] == 0) {
                $restCount++;
                $tmpPoints[$restCount] = $points[$i];
            }
        }

        return $tmpPoints;
    }

    public function updateWafer($wafer, string $box)
    {
        $this->selectedWafer = $wafer;
        $this->box = $box;
        $this->cdo = null;
        $this->cdu = null;
        $this->x = null;
        $this->y = null;
        $this->z = null;
        $this->machine = '';
        $this->rejection = 6;
        $this->aoi_type = 'sqlsrv_aoi2';

        $this->updated('box');
    }

    /**
     * Renders the microscope-aoi view with the necessary data.
     *
     * @return \Illuminate\Contracts\View\View The rendered view
     */
    public function with()
    {
        $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('rejection')->orderBy('wafer_id', 'asc')->get();

        if ($this->search != '') {
            $searchField = $this->searchField;
            $wafers = $wafers->filter(function ($value, $key) use ($searchField) {
                return stristr($value->$searchField, $this->search);
            });
        }

        $rejections = Rejection::find($this->block->rejections);

        if (!empty($rejections))
            $rejections = $rejections->sortBy('number');

        $waferInfo = null;
        if ($this->selectedWafer != '') {
            $waferInfo = Wafer::find($this->selectedWafer);
        }

        if ($this->selectedWafer != '') {
            $sWafers = Process::where('block_id', \BlockHelper::BLOCK_CHROMIUM_COATING)->where('order_id', $this->order->id)->where(function ($query) {
                $query->where('wafer_id', $this->selectedWafer)->orWhere('wafer_id', $this->selectedWafer . '-r');
            })->orderBy('wafer_id', 'desc')->with('wafer')->get();

            if ($sWafers->count() > 0) {
                $this->updateWafer($sWafers->get(0)->wafer_id, $sWafers->get(0)->box);
            }
        } else {
            $sWafers = collect([]);
        }

        return compact(['wafers', 'waferInfo', 'rejections', 'sWafers']);
    }
}
?>

<div class="flex flex-col bg-gray-100 w-full h-full z-[9] border-l border-gray-200 overflow-y-auto" x-data="">
    <div
        class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8] bg-white sticky top-0">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        <span class="grow">{{ $block->name }}</span>
        @can('is-admin')
            @if($wafers->count() > 0)
                <a href="javascript:;"
                   onclick="confirm('Bist du sicher das du alle Einträge löschen willst?') || event.stopImmediatePropagation()"
                   wire:click="clear()"
                   class="hover:bg-gray-50 rounded-sm px-2 py-1 text-sm text-red-500 font-semibold mt-1"><i
                        class="far fa-trash mr-1"></i> Alle Positionen Löschen</a>
            @endif
        @endcan
    </div>
    <div class="h-full bg-gray-100 flex z-[7]" x-data="{ hidePanel: $persist(false) }"
         :class="hidePanel ? '' : 'flex-col'">
        <a href="javascript:;" @click="hidePanel = false"
           class="h-full bg-white w-12 p-3 border-r border-gray-200 hover:bg-gray-50" x-show="hidePanel">
            <p class="transform rotate-90 font-bold w-full text-lg whitespace-nowrap"><i
                    class="far fa-chevron-up mr-3"></i> Eintrag hinzufügen</p>
        </a>
        <div class="w-full h-max bg-white flex flex-col border-r border-gray-200 px-8 pt-3 pb-4" x-show="!hidePanel">
            <h1 class="text-base font-bold flex justify-between items-center">
                Eintrag hinzufügen
                <a href="javascript:;" @click="hidePanel = true"
                   class="px-3 py-1 text-sm rounded-sm font-semibold hover:bg-gray-50"><i class="far fa-eye mr-1"></i>
                    Einträge anzeigen ({{ $wafers->count() }})</a>
            </h1>
            <div class="flex mt-3 gap-4">
                <div class="flex items-center gap-2">
                    <span class="bg-orange-100 w-5 h-5 rounded-md"></span> <span class="text-sm">= Pflichtfeld</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-gray-200 w-5 h-5 rounded-md"></span> <span class="text-sm">= Kein Pflichtfeld</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-gray-200/50 w-5 h-5 rounded-md"></span> <span class="text-sm">= Read Only</span>
                </div>
            </div>
            <div class="flex flex-col h-full relative gap-2 mt-3"
                 x-data="{ operator: {{ auth()->user()->personnel_number }}, rejection: @entangle('rejection') }">
                <div class="w-full h-full absolute" wire:loading wire:target="updateWafer">
                    <div class="w-full h-full flex justify-center absolute items-center z-[5]">
                        <h1 class="text-[#0085CA] font-bold text-2xl"><i class="far fa-spinner animate-spin"></i> Daten
                            von Wafer werden geladen und Post Processing wird ausgeführt...</h1>
                    </div>
                    <div class="w-full h-full bg-white opacity-80 absolute z-[4]"></div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">AR Box ID *:</label>
                    <input wire:model="ar_box" onfocus="this.setSelectionRange(0, this.value.length)" type="text"
                           class="bg-orange-100 @error('ar_box') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold"
                           tabindex="1" placeholder="AR Box ID"/>
                    @error('ar_box')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 mt-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex flex-col w-full relative" x-data="{ show: false, search: '' }"
                         @click.away="show = false">
                        <div class="flex flex-col">
                            <div class="flex">
                                <input type="text" wire:model.live.debounce.1000ms="selectedWafer" id="wafer"
                                       tabindex="2" onfocus="this.setSelectionRange(0, this.value.length)"
                                       @focus="show = true"
                                       class="w-full bg-orange-100 text-sm font-semibold @error('wafer') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror focus:ring-[#0085CA]"
                                       placeholder="Wafer ID eingeben oder scannen..."/>
                            </div>
                            @if(session()->has('waferScanned'))
                                <span class="text-xs mt-1 text-green-600">Gescannter Wafer geladen!</span>
                            @endif
                        </div>
                        <div
                            class="shadow-lg rounded-sm absolute w-full mt-10 border border-gray-300 bg-gray-200 overflow-y-auto max-h-60"
                            x-show="show" x-transition>
                            <div class="flex flex-col divide-y divide-gray-300" wire:loading.remove>
                                <div class="px-2 py-1 text-xs text-gray-500">{{ sizeof($sWafers) }}
                                    Wafer @if($prevBlock != null)
                                        von vorherigem Schritt
                                    @endif</div>
                                @forelse($sWafers as $wafer)
                                    <a href="javascript:;"
                                       wire:click="updateWafer('{{ $wafer->wafer_id }}', '{{ $wafer->box }}')"
                                       @click="show = false"
                                       class="flex items-center px-2 py-1 text-sm hover:bg-gray-100">
                                        @if($wafer->wafer->rejected && !$wafer->wafer->reworked)
                                            <i class="far fa-ban text-red-500 mr-2"></i>
                                        @elseif($wafer->wafer->reworked)
                                            <i class="far fa-exclamation-triangle text-orange-500 mr-2"></i>
                                        @else
                                            <i class="far fa-check text-green-600 mr-2"></i>
                                        @endif
                                        <div class="flex flex-col">
                                            {{ $wafer->wafer_id }}
                                            <span class="text-xs text-gray-500"><i class="fal fa-box-open"></i> Box: {{ $wafer->box }} | von Chrom bezogen</span>
                                            @if($wafer->wafer->rejected && !$wafer->wafer->reworked)
                                                <span
                                                    class="text-xs text-red-500 italic"><b>{{ $wafer->wafer->rejection_reason }}</b> in {{ $wafer->wafer->rejection_order }} <i
                                                        class="fal fa-arrow-right"></i> {{ $wafer->wafer->rejection_avo }} - {{ $wafer->wafer->rejection_position }} </span>
                                            @elseif($wafer->wafer->reworked)
                                                <span class="text-xs text-orange-500 italic">Nachbearbeitet </span>
                                            @elseif($wafer->wafer->is_rework)
                                                <span class="text-xs font-normal text-orange-500">Dieser Wafer ist ein Nacharbeits Wafer</span>
                                            @else
                                                <span class="text-xs text-green-600 italic">Wafer ist in Ordnung</span>
                                            @endif
                                        </div>
                                    </a>
                                @empty
                                    @if($this->search != '')
                                        <div class="px-2 py-1 text-sm">Keine Wafer gefunden!</div>
                                    @else
                                        <div class="px-2 py-1 text-sm">Scanne oder tippe eine Wafer ID ein...</div>
                                    @endif
                                @endforelse
                            </div>
                            <div class="flex w-full px-2 py-1 text-sm" wire:loading><i
                                    class="fal fa-refresh animate-spin mr-1"></i> Wafer werden geladen
                            </div>
                        </div>
                    </div>
                    @error('wafer')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Operator *:</label>
                    <input x-model="operator" onfocus="this.setSelectionRange(0, this.value.length)" type="text"
                           class="bg-orange-100 @error('operator') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold"
                           tabindex="3" placeholder="Operator"/>
                    @error('operator')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex gap-2">
                    <div class="flex flex-col w-full">
                        <label class="text-sm mb-1 text-gray-500">Chrom Box ID (ReadOnly):</label>
                        <input wire:model="box" type="text" disabled class="bg-gray-200/50 text-sm font-semibold border-0 rounded-sm"
                               tabindex="4" placeholder="Chrom Box ID (ReadOnly)"/>
                    </div>
                    <div class="flex flex-col w-full">
                        <label class="text-sm mb-1 text-gray-500">AOI Anlage (ReadOnly):</label>
                        <input wire:model="machine" type="text" disabled class="bg-gray-200/50 text-sm font-semibold border-0 rounded-sm"
                               tabindex="4" placeholder="AOI Anlage"/>
                    </div>
                </div>
                <div class="flex flex-col gap-1">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-500">CD OL:</label>
                            <input type="text" wire:model="cdo"
                                   class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold"
                                   tabindex="5"
                                   placeholder="CD OL"/>
                            @error('cdo')
                                <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                                    <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                                    <span class="font-semibold">{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        <div class="flex flex-col">
                            <label class="text-sm text-gray-500">CD UR:</label>
                            <input type="text" wire:model="cdu"
                                   class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold"
                                   tabindex="6"
                                   placeholder="CD UR"/>
                            @error('cdu')
                                <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                                    <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                                    <span class="font-semibold">{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <div class="flex flex-col">
                            <label class="text-xs text-gray-500">X:</label>
                            <input type="text" wire:model="x"
                                   tabindex="7"
                                   class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                   placeholder="X"/>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs text-gray-500">Y:</label>
                            <input type="text" wire:model="y"
                                   tabindex="8"
                                   class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                   placeholder="Y"/>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-xs text-gray-500">Z:</label>
                            <input type="text" wire:model="z"
                                   tabindex="9"
                                   class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                   placeholder="Z"/>
                        </div>
                    </div>
                    @if($format != null)
                        <div class="bg-blue-500/20 text-blue-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                            <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                            <span class="font-semibold">{{ $format }}</span>
                        </div>
                    @endif
                    @error('xyz_not_calculated')
                    <div class="bg-green-500/20 text-green-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-info-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                    @error('xyz')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Ausschussgrund *:</label>
                    <fieldset class="grid grid-cols-2 gap-0.5">
                        @forelse($rejections as $rejection)
                            <label
                                class="flex px-3 py-3 @if($rejection->reject) bg-red-100/50 @else bg-green-100/50 @endif rounded-sm items-center">
                                <input x-model="rejection" value="{{ $rejection->id }}" type="radio"
                                       class="text-[#0085CA] border-gray-300 rounded-sm focus:ring-[#0085CA] mr-2"
                                       name="rejection">
                                <span class="text-sm">{{ $rejection->name }}</span>
                            </label>
                        @empty
                            <span class="text-xs text-red-500 font-semibold">Ausschussgründe wurden noch nicht definiert...</span>
                        @endforelse
                    </fieldset>
                    @error('rejection')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                @if(session()->has('success'))
                    <span class="mt-1 text-xs font-semibold text-green-600">Eintrag wurde erfolgreich gespeichert</span>
                @endif
                <div class="flex gap-2">
                    @if($waferInfo && !$waferInfo->reworked)
                        <button type="submit" @click="$wire.addEntry(operator, true)" wire:key="reworkBtn"
                                x-show="rejection != 6"
                                class="bg-orange-500 w-max whitespace-nowrap hover:bg-orange-500/80 rounded-sm px-3 py-4 text-sm uppercase text-white text-left"
                                tabindex="10">
                            <span wire:loading.remove wire:target="addEntry">Eintrag als Nacharbeit Speichern</span>
                            <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                        </button>
                    @endif
                    <button type="submit" @click="$wire.addEntry(operator)"
                            class="bg-[#0085CA] w-full hover:bg-[#0085CA]/80 rounded-sm px-3 py-4 text-sm uppercase text-white text-left"
                            tabindex="11">
                        <span wire:loading.remove wire:target="addEntry">Eintrag Speichern</span>
                        <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="w-full flex flex-col pb-4" x-show="hidePanel" x-cloak>
            <div class="flex flex-col px-4 py-3">
                <h1 class="text-base font-bold">Eingetragene Wafer ({{ $wafers->count() }})</h1>
                <div class="flex gap-4">
                    <select wire:model.live="searchField"
                            class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold w-max shadow-sm border-0 focus:ring-[#0085CA]">
                        <option value="wafer_id">Wafer ID</option>
                        <option value="box">Cr Box ID</option>
                        <option value="ar_box">AR Box ID</option>
                    </select>
                    <input type="text" wire:model.live.debounce.500ms="search" onfocus="this.setSelectionRange(0, this.value.length)"
                           class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]"
                           placeholder="Wafer durchsuchen..."/>
                </div>
                <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                    <div
                        class="px-2 py-1 rounded-sm grid grid-cols-11 items-center justify-between bg-gray-200 shadow-sm mb-1">
                        <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                        <span class="text-sm font-bold"><i class="fal fa-user mr-1"></i> Operator</span>
                        <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Cr Box ID</span>
                        <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> AR Box ID</span>
                        <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Anlage</span>
                        <span class="text-sm font-bold"><i class="fal fa-map-marker-alt mr-1"></i> X</span>
                        <span class="text-sm font-bold"><i class="fal fa-map-marker-alt mr-1"></i> Y</span>
                        <span class="text-sm font-bold"><i class="fal fa-map-marker-alt mr-1"></i> Z</span>
                        <span class="text-sm font-bold"><i class="fal fa-calculator mr-1"></i> CDO</span>
                        <span class="text-sm font-bold"><i class="fal fa-calculator mr-1"></i> CDU</span>
                        <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                    </div>
                    @forelse($wafers as $wafer)

                        <div
                            class="bg-white border @if($wafer->rejection->reject) border-red-500/50 @elseif($wafer->reworked || $wafer->wafer->reworked) border-orange-500/50 @else border-green-600/50 @endif flex flex-col rounded-sm hover:bg-gray-50 items-center"
                            x-data="{ waferOpen: false, waferEdit: false }" wire:key="{{ $wafer->wafer_id }}">
                            <div class="flex flex-col px-2 py-2 w-full" x-show="waferEdit"
                                 x-data="{ operator: '{{ $wafer->operator }}', box: '{{ $wafer->ar_box }}', lot: '{{ $wafer->lot }}', machine: '{{ $wafer->machine }}', rejection: {{ $wafer->rejection_id }}, x: '{{ $wafer->x }}', y: '{{ $wafer->y }}', z: '{{ $wafer->z }}', cdo: '{{ $wafer->cd_ol }}', cdu: '{{ $wafer->cd_ur }}' }">
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs text-gray-500">Wafer (Nicht änderbar)</label>
                                    <input disabled type="text" value="{{ $wafer->wafer_id }}"
                                           class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"/>
                                    <label class="text-xs text-gray-500">Operator</label>
                                    <input x-model="operator" type="text"
                                           class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                           placeholder="Operator"/>
                                    <label class="text-xs text-gray-500">AR Box</label>
                                    <input x-model="box" type="text"
                                           class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                           placeholder="AR Box ID"/>
                                    <label class="text-xs text-gray-500">CD</label>
                                    <div class="flex gap-2">
                                        <input x-model="cdo" type="text"
                                               class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                               placeholder="CDO"/>
                                        <input x-model="cdu" type="text"
                                               class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                               placeholder="CDU"/>
                                    </div>
                                    <label class="text-xs text-gray-500">XYZ</label>
                                    <div class="flex gap-2">
                                        <input x-model="x" type="text"
                                               class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                               placeholder="X"/>
                                        <input x-model="y" type="text"
                                               class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                               placeholder="Y"/>
                                        <input x-model="z" type="text"
                                               class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                               placeholder="Z"/>
                                    </div>
                                </div>
                                <label class="text-xs text-gray-500 mt-1">Ausschussgrund</label>
                                <select x-model="rejection"
                                        class="bg-gray-200 rounded-sm border-0 mt-1 focus:ring-[#0085CA] text-xs font-semibold">
                                    @forelse($rejections->lazy() as $rejection)
                                        <option value="{{ $rejection->id }}">{{ $rejection->name }}</option>
                                    @empty
                                        <option value="" disabled>Keine Ausschussgründe definiert</option>
                                    @endforelse
                                </select>
                                <div class="flex gap-1 mt-2">
                                    <a href="javascript:;"
                                       @click="$wire.updateEntry({{ $wafer->id }}, operator, box, rejection, x, y, z, cdo, cdu); waferEdit = false"
                                       class="bg-[#0085CA] hover:bg-[#0085CA]/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Speichern</a>
                                    <a href="javascript:;" @click="waferEdit = false"
                                       class="bg-red-500 hover:bg-red-500/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Abbrechen</a>
                                </div>
                            </div>
                            <div class="flex w-full px-2 py-1 cursor-pointer items-center"
                                 @click="waferOpen = !waferOpen" x-show="!waferEdit">
                                <i class="fal fa-chevron-down mr-2" x-show="!waferOpen"></i>
                                <i class="fal fa-chevron-up mr-2" x-show="waferOpen"></i>
                                <div class="flex flex-col grow">
                                    <div class="grid grid-cols-11 items-center">
                                        <span
                                            class="text-sm font-semibold">{{ $wafer->wafer_id }} @if($wafer->reworked || $wafer->wafer->reworked)
                                                (Nacharbeit)
                                            @endif</span>
                                        <span class="text-xs">{{ $wafer->operator }}</span>
                                        <span class="text-xs">{{ $wafer->box }}</span>
                                        <span class="text-xs">{{ $wafer->ar_box }}</span>
                                        <span class="text-xs">{{ $wafer->machine }}</span>
                                        <span class="text-xs">{{ number_format($wafer->x, 2) }}</span>
                                        <span class="text-xs">{{ number_format($wafer->y, 2) }}</span>
                                        <span class="text-xs">{{ number_format($wafer->z, 2) }}</span>
                                        <span class="text-xs">{{ number_format($wafer->cd_ol, 3) }}</span>
                                        <span class="text-xs">{{ number_format($wafer->cd_ur, 3) }}</span>
                                        <span
                                            class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                                    </div>
                                    @if($wafer->rejection->reject ?? false)
                                        <span
                                            class="text-xs font-normal text-red-500">Ausschuss: {{ $wafer->rejection->name }}</span>
                                        @if($wafer->reworked)
                                            <span
                                                class="text-xs text-orange-500 italic">Wafer wurde Nachbearbeitet </span>
                                        @endif
                                    @elseif($wafer->reworked || $wafer->wafer->reworked)
                                        <span
                                            class="text-xs font-normal text-orange-500">Wafer wurde Nachbearbeitet</span>
                                    @else
                                        <span
                                            class="text-xs font-normal text-green-600">Dieser Wafer ist in Ordnung</span>
                                    @endif
                                    @error('edit' . $wafer->id) <span class="text-xs mt-1 text-red-500"><i
                                            class="fal fa-circle-exclamation mr-1 text-red-500"></i><span
                                            class="text-gray-500">Konnte nicht bearbeiten:</span> {{ $message }}</span> @enderror
                                    @if(session()->has('success' . $wafer->id))
                                        <span class="text-xs mt-1 text-gray-500"><i
                                                class="fal fa-check mr-1 text-green-500"></i> Erfolgreich bearbeitet!</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex w-full px-2 py-2 border-t bg-gray-50 items-center border-gray-200 gap-1"
                                 x-show="waferOpen && !waferEdit">
                                <i class="fal fa-cog mr-1"></i>
                                <a href="{{ route('wafer.show', ['wafer' => $wafer->wafer_id]) }}" target="_blank"
                                   class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i
                                        class="fal fa-search mr-1"></i> Wafer verfolgen</a>
                                <a href="javascript:;" @click="waferEdit = true"
                                   class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i
                                        class="fal fa-pencil mr-1"></i> Wafer bearbeiten</a>
                                @if(!$wafer->wafer->reworked && !$wafer->wafer->is_rework)
                                    <a href="javascript:;"
                                       onclick="confirm('Willst du diesen Wafer wirklich als Nacharbeit markieren?') || event.stopImmediatePropagation()"
                                       wire:click="rework({{ $wafer->id }})"
                                       class="bg-orange-500 text-xs px-3 py-1 uppercase hover:bg-orange-500/80 rounded-sm text-white"><i
                                            class="fal fa-ban mr-1"></i> Nacharbeit</a>
                                @endif
                                <a href="javascript:;"
                                   onclick="confirm('Willst du diesen Wafer wirklich löschen?') || event.stopImmediatePropagation()"
                                   wire:click="removeEntry({{ $wafer->id }})"
                                   class="bg-red-500 text-xs px-3 py-1 uppercase hover:bg-red-500/80 rounded-sm text-white"><i
                                        class="fal fa-trash mr-1"></i> Wafer löschen</a>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col justify-center items-center p-10">
                            <span class="text-lg font-bold text-red-500">Keine Wafer gefunden!</span>
                            <span
                                class="text-sm text-gray-500">Es wurden keine Wafer in diesem Arbeitsschritt gefunden.</span>
                        </div>
                    @endforelse
                </div>
                <div class="flex flex-col justify-center items-center p-10 animate-pulse text-center"
                     wire:loading.delay.longer wire:target="search">
                    <span class="text-lg font-bold text-red-500">Wafer werden geladen...</span><br>
                    <span class="text-sm text-gray-500">Die Wafer werden geladen, wenn dieser Vorgang zu lange dauert bitte die Seite neu laden.</span>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        $wire.on('saved', () => {
            document.getElementById('wafer').focus()
        });
    </script>
@endscript
