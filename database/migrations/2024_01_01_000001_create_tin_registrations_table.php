<?php
// database/migrations/2024_01_01_000001_create_tin_registrations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tin_registrations', function (Blueprint $table) {
            $table->id();
            
            // Document Reference Details
            $table->string('document_locator')->nullable(); // Changed to nullable (was unique, but SQL shows nullable)
            $table->string('ref')->nullable()->unique();
            $table->date('receive_date');
            $table->date('effective_date')->default(now()); // ADDED - missing field
            $table->enum('registration_type', ['NEW', 'AMND'])->default('NEW');
            $table->string('legacy_tin')->nullable();
            $table->string('tin')->nullable()->unique();
            
            // Taxpayer Main Details
            $table->enum('title', ['MR', 'MRS', 'MISS', 'MS']);
            $table->string('surname');
            $table->string('forenames');
            $table->string('maiden_name')->nullable();
            
            // Identification and Residency
            $table->date('date_of_birth');
            $table->string('country_of_birth');
            $table->string('country_of_citizenship')->nullable();
            $table->string('country_of_residence')->nullable();
            $table->string('lesotho_id_number')->nullable();
            $table->date('lesotho_id_expiry')->nullable();
            $table->string('country_of_issue')->nullable();
            $table->enum('other_id_type', ['FRGN', 'IDSA', 'PASP'])->nullable();
            $table->string('other_id_number')->nullable();
            $table->date('other_id_expiry')->nullable();
            
            // Correspondence Details (Postal Address)
            $table->string('post_country')->nullable(); // CHANGED: made nullable
            $table->enum('post_type', ['POTH', 'PBOX', 'POST', 'PBAG'])->nullable(); // CHANGED: made nullable
            $table->string('post_number')->nullable();
            $table->string('post_code')->nullable();
            $table->string('post_address1')->nullable();
            $table->string('post_address2')->nullable();
            $table->string('post_address3')->nullable();
            $table->string('post_address4')->nullable();
            $table->string('post_district')->nullable(); // CHANGED: made nullable
            $table->string('post_city')->nullable();
            
            // Physical Address
            $table->string('physical_country');
            $table->string('street_name');
            $table->string('nearest_place')->nullable();
            $table->string('village')->nullable();
            $table->string('town')->nullable();
            $table->string('physical_district');
            $table->string('phy_postal')->nullable();
            
            // Phone Details
            $table->enum('phone_type', ['CEL1', 'CEL2', 'FAX', 'HOME', 'PFC'])->nullable(); // CHANGED: made nullable
            $table->string('phone_code')->nullable(); // CHANGED: made nullable
            $table->string('phone_number')->nullable(); // CHANGED: made nullable
            $table->string('email');
            $table->boolean('email_verified')->default(0);
            $table->string('email_verification_code')->nullable();
            
            // Marital Status - FIXED: use 'SING' not 'SINGLE'
            $table->enum('marital_status', ['SING', 'MARR', 'DIVO', 'SEPA', 'WIDO']);
            $table->enum('condition_of_marriage', ['ANTE', 'COMM'])->nullable();
            $table->string('spouse_tin')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_maiden_name')->nullable();
            $table->string('spouse_personal_id')->nullable();
            
            // Mobile Money & Banking
            $table->string('mobile_money_type')->nullable();
            $table->string('mobile_money_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_country')->nullable();
            
            // Declaration
            $table->string('printed_name')->nullable(); // CHANGED: made nullable
            $table->boolean('declaration_accepted')->default(0);
            
            // File paths (kept for backward compatibility, but registration_files table is the primary file store)
            $table->string('lesotho_id_path')->nullable();
            $table->string('passport_path')->nullable();
            $table->string('other_id_path')->nullable();
            $table->string('foreign_id_path')->nullable();
            $table->string('antenuptial_path')->nullable();
            
            // Status and Remarks
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'])->default('PENDING');
            $table->text('amendment_notes')->nullable(); // ADDED - missing field
            $table->timestamp('amendment_submitted_at')->nullable(); // ADDED - missing field
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });

        // Employers table
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tin_registration_id')->constrained('tin_registrations')->onDelete('cascade');
            $table->string('employer_name');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        // File attachments table
        Schema::create('registration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tin_registration_id')->constrained('tin_registrations')->onDelete('cascade');
            $table->string('file_type');
            $table->string('file_path');
            $table->string('file_name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('registration_files');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('tin_registrations');
    }
};