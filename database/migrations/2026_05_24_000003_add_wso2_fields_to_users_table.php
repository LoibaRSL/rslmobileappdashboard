<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wso2_id')) {
                $table->string('wso2_id')->nullable()->unique()->after('role');
            }

            if (!Schema::hasColumn('users', 'wso2_username')) {
                $table->string('wso2_username')->nullable()->after('wso2_id');
            }

            if (!Schema::hasColumn('users', 'wso2_attributes')) {
                $table->json('wso2_attributes')->nullable()->after('wso2_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wso2_attributes')) {
                $table->dropColumn('wso2_attributes');
            }

            if (Schema::hasColumn('users', 'wso2_username')) {
                $table->dropColumn('wso2_username');
            }

            if (Schema::hasColumn('users', 'wso2_id')) {
                $table->dropUnique(['wso2_id']);
                $table->dropColumn('wso2_id');
            }
        });
    }
};
