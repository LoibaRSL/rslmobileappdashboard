<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riit_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('person_id');
            $table->string('tin');
            $table->string('return_type')->default('normal');
            $table->boolean('is_amendment')->default(false);
            $table->string('tax_year_end')->nullable();
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->string('tax_type')->nullable();
            $table->string('document_locator')->nullable();
            $table->date('receive_date')->nullable();
            $table->json('form_data')->nullable();
            $table->json('submission_payload')->nullable();
            $table->decimal('total_chargeable_income', 18, 2)->default(0);
            $table->decimal('tax_due', 18, 2)->default(0);
            $table->decimal('tax_overpaid', 18, 2)->default(0);
            $table->boolean('claim_repayment')->default(false);
            $table->boolean('declaration_accepted')->default(false);
            $table->string('declarant_name')->nullable();
            $table->string('status')->default('submitted_to_database');
            $table->string('soap_status')->default('pending');
            $table->string('soap_message')->nullable();
            $table->longText('soap_request')->nullable();
            $table->longText('soap_response')->nullable();
            $table->unsignedSmallInteger('soap_http_status')->nullable();
            $table->timestamp('soap_submitted_at')->nullable();
            $table->text('nil_reason')->nullable();
            $table->string('submission_ip')->nullable();
            $table->string('submission_device')->nullable();
            $table->timestamps();
        });

        Schema::create('riit_return_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riit_return_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('disk')->default('public');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riit_return_attachments');
        Schema::dropIfExists('riit_returns');
    }
};
