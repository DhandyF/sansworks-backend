<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('pre_order_id')->constrained('pre_orders')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignUuid('size_id')->constrained('sizes')->cascadeOnDelete();
            $table->integer('total_cutting');
            $table->integer('remaining')->default(0);
            $table->date('cutting_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_results');
    }
};