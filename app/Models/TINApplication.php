<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TINApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        // Registration Info
        'legacyTIN', 'etpmTIN', 'regType', 'effectiveDate',

        // Main Section
        'title', 'surname', 'forname', 'name', 'maidenName',

        // ID and Residency
        'proofID', 'dateOfBirth', 'passportNum', 'passportExpiryDate',
        'countryOfIssue', 'otherID', 'otherIDNumber', 'driversExpiryDate',
        'otherCountryOfIssue', 'countryOfBirth', 'countryOfRes', 'countryOfCit',

        // Correspondence
        'postCountry', 'postType', 'postNum', 'postPostal',
        'postAddress1', 'postCity', 'postCounty',
        'phyCountry', 'phyAddress1', 'phyCity', 'phyCounty', 'phyPostal',
        'phoneType', 'phoneCode', 'phoneNumber', 'email',

        // Miscellaneous
        'employer', 'maritalStatus', 'condMarriage', 'spouseTIN',
        'spouseName', 'spouseMaiden', 'spousePerID',

        // Bank
        'accountName', 'bank', 'branch', 'bankCountry',
        'bankAccountNum', 'bankAccountType', 'swiftCode', 'branchCode',

        // Mobile
        'mobileMoney', 'mobileMoneyNumber', 'accountAutoPayId',

        // Response
        'tin', 'files', 'employers',
    ];

    
    protected $casts = [
        'employers' => 'array',
    ];


}
