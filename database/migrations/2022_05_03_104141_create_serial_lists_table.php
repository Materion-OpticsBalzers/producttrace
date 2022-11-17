<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('serial_lists', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('article', 50);
            $table->string('article_cust', 100);
            $table->string('format', 100);
            $table->string('po_cust', 50);
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serial_lists');
    }
};
