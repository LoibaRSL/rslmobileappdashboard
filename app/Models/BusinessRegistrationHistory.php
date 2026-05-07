<?php
// app/Models/BusinessRegistrationHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRegistrationHistory extends Model
{
    protected $fillable = [
        'business_registration_id',
        'action',
        'description',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(BusinessRegistration::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}