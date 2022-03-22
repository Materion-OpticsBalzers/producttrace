<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Wafer;
use Livewire\Component;

class ScanWafer extends Component
{
    public function scanWafer($wafer) {
        if($wafer == '') {
            $this->addError('wafer', 'Das Feld darf nicht leer sein!');
            return false;
        }

        if(Wafer::find($wafer) != null) {
            $this->redirect(route('wafer.show', ['wafer' => $wafer]));
        } else {
            $this->addError('wafer', 'Wafer wurde nicht gefunden!');
        }
    }

    public function render()
    {
        return view('livewire.data.scan-wafer');
    }
}
