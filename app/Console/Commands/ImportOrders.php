<?php

namespace App\Console\Commands;

use App\Models\Data\Order;
use App\Models\Generic\Mapping;
use Illuminate\Console\Command;

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
            $results = \DB::connection('oracle')->select("SELECT PRDNR, ARTNR, KADRNR FROM PROD_ERP_001.PRD
                WHERE ARTNR IN({$mapping->articles}) AND STATUS IN(3, 4)");

            foreach($results as $result) {
                Order::firstOrCreate([
                    'id' => $result->prdnr,
                    'mapping_id' => $mapping->id,
                    'article' => $result->artnr,
                    'customer' => $result->kadrnr
                ]);
            }
        }
    }
}
