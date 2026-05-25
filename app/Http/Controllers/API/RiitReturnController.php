<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiitReturn;
use App\Services\RiitSoapSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiitReturnController extends Controller
{
    public function submit(Request $request, RiitSoapSubmissionService $soapService): JsonResponse
    {
        $payload = $this->payload($request);

        validator($payload, [
            'personId' => 'required|string|max:100',
            'tin' => 'required|string|max:50',
            'periodEndDate' => 'required|date',
            'periodStartDate' => 'required|date',
            'taxYearEnd' => 'nullable|string|max:10',
            'isAmendment' => 'nullable|boolean',
            'formData' => 'nullable|array',
            'returnType' => 'nullable|string|max:30',
        ])->validate();

        $return = DB::transaction(function () use ($request, $payload) {
            $formData = $payload['formData'] ?? [];
            $isAmendment = (bool) ($payload['isAmendment'] ?? false);
            $returnType = $payload['returnType'] ?? data_get($formData, 'returnType', 'normal');

            $return = RiitReturn::create([
                'reference_number' => $this->referenceNumber(),
                'person_id' => $payload['personId'],
                'tin' => $payload['tin'],
                'return_type' => $returnType,
                'is_amendment' => $isAmendment,
                'tax_year_end' => $payload['taxYearEnd'] ?? null,
                'period_start_date' => $payload['periodStartDate'],
                'period_end_date' => $payload['periodEndDate'],
                'tax_type' => $payload['taxType'] ?? null,
                'document_locator' => $payload['documentLocator'] ?? $this->documentLocator($returnType, $isAmendment),
                'receive_date' => now()->toDateString(),
                'form_data' => $formData,
                'submission_payload' => $payload,
                'total_chargeable_income' => $this->amount(data_get($formData, 'totalChargeableIncome', 0)),
                'tax_due' => $this->amount(data_get($formData, 'taxDue', 0)),
                'tax_overpaid' => $this->amount(data_get($formData, 'taxOverpaid', 0)),
                'claim_repayment' => (bool) data_get($formData, 'claimRepayment', false),
                'declaration_accepted' => true,
                'declarant_name' => data_get($formData, 'declarantName'),
                'nil_reason' => $payload['nilReason'] ?? data_get($formData, 'nilReason'),
                'submission_ip' => $request->ip(),
                'submission_device' => $request->userAgent(),
            ]);

            $this->storeAttachments($request, $return);

            return $return->fresh('attachments');
        });

        $soapResult = $soapService->submit($return);
        $return->update([
            'status' => $soapResult['success'] ? 'soap_submitted' : 'soap_failed',
            'soap_status' => $soapResult['success'] ? 'success' : 'failed',
            'soap_message' => $soapResult['message'],
            'soap_request' => $soapResult['request'],
            'soap_response' => $soapResult['response'],
            'soap_http_status' => $soapResult['http_status'],
            'soap_submitted_at' => now(),
        ]);

        return response()->json([
            'success' => $soapResult['success'],
            'message' => $soapResult['success']
                ? 'RIIT return saved and submitted to SOAP successfully'
                : 'RIIT return saved, but SOAP submission failed: ' . $soapResult['message'],
            'data' => [
                'id' => $return->id,
                'reference_number' => $return->reference_number,
                'soap_status' => $return->soap_status,
                'soap_message' => $return->soap_message,
                'attachments_count' => $return->attachments()->count(),
            ],
        ], $soapResult['success'] ? 201 : 202);
    }

    private function payload(Request $request): array
    {
        if ($request->filled('data')) {
            $decoded = json_decode($request->input('data'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->all();
    }

    private function storeAttachments(Request $request, RiitReturn $return): void
    {
        foreach ($request->allFiles() as $field => $files) {
            $this->storeFileTree($return, $field, $files);
        }
    }

    private function storeFileTree(RiitReturn $return, string $field, mixed $files, array $path = []): void
    {
        if ($files instanceof UploadedFile) {
            $storedPath = $files->store("riit-returns/{$return->id}/{$field}", 'public');
            $return->attachments()->create([
                'attachment_type' => $field,
                'original_filename' => $files->getClientOriginalName(),
                'file_path' => $storedPath,
                'mime_type' => $files->getMimeType(),
                'file_size' => $files->getSize(),
                'disk' => 'public',
                'metadata' => ['field' => $field, 'path' => $path],
            ]);
            return;
        }

        if (is_array($files)) {
            foreach ($files as $key => $file) {
                $this->storeFileTree($return, $field, $file, array_merge($path, [$key]));
            }
        }
    }

    private function referenceNumber(): string
    {
        return 'RIIT' . now()->format('YmdHis') . Str::upper(Str::random(4));
    }

    private function documentLocator(string $returnType, bool $isAmendment): string
    {
        $prefix = $returnType === 'nil' ? 'NI' : ($isAmendment ? 'RIITAMD' : 'RIIT');

        return $prefix . now()->format('dMY');
    }

    private function amount(mixed $value): float
    {
        return (float) (preg_replace('/[^0-9.\-]/', '', (string) $value) ?: 0);
    }
}
