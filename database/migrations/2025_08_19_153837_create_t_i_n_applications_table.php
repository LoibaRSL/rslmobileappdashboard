<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_i_n_applications', function (Blueprint $table) {
            $table->id();
            $table->string('legacyTIN')->nullable();
            $table->string('etpmTIN')->nullable();
            $table->string('regType');
            $table->date('effectiveDate');
            $table->string('title');
            $table->string('surname');
            $table->string('forname');
            $table->string('name');
            $table->string('maidenName')->nullable();
            $table->string('proofID');
            $table->date('dateOfBirth');
            $table->string('passportNum')->nullable();
            $table->date('passportExpiryDate')->nullable();
            $table->string('countryOfIssue');
            $table->string('otherID')->nullable();
            $table->string('otherIDNumber')->nullable();
            $table->date('driversExpiryDate')->nullable();
            $table->string('otherCountryOfIssue')->nullable();
            $table->string('countryOfBirth');
            $table->string('countryOfRes');
            $table->string('countryOfCit');
            $table->string('postCountry');
            $table->string('postType');
            $table->string('postNum');
            $table->string('postPostal');
            $table->string('postAddress1');
            $table->string('postCity');
            $table->string('postCounty');
            $table->string('phyCountry');
            $table->string('phyAddress1');
            $table->string('phyCity');
            $table->string('phyCounty');
            $table->string('phyPostal');
            $table->string('phoneType');
            $table->string('phoneCode');
            $table->string('phoneNumber');
            $table->string('email');
            $table->json('employers')->nullable();
            $table->string('maritalStatus');
            $table->string('condMarriage')->nullable();
            $table->string('spouseTIN')->nullable();
            $table->string('spouseName')->nullable();
            $table->string('spouseMaiden')->nullable();
            $table->string('spousePerID')->nullable();
            $table->string('accountName')->nullable();
            $table->string('bank')->nullable();
            $table->string('branch')->nullable();
            $table->string('bankCountry')->nullable();
            $table->string('bankAccountNum')->nullable();
            $table->string('bankAccountType')->nullable();
            $table->string('swiftCode')->nullable();
            $table->string('branchCode')->nullable();
            $table->string('mobileMoney');
            $table->string('mobileMoneyNumber');
            $table->string('accountAutoPayId')->nullable();
            $table->json('files')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('review_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('generated_tin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_i_n_applications');
    }
};
