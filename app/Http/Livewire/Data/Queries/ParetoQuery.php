<?php

namespace App\Http\Livewire\Data\Queries;

use Akaunting\Apexcharts\Chart;
use App\Models\Data\Process;
use App\Models\Generic\Block;
use Carbon\Carbon;
use Livewire\Component;

class ParetoQuery extends Component
{
    public $selectedBlock = 0;
    public $dateFrom;
    public $dateTo;
    public $generate = false;

    public function mount() {
        $this->dateFrom = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $blocks = Block::where('avo', '!=', 0)->orderBy('avo')->get();

        $wafers = [];
        $rejections = [];
        $rejectionCounts = [];
        if($this->selectedBlock > 0) {
            $wafers = Process::where('block_id', $this->selectedBlock)->with(['rejection', 'block', 'wafer'])->whereHas('rejection', function($query) {
                return $query->where('reject', true);
            })->where('created_at', '>=', $this->dateFrom)
            ->when($this->dateTo != null, function($query) {
                $query->where('created_at', '<=', $this->dateTo);
            })->lazy();

            if(sizeof($wafers) > 0) {
                $prevRejection = "";
                $index = -1;
                $wafersT = $wafers->sortBy('rejection.name');
                foreach($wafersT as $wafer) {
                    if($wafer->rejection->name != $prevRejection) {
                        $index++;
                        $prevRejection = $wafer->rejection->name;
                        $rejections[] = "'{$wafer->rejection->name}'";
                        $rejectionCounts[$index] = 1;
                    } else {
                        $rejectionCounts[$index] += 1;
                    }
                }

                $this->dispatchBrowserEvent('paretoChanged');
            }
        }

        return view('livewire.data.queries.pareto-query', compact('blocks', 'wafers', 'rejections', 'rejectionCounts'));
    }
}
