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
        Schema::create('cutting_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutting_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('total_cutting')->default(0);
            $table->date('taken_date');
            $table->date('deadline_date');
            $table->string('distribution_number')->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['taken_date', 'deadline_date']);
            $table->index('distribution_number');
            $table->index('tailor_id');
            $table->index('cutting_result_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cutting_distributions');
    }
};
