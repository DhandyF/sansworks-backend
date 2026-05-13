<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('cutting_result_id')->constrained('cutting_results')->cascadeOnDelete();
            $table->foreignUuid('tailor_id')->constrained('tailors')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignUuid('size_id')->constrained('sizes')->cascadeOnDelete();
            $table->integer('total_cutting');
            $table->date('taken_date');
            $table->date('deadline_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_distributions');
    }
};