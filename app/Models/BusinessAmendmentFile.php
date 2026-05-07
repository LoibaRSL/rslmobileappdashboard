<?php
// app/Models/BusinessAmendmentFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAmendmentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_amendment_id',
        'business_registration_id',
        'file_type',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'disk',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function amendment(): BelongsTo
    {
        return $this->belongsTo(BusinessAmendment::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}