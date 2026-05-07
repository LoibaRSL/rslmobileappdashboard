<?php
// app/Models/BusinessAmendmentHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAmendmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_amendment_id',
        'action',
        'description',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function amendment(): BelongsTo
    {
        return $this->belongsTo(BusinessAmendment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}