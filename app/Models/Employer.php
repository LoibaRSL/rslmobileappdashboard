<?php
// app/Models/Employer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_registration_id',
        'employer_name',
        'file_path'
    ];

    public function registration()
    {
        return $this->belongsTo(TinRegistration::class);
    }
}