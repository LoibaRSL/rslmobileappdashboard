<?php
// app/Models/BusinessAmendment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessAmendment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_registration_id',
        'original_tin',
        'amendment_tin',
        'reference_number',
        'document_locator',
        'receive_date',
        'application_type',
        'amendment_type',
        'amended_sections',
        'amendment_data',
        'status',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'applied_at',
        'applied_by',
        'submission_ip',
        'submission_device', 
    ];

    protected $casts = [
        'receive_date' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'applied_at' => 'datetime',
        'amended_sections' => 'array',
        'amendment_data' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(BusinessRegistration::class, 'business_registration_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(BusinessAmendmentFile::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BusinessAmendmentHistory::class)->latest();
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

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $model->reference_number = 'AMEND' . date('Ymd') . str_pad(
                    self::whereDate('created_at', today())->count() + 1, 
                    4, '0', STR_PAD_LEFT
                );
            }
            
            if (empty($model->document_locator)) {
                $model->document_locator = 'AMEND' . strtoupper(date('M')) . date('Y');
            }
            
            if (empty($model->receive_date)) {
                $model->receive_date = now();
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeApplied($query)
    {
        return $query->whereNotNull('applied_at');
    }

    public function scopeNotApplied($query)
    {
        return $query->whereNull('applied_at');
    }

    public function getAmendedSectionsDisplayAttribute(): string
    {
        if (!$this->amended_sections) {
            return 'No sections specified';
        }

        $sectionNames = [
            'business_details' => 'Business Details',
            'trade_details' => 'Trade Details',
            'contact_info' => 'Contact Information',
            'accountant_nominated' => 'Accountant & Nominated Officer',
            'sole_trader_details' => 'Sole Trader Details',
            'principal_details' => 'Principal Details',
            'directors_partners' => 'Directors/Partners',
            'bank_mobile_money' => 'Bank & Mobile Money',
            'tax_registrations' => 'Tax Registrations',
            'declaration' => 'Declaration',
        ];

        $displaySections = [];
        foreach ($this->amended_sections as $section) {
            if (isset($sectionNames[$section])) {
                $displaySections[] = $sectionNames[$section];
            } else {
                $displaySections[] = ucfirst(str_replace('_', ' ', $section));
            }
        }

        return implode(', ', $displaySections);
    }

    // Accessor for amended_sections_text
    // Add these methods to your BusinessAmendment model if not already present

public function getAmendmentDataTextAttribute(): string
{
    if (!$this->amendment_data) {
        return 'No amendment data available.';
    }

    try {
        // If it's already an array (due to casting), encode it
        if (is_array($this->amendment_data)) {
            return json_encode($this->amendment_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        // If it's a JSON string, decode and re-encode for pretty print
        if (is_string($this->amendment_data)) {
            $decoded = json_decode($this->amendment_data, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            // If it's already valid pretty JSON, return as-is
            if (strpos($this->amendment_data, "\n") !== false) {
                return $this->amendment_data;
            }
        }
        
        // Fallback: cast to string
        return (string) $this->amendment_data;
    } catch (\Exception $e) {
        return 'Error formatting amendment data: ' . $e->getMessage();
    }
}

public function getAmendedSectionsTextAttribute(): string
{
    if (!$this->amended_sections) {
        return 'No sections amended';
    }

    try {
        // Use the display version for better readability
        if (method_exists($this, 'getAmendedSectionsDisplayAttribute')) {
            return $this->amended_sections_display;
        }
        
        // Fallback logic
        if (is_array($this->amended_sections)) {
            return implode(', ', array_map(function($section) {
                return ucwords(str_replace('_', ' ', $section));
            }, $this->amended_sections));
        }
        
        if (is_string($this->amended_sections)) {
            $sections = json_decode($this->amended_sections, true);
            if (is_array($sections)) {
                return implode(', ', array_map(function($section) {
                    return ucwords(str_replace('_', ' ', $section));
                }, $sections));
            }
            return $this->amended_sections;
        }
        
        return 'Unable to parse amended sections';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}
}