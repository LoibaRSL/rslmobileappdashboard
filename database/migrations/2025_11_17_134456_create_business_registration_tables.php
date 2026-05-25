<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('application_type')->default('New');
            $table->string('registration_type')->default('NEW');
            $table->string('document_locator');
            $table->date('receive_date');
            $table->string('old_tin')->nullable();
            $table->string('new_tin')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('business_type');
            $table->string('business_type_display');
            $table->string('title')->nullable();
            $table->string('registration_number')->nullable();
            $table->boolean('is_sole_trader')->default(false);
            $table->json('name_structure')->nullable();
            $table->json('structured_postal_address')->nullable();
            $table->json('structured_physical_address')->nullable();
            $table->json('structured_phones')->nullable();
            $table->json('phone_details')->nullable();
            $table->string('email');
            $table->string('primary_phone')->nullable();
            $table->json('trade_details')->nullable();
            $table->json('principal_details')->nullable();
            $table->json('directors_partners')->nullable();
            $table->json('accountant_details')->nullable();
            $table->json('nominated_officer_details')->nullable();
            $table->json('personal_identification')->nullable();
            $table->json('sole_trader_details')->nullable();
            $table->json('file_attachments')->nullable();
            $table->integer('proof_of_trading_files_count')->default(0);
            $table->integer('contract_vat_files_count')->default(0);
            $table->boolean('has_antenuptial_file')->default(false);
            $table->boolean('declaration_accepted')->default(false);
            $table->string('declaration_name')->nullable();
            $table->string('declaration_capacity')->nullable();
            $table->string('declaration_signature')->nullable();
            $table->date('declaration_date')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'additional_info_required',
                'approved',
                'rejected',
                'registered',
            ])->default('submitted');
            $table->text('review_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->dateTime('sms_sent_at')->nullable();
            $table->string('sms_status')->nullable();
            $table->text('sms_error')->nullable();
            $table->string('submission_ip')->nullable();
            $table->string('submission_device')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('business_registration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')->constrained()->cascadeOnDelete();
            $table->string('file_type');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('disk')->default('public');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('business_registration_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('description');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('business_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tin')->nullable();
            $table->string('amendment_tin')->nullable();
            $table->string('reference_number')->unique();
            $table->string('document_locator');
            $table->date('receive_date');
            $table->string('application_type')->default('Amendment');
            $table->enum('amendment_type', ['NEW', 'UPDATE'])->default('NEW');
            $table->json('amended_sections')->nullable();
            $table->json('amendment_data')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'additional_info_required',
                'approved',
                'rejected',
                'processed',
                'applied',
            ])->default('submitted');
            $table->text('review_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submission_ip')->nullable();
            $table->string('submission_device')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('business_amendment_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_amendment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_type');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('disk')->default('public');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('business_amendment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_amendment_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('description');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_amendment_histories');
        Schema::dropIfExists('business_amendment_files');
        Schema::dropIfExists('business_amendments');
        Schema::dropIfExists('business_registration_histories');
        Schema::dropIfExists('business_registration_files');
        Schema::dropIfExists('business_registrations');
    }
};
