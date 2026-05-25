<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiitReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'person_id',
        'tin',
        'return_type',
        'is_amendment',
        'tax_year_end',
        'period_start_date',
        'period_end_date',
        'tax_type',
        'document_locator',
        'receive_date',
        'form_data',
        'submission_payload',
        'total_chargeable_income',
        'tax_due',
        'tax_overpaid',
        'claim_repayment',
        'declaration_accepted',
        'declarant_name',
        'status',
        'soap_status',
        'soap_message',
        'soap_request',
        'soap_response',
        'soap_http_status',
        'soap_submitted_at',
        'nil_reason',
        'submission_ip',
        'submission_device',
    ];

    protected $casts = [
        'is_amendment' => 'boolean',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'receive_date' => 'date',
        'form_data' => 'array',
        'submission_payload' => 'array',
        'total_chargeable_income' => 'decimal:2',
        'tax_due' => 'decimal:2',
        'tax_overpaid' => 'decimal:2',
        'claim_repayment' => 'boolean',
        'declaration_accepted' => 'boolean',
        'soap_submitted_at' => 'datetime',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(RiitReturnAttachment::class);
    }
}
