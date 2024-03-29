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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->unsignedBigInteger('mapping_id');
            $table->string('article', 50);
            $table->string('article_desc', 200);
            $table->string('article_cust', 100);
            $table->string('customer', 50);
            $table->string('supplier', 100)->nullable();
            $table->string('po', 30)->nullable();
            $table->integer('po_pos')->nullable();
            $table->string('po_cust', 50)->nullable();
            $table->date('packaging_date')->nullable();
            $table->timestamps();

            $table->foreign('mapping_id')->references('id')->on('mappings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
