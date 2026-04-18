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
        Schema::create('repair_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('tailor_id')->constrained()->onDelete('restrict');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->foreignId('article_id')->constrained()->onDelete('restrict');
            $table->foreignId('size_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('total_to_repair')->default(0);
            $table->date('taken_date');
            $table->date('deadline_repair_date');
            $table->string('repair_number')->unique();
            $table->string('repair_type')->default('minor');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['taken_date', 'deadline_repair_date']);
            $table->index('repair_number');
            $table->index('repair_type');
            $table->index('tailor_id');
            $table->index('qc_result_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_distributions');
    }
};
