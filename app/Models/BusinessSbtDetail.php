<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSbtDetail extends Model
{
    use HasFactory;

    protected $table = 'business_sbt_details';

    protected $fillable = [
        'business_registration_id',
        'sbt_effective_date'
    ];

    protected $casts = [
        'sbt_effective_date' => 'date'
    ];

    /**
     * Get the business registration that owns the SBT details.
     */
    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}