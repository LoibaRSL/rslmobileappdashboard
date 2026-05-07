<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPersonalIdentification extends Model
{
    use HasFactory;

    protected $table = 'business_personal_identification';

    protected $fillable = [
        'business_registration_id',
        'date_of_birth',
        'passport_number',
        'passport_expiry_date',
        'country_of_issue',
        'other_id_type',
        'other_id_number',
        'other_id_expiry_date',
        'other_country_of_issue',
        'country_of_birth',
        'country_of_residence',
        'country_of_citizenship'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry_date' => 'date',
        'other_id_expiry_date' => 'date',
    ];

    /**
     * Get the business registration that owns the personal identification.
     */
    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }

    /**
     * Check if passport details are available.
     */
    public function hasPassport(): bool
    {
        return !empty($this->passport_number);
    }

    /**
     * Check if other ID details are available.
     */
    public function hasOtherId(): bool
    {
        return !empty($this->other_id_number);
    }

    /**
     * Get age from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Check if passport is expired.
     */
    public function isPassportExpired(): bool
    {
        return $this->passport_expiry_date?->isPast() ?? false;
    }

    /**
     * Check if other ID is expired.
     */
    public function isOtherIdExpired(): bool
    {
        return $this->other_id_expiry_date?->isPast() ?? false;
    }
}