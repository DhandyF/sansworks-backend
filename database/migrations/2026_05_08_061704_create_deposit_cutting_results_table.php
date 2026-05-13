<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_cutting_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('cutting_distribution_id')->constrained('cutting_distributions')->cascadeOnDelete();
            $table->foreignUuid('tailor_id')->constrained('tailors')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignUuid('size_id')->constrained('sizes')->cascadeOnDelete();
            $table->integer('total_sewing_result');
            $table->date('deposit_date');
            $table->string('status')->default('in_progress');
            $table->text('quality_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_cutting_results');
    }
};