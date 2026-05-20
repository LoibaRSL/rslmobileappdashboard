// database/migrations/2026_05_20_000001_add_ds_fields_to_tin_registrations.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tin_registrations', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tin_registrations', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn(['assigned_to_user_id', 'assigned_at']);
        });
    }
};