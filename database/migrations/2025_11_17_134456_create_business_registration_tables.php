<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Main business registrations table
        Schema::create('business_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('application_type')->default('New');
            $table->string('old_tin')->nullable();
            $table->string('new_tin')->nullable();
            $table->string('legal_name');
            $table->string('business_type');
            $table->string('title')->nullable();
            $table->string('registration_number')->nullable();
            $table->json('trade_details')->nullable();
            $table->json('principal_details')->nullable();
            $table->boolean('is_sole_trader')->default(false);
            
            // NEW: Name structure fields
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->string('maiden_name')->nullable();
            
            $table->string('reference_number')->unique();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // Business contact details table - UPDATED with structured fields
        Schema::create('business_contact_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bcd_business_reg_id_foreign');
            
            // Legacy fields (keep for backward compatibility)
            $table->text('postal_address');
            $table->string('postal_code');
            $table->text('physical_address');
            $table->string('chief_street_name');
            $table->string('village')->nullable();
            $table->string('town');
            $table->string('district');
            $table->string('office_phone')->nullable();
            $table->string('cell_phone');
            $table->string('fax1')->nullable();
            $table->string('fax2')->nullable();
            $table->string('email');
            
            // NEW: Structured postal address
            $table->string('postal_country')->nullable();
            $table->text('postal_address1');
            $table->text('postal_address2')->nullable();
            $table->text('postal_address3')->nullable();
            $table->text('postal_address4')->nullable();
            $table->string('postal_city');
            $table->string('postal_county');
            
            // NEW: Structured physical address
            $table->string('physical_country')->nullable();
            $table->text('physical_address1');
            $table->text('physical_address2')->nullable();
            $table->text('physical_address3')->nullable();
            $table->text('physical_address4')->nullable();
            $table->string('physical_city');
            $table->string('physical_county');
            $table->string('physical_postal_code')->nullable();
            
            $table->timestamps();
        });

        // NEW: Business structured phones table
        Schema::create('business_structured_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bsp_business_reg_id_foreign');
            $table->string('phone_type'); // CEL1, CEL2, TEL, FAX
            $table->string('phone_code')->default('266');
            $table->string('phone_number');
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Business accountant details table - UPDATED with structured fields
        Schema::create('business_accountant_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bad_business_reg_id_foreign');
            $table->string('name');
            $table->string('tin');
            
            // Legacy fields
            $table->text('postal_address');
            $table->string('postal_code');
            $table->text('physical_address');
            $table->string('chief_street_name');
            $table->string('village')->nullable();
            $table->string('town');
            $table->string('district');
            $table->string('office_phone')->nullable();
            $table->string('cell_phone');
            $table->string('fax1')->nullable();
            $table->string('fax2')->nullable();
            $table->string('email');
            
            // NEW: Structured fields
            $table->string('postal_country')->nullable();
            $table->text('postal_address1');
            $table->text('postal_address2')->nullable();
            $table->text('postal_address3')->nullable();
            $table->text('postal_address4')->nullable();
            $table->string('postal_city');
            $table->string('postal_county');
            
            $table->string('physical_country')->nullable();
            $table->text('physical_address1');
            $table->text('physical_address2')->nullable();
            $table->text('physical_address3')->nullable();
            $table->text('physical_address4')->nullable();
            $table->string('physical_city');
            $table->string('physical_county');
            $table->string('physical_postal_code')->nullable();
            
            $table->timestamps();
        });

        // Business nominated officer details table - UPDATED with structured fields
        Schema::create('business_nominated_officer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bnod_business_reg_id_foreign');
            $table->string('name');
            $table->string('tin');
            
            // Legacy fields
            $table->text('postal_address');
            $table->string('postal_code');
            $table->text('physical_address');
            $table->string('chief_street_name');
            $table->string('village')->nullable();
            $table->string('town');
            $table->string('district');
            $table->string('office_phone')->nullable();
            $table->string('cell_phone');
            $table->string('fax1')->nullable();
            $table->string('fax2')->nullable();
            $table->string('email');
            
            // NEW: Structured fields
            $table->string('postal_country')->nullable();
            $table->text('postal_address1');
            $table->text('postal_address2')->nullable();
            $table->text('postal_address3')->nullable();
            $table->text('postal_address4')->nullable();
            $table->string('postal_city');
            $table->string('postal_county');
            
            $table->string('physical_country')->nullable();
            $table->text('physical_address1');
            $table->text('physical_address2')->nullable();
            $table->text('physical_address3')->nullable();
            $table->text('physical_address4')->nullable();
            $table->string('physical_city');
            $table->string('physical_county');
            $table->string('physical_postal_code')->nullable();
            
            $table->timestamps();
        });

        // Business director partners table
        Schema::create('business_director_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bdp_business_reg_id_foreign');
            $table->string('name');
            $table->string('tin');
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Business bank details table - UPDATED with new fields
        Schema::create('business_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bbd_business_reg_id_foreign');
            $table->string('account_holder');
            $table->string('country');
            $table->string('bank_name');
            $table->string('branch');
            $table->string('account_number');
            $table->string('account_type');
            $table->string('swift_code')->nullable();
            
            // NEW: Bank and branch codes
            $table->string('bank_code')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('account_auto_pay_id')->nullable();
            
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Business mobile money details table - UPDATED with new fields
        Schema::create('business_mobile_money_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bmmd_business_reg_id_foreign');
            $table->string('mobile_money_type');
            
            // NEW: Mobile number and auto pay ID
            $table->string('mobile_number')->nullable();
            $table->string('account_auto_pay_id')->nullable();
            
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // NEW: Business personal identification table (for sole traders)
        Schema::create('business_personal_identification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bpi_business_reg_id_foreign');
            $table->date('date_of_birth')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('country_of_issue')->nullable();
            $table->string('other_id_type')->nullable();
            $table->string('other_id_number')->nullable();
            $table->date('other_id_expiry_date')->nullable();
            $table->string('other_country_of_issue')->nullable();
            $table->string('country_of_birth')->nullable();
            $table->string('country_of_residence')->nullable();
            $table->string('country_of_citizenship')->nullable();
            $table->timestamps();
        });

        // NEW: Business sole trader details table
        Schema::create('business_sole_trader_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bstd_business_reg_id_foreign');
            $table->string('marital_status')->nullable(); // SINGLE, MARRIED, DIVORCED, WIDOWED
            $table->string('marriage_condition')->nullable();
            $table->string('spouse_tin')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_maiden_name')->nullable();
            $table->string('spouse_per_id')->nullable();
            $table->json('employers')->nullable(); // Array of previous employers
            $table->timestamps();
        });

        // Business VAT details table
        Schema::create('business_vat_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bvd_business_reg_id_foreign');
            $table->boolean('register_for_vat')->default(false);
            $table->date('vat_effective_date')->nullable();
            $table->json('vat_reasons')->nullable();
            $table->string('business_status')->nullable();
            $table->string('previous_owner_name')->nullable();
            $table->text('previous_owner_address')->nullable();
            $table->string('previous_owner_tin')->nullable();
            $table->timestamps();
        });

        // Business PAYE details table
        Schema::create('business_paye_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bpd_business_reg_id_foreign');
            $table->boolean('register_for_paye')->default(false);
            $table->date('paye_employer_date')->nullable();
            $table->integer('current_employees')->nullable();
            $table->decimal('min_annual_salary', 15, 2)->nullable();
            $table->decimal('max_annual_salary', 15, 2)->nullable();
            $table->timestamps();
        });

        // Business FBT details table
        Schema::create('business_fbt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bfd_business_reg_id_foreign');
            $table->boolean('register_for_fbt')->default(false);
            $table->json('fringe_benefit_types')->nullable();
            $table->timestamps();
        });

        // Business WHT details table
        Schema::create('business_wht_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bwd_business_reg_id_foreign');
            $table->boolean('register_for_wht')->default(false);
            $table->json('withholding_types')->nullable();
            $table->text('services_description')->nullable();
            $table->string('other_withholding_type')->nullable();
            $table->timestamps();
        });

        // Business ANTL details table
        Schema::create('business_antl_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bad_antl_business_reg_id_foreign');
            $table->boolean('register_for_antl')->default(false);
            $table->date('antl_effective_date')->nullable();
            $table->timestamps();
        });

        // Business plastic levy details table
        Schema::create('business_plastic_levy_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bpld_business_reg_id_foreign');
            $table->boolean('register_for_plastic_levy')->default(false);
            $table->string('plastic_levy_number')->nullable();
            $table->date('plastic_levy_effective_date')->nullable();
            $table->timestamps();
        });

        // NEW: Business SBT (Small Business Tax) details table
        Schema::create('business_sbt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bsd_business_reg_id_foreign');
            $table->date('sbt_effective_date')->nullable();
            $table->timestamps();
        });

        // Business declaration details table
        Schema::create('business_declaration_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bdd_business_reg_id_foreign');
            $table->boolean('declaration_accepted')->default(false);
            $table->string('declaration_name');
            $table->string('declaration_capacity');
            $table->string('declaration_signature');
            $table->date('declaration_date');
            $table->timestamps();
        });

        // Business registration files table
        Schema::create('business_registration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('brf_business_reg_id_foreign');
            $table->string('file_type'); // proof_of_trading, contract_vat, antenuptial, plastic_levy
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('file_extension');
            $table->timestamps();
        });

        // NEW: Business SOAP integration table
        Schema::create('business_soap_integration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_registration_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->name('bsi_business_reg_id_foreign');
            $table->string('soap_status')->default('pending'); // pending, sent, success, failed
            $table->text('soap_request')->nullable();
            $table->text('soap_response')->nullable();
            $table->json('missing_fields')->nullable(); // Fields that need to be completed
            $table->string('soap_reference')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Drop tables in reverse order to handle foreign key constraints
        Schema::dropIfExists('business_soap_integration');
        Schema::dropIfExists('business_registration_files');
        Schema::dropIfExists('business_declaration_details');
        Schema::dropIfExists('business_sbt_details');
        Schema::dropIfExists('business_plastic_levy_details');
        Schema::dropIfExists('business_antl_details');
        Schema::dropIfExists('business_wht_details');
        Schema::dropIfExists('business_fbt_details');
        Schema::dropIfExists('business_paye_details');
        Schema::dropIfExists('business_vat_details');
        Schema::dropIfExists('business_sole_trader_details');
        Schema::dropIfExists('business_personal_identification');
        Schema::dropIfExists('business_mobile_money_details');
        Schema::dropIfExists('business_bank_details');
        Schema::dropIfExists('business_director_partners');
        Schema::dropIfExists('business_nominated_officer_details');
        Schema::dropIfExists('business_accountant_details');
        Schema::dropIfExists('business_structured_phones');
        Schema::dropIfExists('business_contact_details');
        Schema::dropIfExists('business_registrations');
    }
};