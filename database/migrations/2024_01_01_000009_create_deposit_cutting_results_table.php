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
        Schema::create('deposit_cutting_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_distribution_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('total_sewing_result')->default(0);
            $table->unsignedInteger('cutting_left')->nullable();
            $table->decimal('sewing_price', 12, 2)->nullable();
            $table->date('deposit_date');
            $table->string('status')->default('done');
            $table->text('quality_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deposit_date', 'status']);
            $table->index('tailor_id');
            $table->index('cutting_distribution_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_cutting_results');
    }
};
