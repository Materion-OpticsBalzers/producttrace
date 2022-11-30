<?php

namespace App\Http\Livewire\Backend;

use App\Models\Generic\Format;
use Livewire\Component;

class FormatManager extends Component
{
    public function addFormat($name, $identifier, $dimension, $tolerance) {
        $this->resetErrorBag();

        if(!$name) {
            $this->addError('name', 'Der Name des Formats darf nicht leer sein!');
            return false;
        }

        if(!$identifier) {
            $this->addError('identifier', 'Die Kennung des Formats darf nicht leer sein!');
            return false;
        }

        $format = Format::where('name', $identifier)->first();
        if($format != null) {
            $this->addError('identifier', 'Ein anderes Format mit dieser Kennung ist schon vorhanden');
            return false;
        }

        if($dimension == '') {
            $this->addError('dimension', 'Die Dimension des Formats darf nicht leer sein!');
            return false;
        }

        if($tolerance == '') {
            $this->addError('tolerance', 'Die Toleranz des Formats darf nicht leer sein!');
            return false;
        }

        Format::create([
           'title' => $name,
           'name' => $identifier,
            'min' => $dimension - $tolerance,
            'max' => $dimension + $tolerance
        ]);
    }

    public function saveFormat($id, $title, $name, $min, $max) {
        Format::find($id)->update([
            'title' => $title,
            'name' => $name,
            'min' => $min,
            'max' => $max
        ]);
    }

    public function removeFormat($id) {
        Format::destroy($id);
    }

    public function render()
    {
        $formats = Format::all();

        return view('livewire.backend.format-manager', compact('formats'));
    }
}
