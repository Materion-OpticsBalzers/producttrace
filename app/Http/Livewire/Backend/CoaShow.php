<?php

namespace App\Http\Livewire\Backend;

use App\Models\Data\Coa;
use App\Models\Data\Order;
use App\Models\Data\Serial;
use Carbon\Carbon;
use Carbon\Exceptions\NotACarbonClassException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Renderer\JpGraph;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CoaShow extends Component
{
    public $order;

    public function mount(Order $order)
    {
        $this->order = $order;
    }

    public function generateCoa() {
        if(\CoaHelper::generateCoa($this->order)) {
            session()->flash('success');
        }
    }

    public function approveOrder($orderId, $hasPo = false) {
        Coa::updateOrCreate(['order_id' => $this->order->id], [
            'user_id' => auth()->id(),
            'serialized' => $hasPo,
        ]);

        session()->flash('approved');
    }

    public function render()
    {
        $this->resetErrorBag();

        $data = \CoaHelper::loadCoaData($this->order);

        if($data->found_files < 6)
            $this->addError('files', "Es konnten nicht alle Kurvendateien gefunden werden");

        if(empty($data->ar_data)) {
            $this->addError('ar_data', "Es konnte keine AR Daten für diesen Auftrag und die Charge im CAQ gefunden werden!");
        }

        return view('livewire.backend.coa-show', ['serials' => $data->serials, 'found_files' => $data->found_files, 'ar_data' => $data->ar_data, 'ar_info' => $data->ar_info, 'chrom_lots' => $data->chrom_lots]);
    }
}
