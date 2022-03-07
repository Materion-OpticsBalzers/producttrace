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
            $table->string('raw_lot', 100);
            $table->date('lot_date');
            $table->boolean('rejected')->default(0);
            $table->string('rejection_reason')->nullable();
            $table->string('rejection_postion')->nullable();
            $table->string('rejection_avo')->nullable();
            $table->string('rejection_order')->nullable();
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
