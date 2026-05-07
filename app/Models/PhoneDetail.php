<?php
// app/Models/PhoneDetail.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_registration_id',
        'phone_type',
        'phone_code',
        'phone_number',
    ];

    /**
     * Get the registration that owns the phone detail.
     */
    public function registration()
    {
        return $this->belongsTo(TinRegistration::class, 'tin_registration_id');
    }

    /**
     * Get the full phone number with code.
     */
    public function getFullPhoneNumberAttribute()
    {
        return $this->phone_code . $this->phone_number;
    }

    /**
     * Get the phone type as human readable text.
     */
    public function getPhoneTypeTextAttribute()
    {
        $types = [
            'CEL1' => 'Mobile 1',
            'CEL2' => 'Mobile 2',
            'HOME' => 'Home',
            'WORK' => 'Work',
            'FAX' => 'Fax',
        ];

        return $types[$this->phone_type] ?? $this->phone_type;
    }
}