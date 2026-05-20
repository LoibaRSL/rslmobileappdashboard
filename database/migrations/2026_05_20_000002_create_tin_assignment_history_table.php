// database/migrations/2026_05_20_000002_create_tin_assignment_history_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tin_assignment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tin_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('assigned_to')->constrained('users');
            $table->enum('action', ['assign', 'reassign', 'auto_assign'])->default('assign');
            $table->string('previous_assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_assignment_history');
    }
};