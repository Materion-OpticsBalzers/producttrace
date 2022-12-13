<?php

namespace App\Console\Commands;

use App\Models\Data\Order;
use App\Models\Generic\Mapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importiert Aufträge aus dem ERP System';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach(Mapping::whereNotNull('articles')->get() as $mapping) {
            $results = \DB::connection('oracle')->select("SELECT PRDNR, PRD.ARTNR, PRD.KADRNR, KART.KNDARTNR, ART.KURZBEZ FROM PROD_ERP_001.PRD
                LEFT JOIN PROD_ERP_001.KART ON KART.ARTNR = PRD.ARTNR AND KART.KADRNR = PRD.KADRNR
                LEFT JOIN PROD_ERP_001.ART ON ART.ARTNR = PRD.ARTNR
                WHERE PRD.ARTNR IN({$mapping->articles}) AND PRD.STATUS IN(3, 4)");

            foreach($results as $result) {
                $raw_glass = DB::connection('oracle')->select("SELECT ARTNR FROM PROD_ERP_001.PRDPOS
                WHERE PRDNR = '{$result->prdnr}'");

                $supplier = '';
                switch ($raw_glass[0]->artnr) {
                    case '204908':
                        $supplier = 'Fujitok Nikon';
                        break;
                    case '218503':
                        $supplier = 'AGC';
                        break;
                    case '219452':
                        $supplier = 'Corning';
                        break;
                    case '219497':
                        $supplier = 'Fujitok Ohara';
                        break;
                    default:
                        break;
                }

                Order::firstOrCreate(['id' => $result->prdnr],[
                    'id' => $result->prdnr,
                    'mapping_id' => $mapping->id,
                    'article' => $result->artnr,
                    'article_desc' => $result->kurzbez,
                    'article_cust' => $result->kndartnr,
                    'customer' => $result->kadrnr,
                    'supplier' => $supplier
                ]);
            }
        }
    }
}
