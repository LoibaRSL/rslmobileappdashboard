<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiitReturnAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'riit_return_id',
        'attachment_type',
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

    public function riitReturn(): BelongsTo
    {
        return $this->belongsTo(RiitReturn::class);
    }
}
