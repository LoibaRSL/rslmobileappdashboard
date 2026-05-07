<?php
// app/Models/RegistrationFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_registration_id',
        'file_type',
        'file_path',
        'file_name'
    ];

    public function registration()
    {
        return $this->belongsTo(TinRegistration::class);
    }
}