<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('business_registrations', 'bank_mobile_money')) {
                $table->json('bank_mobile_money')->nullable()->after('directors_partners');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('business_registrations', 'bank_mobile_money')) {
                $table->dropColumn('bank_mobile_money');
            }
        });
    }
};
