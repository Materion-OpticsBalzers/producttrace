<?php

namespace App\Http\Livewire\Data\Queries;

use App\Models\Data\Process;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Exports extends Component
{
    public $headers = array();

    //exportAllWafers
    public $exportAllWafersFrom;
    public $exportAllWafersTo;

    public function mount() {
        $this->headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=file.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $this->exportAllWafersFrom = Carbon::now()->subMonth(1)->format('Y-m-d');
        $this->exportAllWafersTo = Carbon::now()->format('Y-m-d');
    }

    public function exportAllWafers() {
        $this->resetErrorBag();

        $exportAllWafersFrom = Carbon::createFromFormat('Y-m-d', $this->exportAllWafersFrom);
        $exportAllWafersTo = Carbon::createFromFormat('Y-m-d', $this->exportAllWafersTo);

        if($exportAllWafersFrom > $exportAllWafersTo) {
            $this->addError('exportAllWafers', 'Von darf nicht grösser sein als Bis');
            return false;
        }

        $data = Process::where('processes.created_at', '>=', $this->exportAllWafersFrom)->where('processes.created_at', '<=', $this->exportAllWafersTo)
            ->leftJoin('blocks', 'blocks.id', '=', 'processes.block_id')->get();

        if($data->count() == 0) {
            $this->addError('exportAllWafers', 'Keine Daten gefunden');
            return false;
        }

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($data->first()->getAttributes()));

            foreach($data as $row) {
                fputcsv($file, $row->getAttributes());
            }

            fclose($file);
        };

        session()->flash('exportAllWafers');
        return response()->stream($callback, 200, $this->headers);
    }

    public function exportAllSerials() {

    }

    public function render()
    {
        return view('livewire.data.queries.exports');
    }
}
