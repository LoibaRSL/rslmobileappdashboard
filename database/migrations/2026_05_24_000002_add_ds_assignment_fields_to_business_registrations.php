<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('business_registrations', 'assigned_to')) {
                $table->string('assigned_to')->nullable()->after('submission_device');
            }

            if (!Schema::hasColumn('business_registrations', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('business_registrations', 'assigned_at')) {
                $table->dateTime('assigned_at')->nullable()->after('assigned_to_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('business_registrations', 'assigned_to_user_id')) {
                $table->dropConstrainedForeignId('assigned_to_user_id');
            }

            if (Schema::hasColumn('business_registrations', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }

            if (Schema::hasColumn('business_registrations', 'assigned_to')) {
                $table->dropColumn('assigned_to');
            }
        });
    }
};
