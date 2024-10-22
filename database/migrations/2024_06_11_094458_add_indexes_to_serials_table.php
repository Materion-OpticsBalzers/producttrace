<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('serials', function (Blueprint $table) {
            $table->index(['order_id', 'wafer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serials', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'wafer_id']);
        });
    }
};
