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
        Schema::create('wafers', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('order_id', 20)->nullable();
            $table->string('box', 20)->nullable();
            $table->boolean('rejected')->default(0);
            $table->string('rejection_reason')->nullable();
            $table->string('rejection_position')->nullable();
            $table->string('rejection_avo')->nullable();
            $table->string('rejection_order')->nullable();
            $table->string('raw_lot_supplier', 200)->nullable();
            $table->integer('reworks')->default(0);
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
        Schema::dropIfExists('wafers');
    }
};
