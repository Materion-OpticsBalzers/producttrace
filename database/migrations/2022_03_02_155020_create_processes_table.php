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
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 20);
            $table->unsignedBigInteger('block_id');
            $table->string('wafer_id', 100);
            $table->unsignedBigInteger('rejection_id')->nullable();
            $table->string('serial_id', 20)->nullable();
            $table->string('operator', 10)->nullable();
            $table->string('machine', 10)->nullable();
            $table->string('lot', 100)->nullable();
            $table->string('box', 20)->nullable();
            $table->string('ar_box', 20)->nullable();
            $table->string('position', 20)->nullable();
            $table->boolean('reworked')->default(0);
            $table->date('date');
            $table->boolean('transferred')->default(0);
            $table->float('cd_ol')->nullable();
            $table->float('cd_ur')->nullable();
            $table->float('x')->nullable();
            $table->float('y')->nullable();
            $table->float('z')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('block_id')->references('id')->on('blocks');
            $table->foreign('wafer_id')->references('id')->on('wafers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processes');
    }
};
