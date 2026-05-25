// database/migrations/2026_05_20_000001_add_ds_fields_to_tin_registrations.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tin_registrations', 'assigned_to')) {
            Schema::table('tin_registrations', function (Blueprint $table) {
                $table->string('assigned_to')->nullable();
            });
        }
    }

    public function down(): void
    {
        // The dump schema owns the assigned_to column, so this migration is intentionally non-destructive.
    }
};
