<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL with IF NOT EXISTS to avoid conflicts

        // activity_logs table - HIGH PRIORITY
        // User filtering and audit trail performance
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_action_index ON activity_logs (action)');
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_subject_type_index ON activity_logs (subject_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_subject_type_subject_id_index ON activity_logs (subject_type, subject_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS activity_logs_created_at_index ON activity_logs (created_at)');

        // deposit_cutting_results table - CRITICAL (Payslip Performance)
        // Payslip query optimization
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_tailor_id_index ON deposit_cutting_results (tailor_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_brand_id_index ON deposit_cutting_results (brand_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_article_id_index ON deposit_cutting_results (article_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_deposit_date_index ON deposit_cutting_results (deposit_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_status_index ON deposit_cutting_results (status)');
        DB::statement('CREATE INDEX IF NOT EXISTS deposit_cutting_results_cutting_distribution_id_index ON deposit_cutting_results (cutting_distribution_id)');

        // cutting_distributions table - HIGH PRIORITY
        // Production workflow performance
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_tailor_id_index ON cutting_distributions (tailor_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_brand_id_index ON cutting_distributions (brand_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_cutting_result_id_index ON cutting_distributions (cutting_result_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_taken_date_index ON cutting_distributions (taken_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_deadline_date_index ON cutting_distributions (deadline_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_distributions_brand_id_tailor_id_index ON cutting_distributions (brand_id, tailor_id)');

        // repairs table - HIGH PRIORITY (QC Workflow)
        // Repair workflow performance
        DB::statement('CREATE INDEX IF NOT EXISTS repairs_tailor_id_index ON repairs (tailor_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS repairs_brand_id_index ON repairs (brand_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS repairs_article_id_index ON repairs (article_id)');
        // status index already exists in original migration
        DB::statement('CREATE INDEX IF NOT EXISTS repairs_taken_date_index ON repairs (taken_date)');

        // repair_deposits table - MEDIUM PRIORITY
        // Already has deposit_date index, add additional indexes:
        DB::statement('CREATE INDEX IF NOT EXISTS repair_deposits_tailor_id_index ON repair_deposits (tailor_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS repair_deposits_repair_id_index ON repair_deposits (repair_id)');

        // cutting_results table - MEDIUM PRIORITY
        // Cutting workflow performance
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_results_pre_order_id_index ON cutting_results (pre_order_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_results_cutting_date_index ON cutting_results (cutting_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_results_brand_id_index ON cutting_results (brand_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS cutting_results_article_id_index ON cutting_results (article_id)');

        // pre_orders table - LOW PRIORITY
        // Pre-order management
        DB::statement('CREATE INDEX IF NOT EXISTS pre_orders_brand_id_index ON pre_orders (brand_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS pre_orders_pre_order_date_index ON pre_orders (pre_order_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS pre_orders_deadline_date_index ON pre_orders (deadline_date)');
        // status column doesn't exist in pre_orders table
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // activity_logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_action_index');
            $table->dropIndex('activity_logs_subject_type_index');
            $table->dropIndex('activity_logs_subject_type_subject_id_index');
            $table->dropIndex('activity_logs_created_at_index');
        });

        // deposit_cutting_results table
        Schema::table('deposit_cutting_results', function (Blueprint $table) {
            $table->dropIndex('deposit_cutting_results_tailor_id_index');
            $table->dropIndex('deposit_cutting_results_brand_id_index');
            $table->dropIndex('deposit_cutting_results_article_id_index');
            $table->dropIndex('deposit_cutting_results_deposit_date_index');
            $table->dropIndex('deposit_cutting_results_status_index');
            $table->dropIndex('deposit_cutting_results_cutting_distribution_id_index');
        });

        // cutting_distributions table
        Schema::table('cutting_distributions', function (Blueprint $table) {
            $table->dropIndex('cutting_distributions_tailor_id_index');
            $table->dropIndex('cutting_distributions_brand_id_index');
            $table->dropIndex('cutting_distributions_cutting_result_id_index');
            $table->dropIndex('cutting_distributions_taken_date_index');
            $table->dropIndex('cutting_distributions_deadline_date_index');
            $table->dropIndex('cutting_distributions_brand_id_tailor_id_index');
        });

        // repairs table (skip status index - it existed before)
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropIndex('repairs_tailor_id_index');
            $table->dropIndex('repairs_brand_id_index');
            $table->dropIndex('repairs_article_id_index');
            $table->dropIndex('repairs_taken_date_index');
        });

        // repair_deposits table
        Schema::table('repair_deposits', function (Blueprint $table) {
            $table->dropIndex('repair_deposits_tailor_id_index');
            $table->dropIndex('repair_deposits_repair_id_index');
        });

        // cutting_results table
        Schema::table('cutting_results', function (Blueprint $table) {
            $table->dropIndex('cutting_results_pre_order_id_index');
            $table->dropIndex('cutting_results_cutting_date_index');
            $table->dropIndex('cutting_results_brand_id_index');
            $table->dropIndex('cutting_results_article_id_index');
        });

        // pre_orders table
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropIndex('pre_orders_brand_id_index');
            $table->dropIndex('pre_orders_pre_order_date_index');
            $table->dropIndex('pre_orders_deadline_date_index');
        });
    }
};
