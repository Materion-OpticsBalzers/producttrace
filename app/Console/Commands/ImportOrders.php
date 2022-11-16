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
                $raw_glass = DB::connection('oracle')->select("SELECT PRD.PRDNR, PRD.ARTNR, STKLST.VARNR, STKLST.VARBEZ FROM PROD_ERP_001.PRD
                LEFT JOIN PROD_ERP_001.STKLST ON STKLST.ARTNR = PRD.ARTNR AND PRD.VARSTULI = STKLST.VARNR
                WHERE PRD.PRDNR = '$result->prdnr' AND STKLST.TYP = 'OP'");

                Order::firstOrCreate(['id' => $result->prdnr],[
                    'id' => $result->prdnr,
                    'mapping_id' => $mapping->id,
                    'article' => $result->artnr,
                    'article_desc' => $result->kurzbez,
                    'article_cust' => $result->kndartnr,
                    'customer' => $result->kadrnr,
                    'supplier' => $raw_glass[0]->varbez
                ]);
            }
        }
    }
}
