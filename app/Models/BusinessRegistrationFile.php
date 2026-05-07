<?php
// app/Models/BusinessRegistrationFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRegistrationFile extends Model
{
    protected $fillable = [
        'business_registration_id',
        'file_type',
        'original_filename',
        'file_path', // This is the database column
        'mime_type',
        'file_size',
        'disk',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    // Rename the accessor to avoid conflict
    protected $appends = ['storage_path', 'url'];
    
    public function registration(): BelongsTo
    {
        return $this->belongsTo(BusinessRegistration::class);
    }

    // Keep the original file_path as is (database value)
    // No need for file_path accessor since it's already a column

    // Add a different name for the storage path
    public function getStoragePathAttribute(): string
    {
        return storage_path('app/public/' . $this->attributes['file_path']);
    }

    // URL for web access
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->attributes['file_path']);
    }
}