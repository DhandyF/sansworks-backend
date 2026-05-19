<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pre_order_id');
            $table->date('shipment_date');
            $table->integer('total_shipment')->default(0);
            $table->timestamps();

            $table->foreign('pre_order_id')->references('id')->on('pre_orders')->onDelete('cascade');
            $table->index(['pre_order_id', 'shipment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};