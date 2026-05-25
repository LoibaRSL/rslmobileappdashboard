<?php
// app/Models/BusinessRegistration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'application_type',
        'registration_type',
        'document_locator',
        'receive_date',
        'old_tin',
        'new_tin',
        'legal_name',
        'business_type',
        'business_type_display',
        'title',
        'registration_number',
        'is_sole_trader',
        'name_structure',
        'structured_postal_address',
        'structured_physical_address',
        'structured_phones',
        'phone_details',
        'email',
        'primary_phone',
        'trade_details',
        'principal_details',
        'directors_partners',
        'bank_mobile_money',
        'accountant_details',
        'nominated_officer_details',
        'personal_identification',
        'sole_trader_details',
        'file_attachments',
        'proof_of_trading_files_count',
        'contract_vat_files_count',
        'has_antenuptial_file',
        'declaration_accepted',
        'declaration_name',
        'declaration_capacity',
        'declaration_signature',
        'declaration_date',
        'status',
        'person_id',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'sms_sent',
        'sms_sent_at',
        'sms_status',
        'sms_error',
        'submission_ip',
        'submission_device',
        'assigned_to',
        'assigned_to_user_id',
        'assigned_at',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'declaration_date' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'assigned_at' => 'datetime',
        'is_sole_trader' => 'boolean',
        'declaration_accepted' => 'boolean',
        'has_antenuptial_file' => 'boolean',
        'sms_sent' => 'boolean',
        'name_structure' => 'array',
        'structured_postal_address' => 'array',
        'structured_physical_address' => 'array',
        'structured_phones' => 'array',
        'phone_details' => 'array',
        'trade_details' => 'array',
        'principal_details' => 'array',
        'directors_partners' => 'array',
        'bank_mobile_money' => 'array',
        'accountant_details' => 'array',
        'nominated_officer_details' => 'array',
        'personal_identification' => 'array',
        'sole_trader_details' => 'array',
        'file_attachments' => 'array',
    ];

     protected $appends = ['phone_numbers', 'verified_phones'];

         public function getPhoneNumbersAttribute(): array
    {
        return $this->phone_details ?? [];
    }


    public function getDisplayNameAttribute(): string
    {
        // If legal_name exists and is not empty, use it
        if (!empty($this->legal_name)) {
            return $this->legal_name;
        }
        
        // Otherwise, construct from name_structure JSON
        $nameStructure = $this->name_structure;
        
        if (is_array($nameStructure)) {
            $forename = $nameStructure['forename'] ?? '';
            $surname = $nameStructure['surname'] ?? '';
            
            // Trim and combine
            $forename = trim($forename);
            $surname = trim($surname);
            
            if ($forename || $surname) {
                return trim("{$forename} {$surname}");
            }
        }
        
        // Fallback if nothing is available
        return 'N/A';
    }
    
    /**
     * For better search performance, add a scope
     */
    public function scopeSearchByName($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('legal_name', 'like', "%{$search}%")
              ->orWhereJsonContains('name_structure->forename', $search)
              ->orWhereJsonContains('name_structure->surname', $search);
        });
    }


    public function getVerifiedPhonesAttribute(): array
    {
        $phones = $this->phone_details ?? [];
        return array_filter($phones, function($phone) {
            return ($phone['verified'] ?? false) === true;
        });
    }

    public function getPrimaryPhoneAttribute(): ?string
    {
        $phones = $this->phone_details ?? [];
        foreach ($phones as $phone) {
            if (($phone['phoneType'] ?? null) === 'CEL1' && ($phone['verified'] ?? false)) {
                $code = $phone['phoneCode'] ?? '266';
                $number = $phone['phoneNumber'] ?? '';
                if ($number) {
                    return $code . $number;
                }
            }
        }
        
        // Fallback to first verified phone
        foreach ($phones as $phone) {
            if (($phone['verified'] ?? false)) {
                $code = $phone['phoneCode'] ?? '266';
                $number = $phone['phoneNumber'] ?? '';
                if ($number) {
                    return $code . $number;
                }
            }
        }
        
        return null;
    }


    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $todayCount = self::whereDate('created_at', today())->count();

$model->reference_number = sprintf(
    'BUS%s%04d',
    now()->format('Ymd'),
    $todayCount + 1
);

            }
            
            if (empty($model->document_locator)) {
                $model->document_locator = 'BUS' . strtoupper(date('M')) . date('Y');
            }
            
            if (empty($model->receive_date)) {
                $model->receive_date = now();
            }
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(BusinessRegistrationFile::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BusinessRegistrationHistory::class)->latest();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }


    public function getFullNameAttribute(): string
    {
        if ($this->is_sole_trader) {
            $name = $this->name_structure ?? [];
            $surname = $name['surname'] ?? '';
            $forename = $name['forename'] ?? '';
            return trim("$forename $surname");
        }
        
        return $this->legal_name ?? 'N/A';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }


}
