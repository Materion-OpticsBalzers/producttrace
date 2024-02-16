<?php

use App\Models\Data\Order;
use App\Models\Data\Serial;
use App\Models\Data\Process;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class CoaHelper {
    public static function loadCoaData($order) {
        $serials = Serial::where('order_id', $order->id)
            ->with(['wafer','order', 'wafer.order', 'wafer.processes' => function($query) {
                $query->whereIn('block_id', [2, 4, 6, 8, 9])->orderBy('block_id');
            }])->orderBy('id')->get();

        $chrom_lots = collect([]);
        $packaging_date = null;
        $ar_info = null;
        foreach($serials as $serial) {
            $serial_processes = collect([
                '2' => null,
                '4' => null,
                '6' => null,
                '8' => null,
                '9' => null
            ]);


            if($serial->wafer != null) {
                foreach ($serial->wafer->processes as $process) {
                    $serial_processes[$process->block_id] = $process;
                }

                $serial->wafer->processes = $serial_processes;
            } else {
                $serial->wafer = (object) [
                    'id' => 'Missing',
                    'rejected' => true,
                    'processes' => $serial_processes,
                    'order' => (object) [
                        'supplier' => 'Missing'
                    ]
                ];
            }


            if (!$packaging_date) {
                $ocq = $serial->wafer->processes[BlockHelper::BLOCK_OUTGOING_QUALITY_CONTROL];

                if ($ocq) {
                    $packaging_date = $ocq->created_at->format('d.m.Y');
                }
            }

            if (!$ar_info) {
                $arc = $serial->wafer->processes[BlockHelper::BLOCK_ARC];

                if($arc) {
                    $ar_info = $arc;
                }
            }

            $chrom_info = $serial->wafer->processes[BlockHelper::BLOCK_CHROMIUM_COATING];
            $aoi_info = $serial->wafer->processes[BlockHelper::BLOCK_MICROSCOPE_AOI];
            if ($chrom_info && !$chrom_lots->has($chrom_info->lot)) {
                $chrom_lots[$chrom_info->lot] = (object)[
                    'lot' => $chrom_info->lot,
                    'cd_ol' => collect([]),
                    'cd_ur' => collect([]),
                    'order' => $chrom_info->order_id
                ];

                if ($aoi_info) {
                    $chrome_wafers = Process::where('block_id', BlockHelper::BLOCK_CHROMIUM_COATING)->where('lot', $chrom_info->lot)->get('wafer_id');

                    foreach ($chrome_wafers as $cr_wafer) {
                        $aoi_wafer = Process::where('wafer_id', $cr_wafer->wafer_id)->where('block_id', BlockHelper::BLOCK_MICROSCOPE_AOI)->first();

                        if ($aoi_wafer && $aoi_wafer->cd_ol && $aoi_wafer->cd_ur) {
                            $chrom_lots->get($chrom_info->lot)->cd_ol->add($aoi_wafer->cd_ol);
                            $chrom_lots->get($chrom_info->lot)->cd_ur->add($aoi_wafer->cd_ur);
                        }
                    }
                }
            }
        }

        if(!$ar_info) {
            $ar_info = (object) [
                'lot' => null,
                'machine' => null,
                'created_at' => null
            ];
        }

        if($ar_info->lot) {
            $ar_data = DB::connection('sqlsrv_caq')->select("SELECT TAUFTRAG, TCHARGE, TWERTE FROM CPLUSCHARGENINFO
            LEFT JOIN CPLUSAUFTRAG ON CPLUSAUFTRAG.ID = CPLUSCHARGENINFO.CPLUSAUFTRAG_ID
            LEFT JOIN CPLUSSTICHPROBE ON CPLUSSTICHPROBE.ID = CPLUSCHARGENINFO.CPLUSSTICHPROBE_ID
            LEFT JOIN CPLUSWERT ON CPLUSWERT.CPLUSSTICHPROBE_ID = CPLUSSTICHPROBE.ID
            WHERE CPLUSAUFTRAG.TAUFTRAG = '{$order->id}' AND CPLUSCHARGENINFO.TCHARGE = '{$ar_info->lot}' AND LAVO = 30");

            $foundFiles = CoaHelper::checkFiles($ar_info);
        }

        return (object) [
            'ar_data' => $ar_data ?? [],
            'chrom_lots' => $chrom_lots,
            'serials' => $serials,
            'ar_info' => $ar_info,
            'packaging_date' => $packaging_date,
            'found_files' => $foundFiles ?? []
        ];
    }

    public static function generateSerialList($po) {
        $spreadsheet = IOFactory::load(public_path('media/template.xls'));
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('B3', $po->po_cust ?: '');
        $sheet->setCellValue('B4', $po->delivery_date ? date('d/m/Y', strtotime($po->delivery_date)) : '');
        $sheet->setCellValue('B5', $po->id);
        $sheet->setCellValue('B6', $po->article);
        $sheet->setCellValue('B8', $po->article_cust);
        $sheet->setCellValue('B9', $po->format);

        $orders = Order::where('po', $po->id)->with('serials')->orderBy('po_pos', 'asc')->lazy();

        $startIndex = 12;
        if($orders->count() > 0) {
            $firstPos = $orders->first()->po_pos / 10;
            $startIndex += $firstPos - 1;
        }

        foreach($orders as $order) {
            $sheet->setCellValue("B{$startIndex}", $order->serials->first()->id ?? '?');
            $sheet->setCellValue("C{$startIndex}", $order->serials->last()->id ?? '?');
            $sheet->setCellValue("D{$startIndex}", $order->serials->count());
            $sheet->setCellValue("E{$startIndex}", $order->serials->count() - $order->missingSerials()->count());
            $sheet->setCellValue("F{$startIndex}",  join(', ', $order->missingSerials()->pluck('id')->toArray()));

            $startIndex++;
        }

        $writer = new Xls($spreadsheet);
        $writer->save(public_path('tmp\sl_' . $po->id . '.xls'));
        $spreadsheet->disconnectWorksheets();

        if(!Storage::disk('s')->exists(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year))
            Storage::disk('s')->makeDirectory(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year);

        if(!Storage::disk('s')->exists(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust))
            Storage::disk('s')->makeDirectory(config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust);



        File::move(public_path('tmp\sl_' . $po->id . '.xls'), '\\\\opticsbalzers.local\\data\\' . config('filesystems.pt_paths.coa_base_path') . '\\' . Carbon::now()->year . '\\' . $po->id . '_' . $po->po_cust . '\\' . $po->id . '_' . $po->po_cust .  '.xls');
    }

    private static function findInSubfolder(string $baseDir, string $fileName, $date) : string {
        $file = '';

        if(Storage::disk('s')->exists($baseDir . $date->monthName)) {
            if(Storage::disk('s')->exists($baseDir . $date->monthName . '/' . $fileName)) {
                $file = $baseDir . $date->monthName . '/' . $fileName;
            }
        } else {
            if(Storage::disk('s')->exists($baseDir . $fileName)) {
                $file = $baseDir . $fileName;
            }
        }

        return $file;
    }

    public static function checkFiles($ar_info) {
        $leyboldSub = substr($ar_info->machine, 4, 1);
        $ar_lot = $ar_info->lot;
        $ar_date = Carbon::make($ar_info->created_at);

        $files = [
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}T{$ar_lot}A.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}T{$ar_lot}A.dsp"
            ],
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}T{$ar_lot}M.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}T{$ar_lot}M.dsp"
            ],
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}T{$ar_lot}Z.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}T{$ar_lot}Z.dsp"
            ],
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}R{$ar_lot}A.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}R{$ar_lot}A.dsp"
            ],
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}R{$ar_lot}M.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}R{$ar_lot}M.dsp"
            ],
            (object) [
                'mainRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/01l35ar/",
                'main' => "{$leyboldSub}R{$ar_lot}Z.rls",
                'secondRoot' => "090 Produktion/10 Linie 1/30 Production/10 Messdaten/01 Spektralphotometer/Agilent Cary 7000/",
                'second' => "{$leyboldSub}R{$ar_lot}Z.dsp"
            ]
        ];

        $foundFiles = 0;
        $filePaths = [];
        foreach($files as $file) {
            if (Storage::disk('s')->exists($file->mainRoot . $file->main)) {
                $foundFiles++;
                $filePaths[] = (object) [
                    'type' => 'main',
                    'file' => $file->mainRoot . $file->main
                ];
                continue;
            } else {
                $result = self::findInSubfolder($file->mainRoot . $ar_date->year . '/', $file->main, $ar_date);
                if($result) {
                    $foundFiles++;
                    $filePaths[] = (object) [
                        'type' => 'main',
                        'file' => $result
                    ];
                    continue;
                }
            }

            if (Storage::disk('s')->exists($file->secondRoot . $file->second)) {
                $foundFiles++;
                $filePaths[] = (object) [
                    'type' => 'second',
                    'file' => $file->secondRoot . $file->second
                ];
            } else {
                $result = self::findInSubfolder($file->secondRoot . $ar_date->year . '/', $file->second, $ar_date);
                if($result) {
                    $foundFiles++;
                    $filePaths[] = (object) [
                        'type' => 'second',
                        'file' => $result
                    ];
                }
            }
        }

        return $filePaths;
    }

    public static function readFile($path, $type) {
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

    public static function generateCoa($order, $final = false, $qa = null)
    {
        $data = \CoaHelper::loadCoaData($order);

        $reader = IOFactory::createReaderForFile(public_path('media/CofA_Template_n.xlsx'));
        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load(public_path('media/CofA_Template_n.xlsx'));
        $sheet = $spreadsheet->getSheetByName("CoA");

        $coaDate = "";
        if ($order->po) {
            $result = DB::connection('oracle')->select("SELECT BESTELLDATUM_C as datum FROM PROD_ERP_001.DOK
            LEFT JOIN PROD_ERP_001.CUST0011 ON CUST0011.DOKNR = DOK.DOKNR
            WHERE DOK.DOKNR = '{$order->po}'");

            if (!empty($result)) {
                $coaDate = $result[0]->datum;
                $coaDate = Carbon::make($coaDate)->format('m/d/Y');
            }
        }

        $sheet->setCellValue('D15', $order->po_cust);
        $sheet->setCellValue('D16', $coaDate);
        $sheet->setCellValue('D18', $order->article_cust);
        $sheet->setCellValue('L15', $order->po . ' / ' . $order->po_pos);
        $sheet->setCellValue('L16', $order->article);
        $sheet->setCellValue('L17', $data->packaging_date ? Carbon::make($data->packaging_date)->format('m/d/Y') : '');
        $sheet->setCellValue('B50', Carbon::now()->format('m/d/Y'));
        $sheet->setCellValue('L50', $qa ? $qa->name : auth()->user()->name);

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

        $sheet->setCellValue('D12', $order->po_cust);
        $sheet->setCellValue('L12', $order->po . ' / ' . $order->po_pos);
        $sheet->setCellValue('B52', Carbon::now()->format('m/d/Y'));
        $sheet->setCellValue('L52', $qa ? $qa->name : auth()->user()->name);

        $index = 33;
        foreach ($data->chrom_lots as $lot) {
            $sheet->setCellValue('A' . $index, 5);
            $sheet->setCellValue('D' . $index, '±1');
            $sheet->setCellValue('G' . $index, number_format($lot->cd_ur->filter(function($value) {
                return $value <> 0;
            })->avg(), 2));
            $sheet->setCellValue('J' . $index, number_format($lot->cd_ol->filter(function($value) {
                return $value <> 0;
            })->avg(), 2));
            $sheet->setCellValue('M' . $index, $lot->lot);

            $index++;
        }

        $sheet = $spreadsheet->getSheetByName("Position");

        $sheet->setCellValue('D9', substr($data->ar_info->machine, 4, 1) . '_' . $data->ar_info->lot);
        $sheet->setCellValue('D10', $data->ar_info->created_at->format('m/d/Y'));
        $sheet->setCellValue('B55', Carbon::now()->format('m/d/Y'));
        $sheet->setCellValue('K55', $qa ? $qa->name : auth()->user()->name);

        $index = 15;
        foreach ($data->serials as $serial) {
            $sheet->setCellValue('C' . $index, $serial->id);
            $sheet->setCellValue('G' . $index, $serial->wafer->rejected ? 'Missing' : substr($serial->wafer->processes[BlockHelper::BLOCK_ARC]->position ?? '?', 0, 1));
            $sheet->setCellValue('K' . $index, str_replace('-r', '', $serial->wafer_id ?? 'Missing'));
            $sheet->setCellValue('M' . $index, $serial->wafer->order->supplier);
            $sheet->setCellValue('N' . $index, $serial->wafer->processes[BlockHelper::BLOCK_CHROMIUM_COATING]->lot ?? 'Missing');
            $sheet->setCellValue('O' . $index, $serial->wafer->processes[BlockHelper::BLOCK_CHROMIUM_COATING]->machine ?? 'Missing');
            $sheet->setCellValue('P' . $index, $serial->wafer->processes[BlockHelper::BLOCK_LITHO]->machine ?? 'Missing');
            $sheet->setCellValue('Q' . $index, $serial->wafer->processes[BlockHelper::BLOCK_ARC]->machine ?? 'Missing');

            $index++;
        }

        $sheet = $spreadsheet->getSheetByName("Kurve");
        $files = CoaHelper::checkFiles($data->ar_info);

        $charIndex = 'C';
        foreach ($files as $file) {
            $file_data = CoaHelper::readFile($file->file, $file->type);

            if (!empty($file_data)) {
                $index = 4;
                foreach ($file_data as $line) {
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
        $writer->save(public_path('tmp\coa_' . $order->id . '.xlsx'));
        $spreadsheet->disconnectWorksheets();

        if ($final) {
            if(!Storage::disk('s')->exists(env('PT_COA_BASE_PATH') . '\\' . Carbon::now()->year . '\\' . $order->po . '_' . $order->po_cust))
                Storage::disk('s')->makeDirectory(env('PT_COA_BASE_PATH') . '\\' . Carbon::now()->year . '\\' . $order->po . '_' . $order->po_cust);

            File::move(public_path('tmp\coa_' . $order->id . '.xlsx'), '\\\\opticsbalzers.local\data\\' . env('PT_COA_BASE_PATH') . '\\' . Carbon::now()->year . '\\' . $order->po . '_' . $order->po_cust . '\\CoA_' . $data->serials->first()->id . '_'. $data->serials->last()->id . '.xlsx');
            File::delete('\\\\opticsbalzers.local\data\\' . env('PT_COA_BASE_PATH_TEMP') . '\\' . $data->serials->first()->id . '_' . $data->serials->last()->id . '.xlsx');
        } else {
            File::move(public_path('tmp\coa_' . $order->id . '.xlsx'), '\\\\opticsbalzers.local\data\\' . env('PT_COA_BASE_PATH_TEMP') . '\\' . $data->serials->first()->id . '_' . $data->serials->last()->id . '.xlsx');
        }

        return true;
    }
}
