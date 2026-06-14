<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_deposits', function (Blueprint $table) {
            $table->decimal('default_charge_per_pcs', 10, 2)->nullable()->after('charge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('repair_deposits', function (Blueprint $table) {
            $table->dropColumn('default_charge_per_pcs');
        });
    }
};
