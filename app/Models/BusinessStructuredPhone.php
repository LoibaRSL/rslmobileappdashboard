<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessStructuredPhone extends Model
{
    use HasFactory;

    protected $table = 'business_structured_phones';

    protected $fillable = [
        'business_registration_id',
        'phone_type',
        'phone_code',
        'phone_number',
        'order_index'
    ];

    protected $casts = [
        'order_index' => 'integer'
    ];

    /**
     * Get the business registration that owns the phone.
     */
    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }

    /**
     * Scope for specific phone types.
     */
    public function scopeType($query, $type)
    {
        return $query->where('phone_type', $type);
    }

    /**
     * Scope for ordered phones.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}