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
        Schema::create('qc_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_cutting_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_to_repair')->default(0);
            $table->date('qc_date');
            $table->foreignId('qc_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('defect_details')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['qc_date', 'tailor_id']);
            $table->index('qc_by');
            $table->index('deposit_cutting_result_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_results');
    }
};
