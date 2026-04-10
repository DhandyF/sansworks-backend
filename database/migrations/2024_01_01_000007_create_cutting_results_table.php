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
        Schema::create('cutting_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('total_cutting')->default(0);
            $table->date('cutting_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cutting_date', 'brand_id']);
            $table->index('batch_number');
            $table->index('fabric_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutting_results');
    }
};
