<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_operation_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('subject_label')->nullable();
            $table->string('event_type');
            $table->string('channel')->nullable();
            $table->string('status')->default('info');
            $table->string('title');
            $table->text('message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_operation_events');
    }
};
