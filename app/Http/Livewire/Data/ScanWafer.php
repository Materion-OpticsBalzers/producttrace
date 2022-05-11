<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Process;
use App\Models\Data\Serial;
use App\Models\Data\Wafer;
use Livewire\Component;

class ScanWafer extends Component
{
    public $search = '';
    public $searchType = 'wafer';

    public function render()
    {
        $wafers = [];

        if($this->search != '') {
            if($this->searchType == 'wafer') {
                if (Wafer::find(trim($this->search)) != null) {
                    $this->redirect(route('wafer.show', ['wafer' => $this->search]));
                } else {
                    $this->addError('wafer', 'Wafer wurde nicht gefunden!');
                }
            }

            if($this->searchType == 'serial') {
                $serial = Serial::find($this->search);
                if ($serial != null) {
                    $this->redirect(route('wafer.show', ['wafer' => $serial->wafer_id]));
                } else {
                    $this->addError('wafer', 'Es wurde kein Wafer mit dieser Serial gefunden!');
                }
            }

            if($this->searchType == 'chrome') {
                $wafers = Process::where('lot', $this->search)->where('block_id', 2)->limit(50)->lazy();
                if ($wafers->count() == 0) {
                    $this->addError('wafer', 'Es wurde kein Wafer mit dieser Chromcharge gefunden!');
                }
            }

            if($this->searchType == 'ar') {
                $wafers = Process::where('lot', $this->search)->where('block_id', 8)->limit(50)->lazy();
                if ($wafers->count() == 0) {
                    $this->addError('wafer', 'Es wurde kein Wafer mit dieser AR Charge gefunden!');
                }
            }

            if($this->searchType == 'box') {
                $wafers = Process::where('box', $this->search)->select('wafer_id')->groupBy('wafer_id')->limit(50)->get();
                if ($wafers->count() == 0) {
                    $this->addError('wafer', 'Es wurde kein Wafer mit dieser Box gefunden!');
                }
            }
        }

        return view('livewire.data.scan-wafer', compact('wafers'));
    }
}
