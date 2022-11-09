<?php

namespace App\Http\Livewire\Data\Queries;

use App\Models\Data\Process;
use Carbon\Carbon;
use Livewire\Component;

class CDOLQuery extends Component
{
    public $dateFrom;
    public $dateTo;

    public function mount() {
        $this->dateFrom = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $wafers = Process::where('block_id', 6)->whereNotNull(['cd_ol', 'cd_ur'])->orderBy('created_at')->get();

        $values = [];
        $values2 = [];
        $valueLabels = [];
        if($wafers->count() > 0) {
            foreach($wafers as $wafer) {
                $values[] = $wafer->cd_ol;
                $values2[] = $wafer->cd_ur;
                $valueLabels[] = "'$wafer->wafer_id'";
            }
        }

        return view('livewire.data.queries.c-d-o-l-query', compact('wafers', 'values', 'values2', 'valueLabels'));
    }
}
