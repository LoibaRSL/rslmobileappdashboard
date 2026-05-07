<?php

// app/Models/BankingDetail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_registration_id',
        'bank_code',
        'bank_country',
        'account_holder_name',
        'branch',
        'account_number',
        'account_type',
        'swift_code',
        'branch_code',
        'file_path',
    ];

    public function registration()
    {
        return $this->belongsTo(TinRegistration::class, 'tin_registration_id');
    }

    // Accessor to get full bank name
    public function getBankNameAttribute()
    {
        $banks = [
            'FNB' => 'First National Bank Lesotho',
            'LCS' => 'Lephola Credit and Savings',
            'NED' => 'Nedbank Lesotho',
            'OTHE' => 'OTHER',
            'POST' => 'Lesotho PostBank',
            'STD' => 'Standard Lesotho Bank',
            'UBT' => 'U Bank(TEBA)',
        ];

        return $banks[$this->bank_code] ?? $this->bank_code;
    }
}
