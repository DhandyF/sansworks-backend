<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'email')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('users');

                if (isset($indexes['users_email_unique'])) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->dropUnique(['email']);
                    });
                }

                Schema::table('users', function (Blueprint $table) {
                    $table->string('email')->nullable()->change();
                });

                if (!isset($indexes['users_email_unique'])) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->unique('email');
                    });
                }
            }

            if (Schema::hasColumn('users', 'name')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('name')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
        });
    }
};