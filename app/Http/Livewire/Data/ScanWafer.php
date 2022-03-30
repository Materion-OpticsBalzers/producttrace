<?php

namespace App\Http\Livewire\Data;

use App\Models\Data\Serial;
use App\Models\Data\Wafer;
use Livewire\Component;

class ScanWafer extends Component
{
    public function scanWafer($wafer, $searchType) {
        if($wafer == '') {
            $this->addError('wafer', 'Das Feld darf nicht leer sein!');
            return false;
        }

        switch ($searchType) {
            case 'wafer':
                if(Wafer::find($wafer) != null) {
                    $this->redirect(route('wafer.show', ['wafer' => $wafer]));
                } else {
                    $this->addError('wafer', 'Wafer wurde nicht gefunden!');
                }
                break;
            case 'serial':
                $serial = Serial::find($wafer);
                if($serial != null) {
                    $this->redirect(route('wafer.show', ['wafer' => $serial->wafer_id]));
                } else {
                    $this->addError('wafer', 'Es wurde kein Wafer mit dieser Serial gefunden!');
                }
        }


    }

    public function render()
    {
        return view('livewire.data.scan-wafer');
    }
}
