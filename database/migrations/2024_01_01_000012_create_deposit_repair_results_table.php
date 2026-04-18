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
        Schema::create('deposit_repair_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_distribution_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->date('deposit_date');
            $table->unsignedInteger('total_repaired')->default(0);
            $table->unsignedInteger('product_to_repair_left')->nullable();
            $table->string('repair_quality_rating')->default('good');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deposit_date', 'repair_quality_rating']);
            $table->index('tailor_id');
            $table->index('repair_distribution_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_repair_results');
    }
};
