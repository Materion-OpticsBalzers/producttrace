<?php

namespace App\Http\Livewire\Blocks;

use App\Models\Data\Process;
use App\Models\Data\Wafer;
use App\Models\Generic\Block;
use App\Models\Generic\Rejection;
use Carbon\Carbon;
use Livewire\Component;

class ChromiumCoating extends Component
{
    public $blockId;
    public $orderId;

    public $search = '';

    public $selectedWafer = null;

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
            $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
            return false;
        }

        if($wafer->reworks == 2) {
            $this->addError('wafer', 'Dieser Wafer darf nicht mehr verwendet werden.');
            return false;
        }

        if(Process::where('wafer_id', $wafer->id)->where('block_id', $this->blockId)->exists()) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            return false;
        }

        return true;
    }

    public function addEntry($order, $block, $operator, $box, $machine, $position) {
        $error = false;

        if(!$this->checkWafer($this->selectedWafer)) {
            $this->addError('response', 'Bitte zuerst die Fehler korrigieren!');
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

        if($machine == '') {
            $this->addError('machine', 'Anlagennummer darf nicht leer sein!');
            $error = true;
        }

        if($position == null) {
            $this->addError('position', 'Es muss eine Position abgegeben werden!');
            $error = true;
        }

        if($error)
            return false;

        Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $order,
            'block_id' => $block,
            'operator' => $operator,
            'box' => $box,
            'machine' => $machine,
            'position' => $position,
            'date' => now()
        ]);

        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
    }

    public function removeEntry($entryId) {
        $process = Process::find($entryId);

        if($process->wafer->rejected && ($process->rejection->reject ?? false)) {
            Wafer::find($process->wafer_id)->update([
                'rejected' => false,
                'rejection_reason' => null,
                'rejection_position' => null,
                'rejection_avo' => null,
                'rejection_order' => null
            ]);
        }

        $process->delete();
    }

    public function clear($order, $block) {
        $wafers = Process::where('order_id', $order)->where('block_id', $block)->with('wafer');

        foreach ($wafers->lazy() as $wafer) {
            if($wafer->wafer->rejected && ($wafer->rejection->reject ?? false)) {
                Wafer::find($wafer->wafer_id)->update([
                    'rejected' => false,
                    'rejection_reason' => null,
                    'rejection_position' => null,
                    'rejection_avo' => null,
                    'rejection_order' => null
                ]);
            }
        }

        $wafers->delete();
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

        $rejections = Rejection::find($block->rejections);

        if(!empty($rejections))
            $rejections = $rejections->sortBy('id');

        if($this->selectedWafer != '')
            $sWafers = Wafer::where('id', 'like', "%$this->selectedWafer%")->limit(30)->get();
        else
            $sWafers = [];

        $calculatedPos = 'Aussen';
        if($wafers->count() > 9 && $wafers->count() <= 13)
            $calculatedPos = 'Mitte';
        elseif($wafers->count() > 13)
            $calculatedPos = 'Zentrum';

        return view('livewire.blocks.chromium-coating', compact('block', 'wafers', 'rejections', 'sWafers', 'calculatedPos'));
    }
}
