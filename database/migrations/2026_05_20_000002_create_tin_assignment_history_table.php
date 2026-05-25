// database/migrations/2026_05_20_000002_create_tin_assignment_history_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The canonical rslstart dump does not contain tin_assignment_history.
        // Keep this migration as a no-op so existing migration history remains stable.
    }

    public function down(): void
    {
        //
    }
};
