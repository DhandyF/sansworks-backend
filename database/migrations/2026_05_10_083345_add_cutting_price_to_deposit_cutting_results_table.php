<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_cutting_results', function (Blueprint $table) {
            $table->decimal('cutting_price_per_pcs', 15, 2)->default(0)->after('total_sewing_result');
            $table->decimal('total_price', 15, 2)->default(0)->after('cutting_price_per_pcs');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_cutting_results', function (Blueprint $table) {
            $table->dropColumn(['cutting_price_per_pcs', 'total_price']);
        });
    }
};