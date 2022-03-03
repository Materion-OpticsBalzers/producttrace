<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\ProcessData;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Carbon\Carbon;
use Livewire\Component;

class IncomingQualityControl extends Component
{
    public $blockId;
    public $orderId;

    public $waferError = true;

    public function checkWafer($wafer) {
        if($wafer == '') {
            $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
            $this->waferError = true;
            return false;
        }

        $wafer = Wafer::find($wafer);

        if($wafer == null) {
            $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
            $this->waferError = true;
            return false;
        }

        if($wafer->reworks == 2) {
            $this->addError('wafer', 'Dieser Wafer darf nicht mehr verwendet werden.');
            $this->waferError = true;
            return false;
        }

        if(Process::where('wafer_id', $wafer->id)->where('block_id', $this->blockId)->count() > 0) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            $this->waferError = true;
            return false;
        }

        $this->waferError = false;
        session()->flash('waferCheck', 'Wafernummer in Ordnung');
    }

    public function addEntry($wafer, $order, $block, $operator, $box, $rejection) {
        $error = false;

        $this->checkWafer($wafer);

        if($this->waferError) {
            $this->addError('response', 'Ein Fehler mit der Wafernummer hat das Speichern verhindert');
            $error = true;
        }

        if($operator == '') {
            $this->addError('operator', 'Der Operator darf nicht leer sein!');
            $error = true;
        }

        if($box == '') {
            $this->addError('box', 'Die Box ID Darf nicht leer sein!');
            $error = true;
        }

        if($rejection == null) {
            $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        $process = Process::create([
            'wafer_id' => $wafer,
            'order_id' => $order,
            'block_id' => $block,
            'operator' => $operator,
            'box' => $box,
            'date' => Carbon::now()
        ]);

        ProcessData::create([
           'process_id' => $process->id,
           'rejection_id'
        ]);

        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
    }

    public function render()
    {
        $block = Block::find($this->blockId);
        $wafers = Process::where('order_id', $this->orderId)->where('block_id', $this->blockId)->lazy();

        $rejections = Rejection::find(json_decode($block->rejections));

        if(!empty($rejections))
            $rejections = $rejections->sortBy('id');

        return view('livewire.blocks.incoming-quality-control', compact('block', 'wafers', 'rejections'));
    }
}
