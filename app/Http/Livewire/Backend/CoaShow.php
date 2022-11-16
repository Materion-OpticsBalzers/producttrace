<?php

namespace App\Http\Livewire\Backend;

use App\Models\Data\Order;
use App\Models\Data\Serial;
use Carbon\Carbon;
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

    public function getData() {
        $serials = Serial::where('order_id', $this->order->id)->whereNotNull('wafer_id')
            ->with(['wafer','order', 'wafer.order', 'wafer.processes' => function($query) {
                $query->whereIn('block_id', [2, 4, 6, 8]);
            }])
            ->whereHas('wafer', function($query) {
                $query->where('rejected', false);
            })->orderBy('id')->lazy();

        $chrom_lots = collect([]);
        foreach($serials as $serial) {
            $chrom_info = $serial->wafer->processes->first() ?? null;
            $aoi_info = $serial->wafer->processes->get(2) ?? null;
            if($chrom_info && !$chrom_lots->has($chrom_info->lot)) {
                $chrom_lots[$chrom_info->lot] = (object)[
                    'lot' => $chrom_info->lot,
                    'cd_ol' => collect([]),
                    'cd_ur' => collect([]),
                    'order' => $chrom_info->order_id
                ];
            }

            if($aoi_info) {
                $chrom_lots->get($chrom_info->lot)->cd_ol->add($aoi_info->cd_ol);
                $chrom_lots[$chrom_info->lot]->cd_ur->add($aoi_info->cd_ur);
            }
        }

        $ar_info = $serials->first()->wafer->processes->get(3) ?? (object) [
            'lot' => '',
            'machine' => ''
        ];

        $ar_data = DB::connection('sqlsrv_caq')->select("SELECT TAUFTRAG, TCHARGE, TWERTE FROM CPLUSCHARGENINFO
            LEFT JOIN CPLUSAUFTRAG ON CPLUSAUFTRAG.ID = CPLUSCHARGENINFO.CPLUSAUFTRAG_ID
            LEFT JOIN CPLUSSTICHPROBE ON CPLUSSTICHPROBE.ID = CPLUSCHARGENINFO.CPLUSSTICHPROBE_ID
            LEFT JOIN CPLUSWERT ON CPLUSWERT.CPLUSSTICHPROBE_ID = CPLUSSTICHPROBE.ID
            WHERE CPLUSAUFTRAG.TAUFTRAG = '{$this->order->id}' AND CPLUSCHARGENINFO.TCHARGE = '{$ar_info->lot}' AND LAVO = 30");

        $foundFiles = $this->checkFiles($ar_info->lot, $ar_info->machine);

        return (object) [
            'ar_data' => $ar_data,
            'chrom_lots' => $chrom_lots,
            'serials' => $serials,
            'ar_info' => $ar_info,
            'found_files' => $foundFiles
        ];
    }

    public function checkFiles($ar_lot, $leybold) {
        $leyboldSub = substr($leybold, 4, 1);
        $files = [
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}R{$ar_lot}A.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}R{$ar_lot}A.dsp"
            ],
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}R{$ar_lot}M.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}R{$ar_lot}M.dsp"
            ],
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}R{$ar_lot}Z.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}R{$ar_lot}Z.dsp"
            ],
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}T{$ar_lot}A.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}T{$ar_lot}A.dsp"
            ],
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}T{$ar_lot}M.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}T{$ar_lot}M.dsp"
            ],
            (object) [
                'main' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/{$leyboldSub}T{$ar_lot}Z.rls",
                'second' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/{$leyboldSub}T{$ar_lot}Z.dsp"
            ]
        ];

        $foundFiles = 0;
        $filePaths = [];
        foreach($files as $file) {
            if (Storage::disk('s')->exists($file->main)) {
                $foundFiles++;
                $filePaths[] = (object) [
                    'type' => 'main',
                    'file' => $file->main
                ];
                continue;
            }

            if (Storage::disk('s')->exists($file->second)) {
                $foundFiles++;
                $filePaths[] = (object) [
                    'type' => 'second',
                    'file' => $file->second
                ];
            }
        }

        if(sizeof($files) > $foundFiles)
            $this->addError('files', "Es konnten nicht alle Kurvendateien gefunden werden");

        return $filePaths;
    }

    public function generateCoa() {
        $data = $this->getData();

        $reader = IOFactory::createReaderForFile(public_path('media/CofA_Template_n.xlsx'));
        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load(public_path('media/CofA_Template_n.xlsx'));
        $sheet = $spreadsheet->getSheetByName("CoA");
        $sheet->setCellValue('D15', $this->order->po_cust);
        $sheet->setCellValue('D16', Carbon::now()->format('m/d/Y'));
        $sheet->setCellValue('D18', $this->order->article_cust);
        $sheet->setCellValue('L15', $this->order->po);
        $sheet->setCellValue('L16', $this->order->article);

        $sheet->setCellValue('H21', substr($data->ar_info->machine, 4, 1) . '_' . $data->ar_info->lot);

        $sheet->setCellValue('G25', collect(explode(';', $data->ar_data[0]->TWERTE))->get(1));
        $sheet->setCellValue('M25', collect(explode(';', $data->ar_data[3]->TWERTE))->get(1));
        $sheet->setCellValue('G26', collect(explode(';', $data->ar_data[1]->TWERTE))->get(1));
        $sheet->setCellValue('M26', collect(explode(';', $data->ar_data[4]->TWERTE))->get(1));
        $sheet->setCellValue('M27', collect(explode(';', $data->ar_data[5]->TWERTE))->get(1));
        $sheet->setCellValue('G28', collect(explode(';', $data->ar_data[2]->TWERTE))->get(1));
        $sheet->setCellValue('M29', collect(explode(';', $data->ar_data[6]->TWERTE))->get(1));
        $sheet->setCellValue('M30', collect(explode(';', $data->ar_data[7]->TWERTE))->get(1));
        $sheet->setCellValue('M31', collect(explode(';', $data->ar_data[8]->TWERTE))->get(1));
        $sheet->setCellValue('M32', collect(explode(';', $data->ar_data[9]->TWERTE))->get(1));

        $sheet = $spreadsheet->getSheetByName("Chrom");

        $index = 33;
        foreach($data->chrom_lots as $lot) {
            $sheet->setCellValue('A' . $index, 5);
            $sheet->setCellValue('D' . $index, '±1');
            $sheet->setCellValue('G' . $index, number_format($lot->cd_ur->avg(), 2));
            $sheet->setCellValue('J' . $index, number_format($lot->cd_ol->avg(), 2));
            $sheet->setCellValue('M' . $index, $lot->lot);

            $index++;
        }

        $sheet = $spreadsheet->getSheetByName("Position");

        $sheet->setCellValue('D9', substr($data->ar_info->machine, 4, 1) . '_' . $data->ar_info->lot);
        $sheet->setCellValue('D10', $data->ar_info->created_at->format('m/d/Y'));

        $index = 15;
        foreach($data->serials as $serial) {
            $sheet->setCellValue('C' . $index, $serial->id);
            $sheet->setCellValue('G' . $index, substr($serial->wafer->processes->first()->position ?? '?', 0, 1));
            $sheet->setCellValue('K' . $index, str_replace('-r', '', $serial->wafer_id));
            $sheet->setCellValue('M' . $index, $serial->wafer->order->supplier);
            $sheet->setCellValue('N' . $index, $serial->wafer->processes->first()->lot ?? 'chrom fehlt');
            $sheet->setCellValue('O' . $index, $serial->wafer->processes->first()->machine ?? 'chrom fehlt');
            $sheet->setCellValue('P' . $index, $serial->wafer->processes->get(1)->machine ?? 'ar fehlt');
            $sheet->setCellValue('Q' . $index, $serial->wafer->processes->get(3)->machine ?? 'ar fehlt');

            $index++;
        }

        $sheet = $spreadsheet->getSheetByName("Kurve");
        $files = $this->checkFiles($data->ar_info->lot, $data->ar_info->machine);

        $charIndex = 'C';
        foreach($files as $file) {
            $file_data = $this->readFile($file->file, $file->type);

            if(!empty($file_data)) {
                $index = 4;
                foreach($file_data as $line) {
                    $sheet->setCellValue($charIndex . $index, $line);
                    $index++;
                }
            }

            $charIndex++;
        }

        /*Settings::setChartRenderer(JpGraph::class);
        $chart->render(public_path('tmp') . '/chart.png');*/

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        $writer->setIncludeCharts(true);
        $writer->save(public_path('tmp\coa_' . $this->order->id . '.xlsx'));
        $spreadsheet->disconnectWorksheets();
        File::move(public_path('tmp\coa_' . $this->order->id . '.xlsx'), '\\\\opticsbalzers.local\data\090 Produktion\10 Linie 1\30 Production\Affymetrix\Serial_CoA\PT_Test\coa_' . $this->order->id . '.xlsx');

        session()->flash('success');
    }

    public function readFile($path, $type) : array {
        $path = Storage::disk('s')->path($path);
        $data = [];

        if($type == 'main') {
            $found_line = false;
            foreach (file($path) as $line) {
                if($line == stristr($line, "#Data")) {
                    $found_line = true;
                    continue;
                }

                if($found_line)
                    $data[] = preg_split("/\s+/", $line)[1] ?? '';
            }
        } else {
            foreach (file($path) as $line) {
                $data[] = preg_split("/\s+/", $line)[1] ?? '';
            }
        }

        return $data;
    }

    public function generateChart() : Chart {
        $xAxisTickValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Kurve!$B$4:$B$364')
        ];

        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Kurve!$B$3')
        ];

        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Kurve!$C$4:$C$364')
        ];

        $series = new DataSeries(DataSeries::TYPE_LINECHART, null, [], $dataSeriesLabels, $xAxisTickValues, $dataSeriesValues);
        $plotArea = new PlotArea(null, [$series]);

        return new Chart('chart1', new Title('Test'), null, $plotArea, false, DataSeries::EMPTY_AS_GAP, new Title('Test'));
    }

    public function render()
    {
        $this->resetErrorBag();

        $data = $this->getData();

        if(empty($data->ar_data)) {
            $this->addError('ar_data', "Es konnte keine AR Daten für diesen Auftrag und die Charge im CAQ gefunden werden!");
        }

        return view('livewire.backend.coa-show', ['serials' => $data->serials, 'found_files' => $data->found_files, 'ar_data' => $data->ar_data, 'ar_info' => $data->ar_info, 'chrom_lots' => $data->chrom_lots]);
    }
}
