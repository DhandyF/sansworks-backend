<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing columns if present (may have been added with wrong constraints)
        $existing = array_filter(
            ['charge_amount', 'charge_percent', 'default_charge_per_pcs'],
            fn ($col) => Schema::hasColumn('deposit_cutting_results', $col)
        );
        if (!empty($existing)) {
            Schema::table('deposit_cutting_results', function (Blueprint $table) use ($existing) {
                $table->dropColumn(array_values($existing));
            });
        }

        Schema::table('deposit_cutting_results', function (Blueprint $table) {
            $table->decimal('charge_amount', 15, 2)->default(0)->after('status');
            $table->integer('charge_percent')->nullable()->after('charge_amount');
            $table->decimal('default_charge_per_pcs', 15, 2)->nullable()->after('charge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_cutting_results', function (Blueprint $table) {
            $table->dropColumn(['charge_amount', 'charge_percent', 'default_charge_per_pcs']);
        });
    }
};
