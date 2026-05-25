<?php
// app/Models/TinRegistration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TinRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_locator',
        'receive_date',
        'registration_type',
        'legacy_tin',
        'tin',
        'title',
        'ref',
        'surname',
        'forenames',
        'maiden_name',
        'date_of_birth',
        'country_of_birth',
        'country_of_citizenship',
        'country_of_residence',
        'lesotho_id_number',
        'lesotho_id_expiry',
        'country_of_issue',
        'other_id_type',
        'other_id_number',
        'other_id_expiry',
        'post_country',
        'post_type',
        'post_number',
        'post_code',
        'post_address1',
        'post_address2',
        'post_address3',
        'post_address4',
        'post_district',
        'physical_country',
        'street_name',
        'nearest_place',
        'village',
        'town',
        'physical_district',
        'phone_type',
        'phone_code',
        'phone_number',
        'email',
        'email_verified',
        'email_verification_code',
        'marital_status',
        'condition_of_marriage',
        'spouse_tin',
        'spouse_name',
        'spouse_maiden_name',
        'spouse_personal_id',
        'mobile_money_type',
        'mobile_money_number',
        'bank_name',
        'bank_country',
        'printed_name',
        'declaration_accepted',
        'status',
        'amended_from_id',
        'is_amendment',
        'amendment_notes',
        'amendment_submitted_at',
        'remarks',
        'amendment_notes',
        'amendment_submitted_at',
        'assigned_to',
        'assigned_to_user_id',
        'assigned_at',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'date_of_birth' => 'date',
        'lesotho_id_expiry' => 'date',
        'other_id_expiry' => 'date',
        'email_verified' => 'boolean',
        'declaration_accepted' => 'boolean',
        'assigned_at' => 'datetime',
    ];

   // Generate TIN
    public function generateTIN(): string
    {
        $prefix = 'IND' . now()->format('Ymd');
        $sequence = self::where('tin', 'like', $prefix . '%')
            ->pluck('tin')
            ->map(function ($tin) use ($prefix) {
                return (int) substr((string) $tin, strlen($prefix));
            })
            ->max() + 1;

        do {
            $tin = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::where('tin', $tin)->exists());

        return $tin;
    }

    // Check if this registration has pending amendments
    public function hasPendingAmendment(): bool
    {
        return $this->status === 'PENDING' && $this->registration_type === 'AMND';
    }

    // Check if registration is approved
    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    // Check if registration is pending
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    // Check if this is an amendment
    public function isAmendment(): bool
    {
        return $this->registration_type === 'AMND';
    }

    // Get full name
    public function getFullNameAttribute(): string
    {
        return "{$this->forenames} {$this->surname}";
    }


    public function employers(): HasMany
    {
        return $this->hasMany(Employer::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(RegistrationFile::class);
    }

    // Load counts for the table
    public function getEmployersCountAttribute(): int
    {
        if ($this->relationLoaded('employers')) {
            return $this->employers->count();
        }
        
        return $this->employers()->count();
    }

    public function getRegistrationFilesCountAttribute(): int
    {
        if ($this->relationLoaded('files')) {
            return $this->files->count();
        }
        
        return $this->files()->count();
    }

     public function bankingDetails()
    {
        return $this->hasMany(BankingDetail::class);
    }

    public function mobileMoneyDetails()
    {
        return $this->hasMany(MobileMoneyDetail::class);
    }
   
    public function phoneDetails()
    {
        return $this->hasMany(PhoneDetail::class);
    }

    /**
     * Get the primary phone number (first phone detail)
     */
    public function getPrimaryPhoneAttribute()
    {
        return $this->phoneDetails->first();
    }


}
