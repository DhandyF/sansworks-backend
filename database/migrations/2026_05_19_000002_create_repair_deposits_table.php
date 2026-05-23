<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('repair_id');
            $table->uuid('tailor_id');
            $table->integer('total_deposit')->default(0);
            $table->date('deposit_date');
            $table->decimal('charge_amount', 10, 2)->default(0);
            $table->integer('charge_percent')->default(0);
            $table->timestamps();

            $table->foreign('repair_id')->references('id')->on('repairs')->onDelete('cascade');
            $table->foreign('tailor_id')->references('id')->on('tailors')->onDelete('cascade');
            $table->index('deposit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_deposits');
    }
};