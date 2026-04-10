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
        Schema::create('daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('statistic_date')->unique();
            $table->decimal('total_fabric_input', 12, 2)->default(0);
            $table->decimal('total_fabric_cost', 14, 2)->default(0);
            $table->unsignedInteger('total_cutting_result')->default(0);
            $table->unsignedInteger('total_cutting_distribution')->default(0);
            $table->unsignedInteger('total_deposit_cutting')->default(0);
            $table->decimal('total_sewing_price', 14, 2)->default(0);
            $table->unsignedInteger('total_qc_result')->default(0);
            $table->unsignedInteger('total_qc_to_repair')->default(0);
            $table->unsignedInteger('total_repair_distribution')->default(0);
            $table->unsignedInteger('total_deposit_repair')->default(0);
            $table->unsignedInteger('active_tailors')->default(0);
            $table->unsignedInteger('active_brands')->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->unsignedInteger('overdue_orders')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('defect_rate', 5, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('statistic_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_statistics');
    }
};
