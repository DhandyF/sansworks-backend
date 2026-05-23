<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tailor_id');
            $table->uuid('brand_id');
            $table->uuid('article_id');
            $table->string('name');
            $table->integer('total_repair')->default(0);
            $table->decimal('sewing_price', 10, 2)->default(0);
            $table->date('taken_date');
            $table->date('deadline_date');
            $table->string('status')->default('in_progress');
            $table->timestamps();

            $table->foreign('tailor_id')->references('id')->on('tailors')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};