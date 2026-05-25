<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BusinessAmendment;
use App\Models\BusinessRegistration;
use App\Models\BusinessAmendmentFile;
use App\Models\BusinessAmendmentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BusinessAmendmentController extends Controller
{
    /**
     * Submit a business amendment
     */
   public function store(Request $request)
{
    try {
        Log::info('=== AMENDMENT SUBMISSION STARTED ===');
        Log::info('Request Headers:', $request->headers->all());
        
        // Log form data (excluding files for readability)
        $formData = $request->except(['proof_of_trading', 'contract_vat', 'antenuptial_file', 
                                     'bank_confirmation', 'mobile_money_confirmation']);
        
        Log::info('Request form data (excluding files):', [
            'total_fields' => count($formData),
            'fields' => array_keys($formData),
            'selected_sections' => $request->input('selected_sections', ''),
            'tin' => $request->input('tin', ''),
            'document_locator' => $request->input('document_locator', ''),
        ]);
        
        // Check for JSON application_data field
        if ($request->has('application_data')) {
            $applicationData = $request->input('application_data');
            Log::info('application_data field received:', [
                'type' => gettype($applicationData),
                'length' => is_string($applicationData) ? strlen($applicationData) : 'N/A',
                'is_json' => is_string($applicationData) && json_decode($applicationData) !== null,
            ]);
            
            // If it's a JSON string, decode it
            if (is_string($applicationData) && !empty($applicationData)) {
                try {
                    $decodedData = json_decode($applicationData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                        Log::info('Successfully decoded application_data JSON', [
                            'decoded_keys' => array_keys($decodedData),
                        ]);
                        
                        // Merge decoded data with request
                        $request->merge($decodedData);
                    } else {
                        Log::warning('Failed to decode application_data JSON', [
                            'json_error' => json_last_error_msg(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception decoding application_data:', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // Validate required fields
        $validator = Validator::make($request->all(), [
            'tin' => 'required|string|max:50',
            'selected_sections' => 'required|array', // Changed to array
            'selected_sections.*' => 'string|in:business_details,trade_details,contact_info,accountant_nominated,sole_trader_details,principal_details,directors_partners,bank_mobile_money,tax_registrations,declaration',
            'document_locator' => 'required|string|max:100',
            'receive_date' => 'required|date',
            'application_type' => 'required|string|max:50',
            'business_type' => 'required|string|max:10',
            'business_type_display' => 'required|string|max:100',
            'is_sole_trader' => 'required|boolean',
        ]);
        
        if ($validator->fails()) {
            Log::error('Validation failed:', [
                'errors' => $validator->errors()->toArray(),
                'selected_sections' => $request->input('selected_sections'),
                'selected_sections_type' => gettype($request->input('selected_sections')),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'debug' => [
                    'received_fields' => array_keys($request->except(['proof_of_trading', 'contract_vat', 'antenuptial_file'])),
                    'selected_sections' => $request->input('selected_sections'),
                ],
            ], 422);
        }
        
        DB::beginTransaction();
        
        // Extract data
        $tin = $request->input('tin');
        $selectedSections = $request->input('selected_sections', []); // Already an array
        $documentLocator = $request->input('document_locator');
        $receiveDate = $request->input('receive_date');
        $applicationType = $request->input('application_type', 'Amendment');
        $registrationType = $request->input('registration_type', 'AMEND');
        $etpmTin = $request->input('etpm_tin', $tin);
        
        Log::info('Processing amendment for TIN:', [
            'tin' => $tin,
            'etpm_tin' => $etpmTin,
            'selected_sections_count' => count($selectedSections),
            'selected_sections' => $selectedSections, // Already an array
            'document_locator' => $documentLocator,
        ]);
        
        // Check if original registration exists
        $originalRegistration = BusinessRegistration::where('new_tin', $tin)
            ->orWhere('old_tin', $tin)
            ->latest()
            ->first();
        
        Log::info('Original registration lookup:', [
            'found' => $originalRegistration !== null,
            'registration_id' => $originalRegistration ? $originalRegistration->id : null,
            'registration_tin' => $originalRegistration ? ($originalRegistration->new_tin ?? $originalRegistration->old_tin) : null,
        ]);
        
        // Generate reference number
        $referenceNumber = $this->generateReferenceNumber();
        
        // Convert selected_sections array to comma-separated string for database storage
        $selectedSectionsString = is_array($selectedSections) ? implode(',', $selectedSections) : $selectedSections;
        
        // Create amendment record
        $amendment = BusinessAmendment::create([
            'business_registration_id' => $originalRegistration ? $originalRegistration->id : null,
            'tin' => $tin,
            'amendment_tin' => $tin,
            'reference_number' => $referenceNumber,
            'document_locator' => $documentLocator,
            'receive_date' => $receiveDate,
            'application_type' => $applicationType,
            'amendment_type' => $originalRegistration ? 'UPDATE' : 'NEW',
            'amended_sections' => $selectedSections, // Store as array (JSON)
            'status' => 'submitted',
            'submission_ip' => $request->ip(),
            'submission_device' => $request->userAgent(),
        ]);
        
        Log::info('Amendment record created:', [
            'id' => $amendment->id,
            'reference_number' => $referenceNumber,
            'amendment_type' => $amendment->amendment_type,
        ]);
        
        // Process and store ALL amendment data
        $amendmentData = $this->processAmendmentData($request);
        $amendment->amendment_data = $amendmentData;
        $amendment->save();
        
        Log::info('Amendment data stored:', [
            'data_keys_count' => count($amendmentData),
            'data_keys_sample' => array_slice(array_keys($amendmentData), 0, 20),
        ]);
        
        // Handle file uploads
        $this->handleAmendmentFiles($amendment, $request, $originalRegistration);
        
        // Create history record
        $this->createAmendmentHistory($amendment, $request, $selectedSections);
        
        DB::commit();
        
        Log::info('Amendment submitted successfully:', [
            'amendment_id' => $amendment->id,
            'reference_number' => $amendment->reference_number,
            'has_original_registration' => $originalRegistration !== null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Business amendment submitted successfully',
            'data' => [
                'amendment_id' => $amendment->id,
                'reference_number' => $amendment->reference_number,
                'created_at' => $amendment->created_at->toDateTimeString(),
                'has_original_registration' => $originalRegistration !== null,
                'document_locator' => $amendment->document_locator,
            ],
        ], 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Amendment submission failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'request_data' => $request->except(['proof_of_trading', 'contract_vat', 'antenuptial_file']),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Amendment submission failed',
            'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Internal server error',
            'debug' => env('APP_DEBUG', false) ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], 500);
    }
}
    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber(): string
    {
        $date = now()->format('Ymd');
        $count = BusinessAmendment::whereDate('created_at', today())->count() + 1;
        $serial = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        return "BUSAMD{$date}{$serial}";
    }
    
    /**
     * Process amendment data from request
     */
    private function processAmendmentData(Request $request): array
    {
        $data = [];
        
        // Start with all request data (excluding files)
        $allData = $request->except([
            'proof_of_trading',
            'contract_vat',
            'antenuptial_file',
            'bank_confirmation',
            'mobile_money_confirmation',
            'nominated_officer_change',
            'name_change_proof',
            'vat_supporting_documents',
            'paye_supporting_documents',
            'plastic_levy_supporting_documents',
            '_token',
            '_method',
        ]);
        
        Log::info('Processing amendment data from request:', [
            'total_fields' => count($allData),
            'field_samples' => array_slice(array_keys($allData), 0, 30),
        ]);
        
        foreach ($allData as $key => $value) {
            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }
            
            // Handle nested JSON fields
            if (in_array($key, ['amendment_data', 'application_data'])) {
                continue; // Already handled
            }
            
            // Convert string booleans to actual booleans
            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }
            
            // Handle JSON strings
            if (is_string($value) && Str::startsWith($value, '{') && Str::endsWith($value, '}')) {
                try {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $value = $decoded;
                    }
                } catch (\Exception $e) {
                    // Keep as string if not valid JSON
                }
            }
            
            // Handle array notation (e.g., tradeDetails[0][tradeName])
            if (Str::contains($key, '[') && Str::contains($key, ']')) {
                $this->processArrayNotationData($data, $key, $value);
            } else {
                $data[$key] = $value;
            }
        }
        
        // Add metadata
        $data['submission_timestamp'] = now()->toDateTimeString();
        $data['submission_ip'] = $request->ip();
        $data['submission_device'] = $request->userAgent();
        
        Log::info('Processed amendment data structure:', [
            'total_keys' => count($data),
            'top_level_keys' => array_keys($data),
        ]);
        
        return $data;
    }
    
    /**
     * Process array notation data (e.g., tradeDetails[0][tradeName])
     */
    private function processArrayNotationData(array &$data, string $key, $value): void
    {
        // Convert array notation to dot notation
        $dotKey = preg_replace('/\[(\w+)\]/', '.$1', $key);
        $dotKey = str_replace('[]', '.', $dotKey);
        $dotKey = trim($dotKey, '.');
        
        // Convert to nested array
        $keys = explode('.', $dotKey);
        $current = &$data;
        
        foreach ($keys as $index => $k) {
            if ($index === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }
    
    /**
     * Handle amendment file uploads
     */
    private function handleAmendmentFiles(
        BusinessAmendment $amendment, 
        Request $request, 
        ?BusinessRegistration $originalRegistration
    ): void {
        Log::info('Processing file uploads for amendment:', [
            'amendment_id' => $amendment->id,
            'files_present' => array_keys($request->allFiles()),
        ]);
        
        // Handle proof of trading files
        if ($request->hasFile('proof_of_trading')) {
            $files = $request->file('proof_of_trading');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'proof_of_trading', $index, $originalRegistration);
                }
            }
        }
        
        // Handle contract VAT files
        if ($request->hasFile('contract_vat')) {
            $files = $request->file('contract_vat');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'contract_vat', $index, $originalRegistration);
                }
            }
        }
        
        // Handle antenuptial file
        if ($request->hasFile('antenuptial_file')) {
            $file = $request->file('antenuptial_file');
            if ($file && $file->isValid()) {
                $this->storeAmendmentFile($amendment, $file, 'antenuptial', 0, $originalRegistration);
            }
        }
        
        // Handle bank confirmation files
        if ($request->hasFile('bank_confirmation')) {
            $files = $request->file('bank_confirmation');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'bank_confirmation', $index, $originalRegistration);
                }
            }
        }
        
        // Handle mobile money confirmation files
        if ($request->hasFile('mobile_money_confirmation')) {
            $files = $request->file('mobile_money_confirmation');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'mobile_money_confirmation', $index, $originalRegistration);
                }
            }
        }

                // Handle nominated_officer_change files
        if ($request->hasFile('nominated_officer_change')) {
            $files = $request->file('nominated_officer_change');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'nominated_officer_change', $index, $originalRegistration);
                }
            }
        }

                // Handle name_change_proof files
        if ($request->hasFile('name_change_proof')) {
            $files = $request->file('name_change_proof');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'name_change_proof', $index, $originalRegistration);
                }
            }
        }


                // Handle vat_supporting_documents files
        if ($request->hasFile('vat_supporting_documents')) {
            $files = $request->file('vat_supporting_documents');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'vat_supporting_documents', $index, $originalRegistration);
                }
            }
        }

           // Handle paye_supporting_documents files
        if ($request->hasFile('paye_supporting_documents')) {
            $files = $request->file('paye_supporting_documents');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'paye_supporting_documents', $index, $originalRegistration);
                }
            }
        }


        // Handle plastic_levy_supporting_documents files
        if ($request->hasFile('plastic_levy_supporting_documents')) {
            $files = $request->file('plastic_levy_supporting_documents');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $this->storeAmendmentFile($amendment, $file, 'plastic_levy_supporting_documents', $index, $originalRegistration);
                }
            }
        }


    }
    
    /**
     * Store amendment file
     */
    private function storeAmendmentFile(
        BusinessAmendment $amendment, 
        $file, 
        string $type, 
        int $index,
        ?BusinessRegistration $originalRegistration
    ): void {
        $filename = $file->getClientOriginalName();
        $path = $file->store("business-amendments/{$amendment->id}/{$type}", 'public');
        
        BusinessAmendmentFile::create([
            'business_amendment_id' => $amendment->id,
            'business_registration_id' => $originalRegistration ? $originalRegistration->id : null,
            'file_type' => $type,
            'original_filename' => $filename,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'metadata' => [
                'original_name' => $filename,
                'extension' => $file->getClientOriginalExtension(),
                'uploaded_at' => now()->toDateTimeString(),
                'index' => $index,
            ],
        ]);
        
        Log::info('File stored successfully:', [
            'type' => $type,
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
        ]);
    }
    
    /**
     * Create amendment history record
     */
    private function createAmendmentHistory(
        BusinessAmendment $amendment, 
        Request $request, 
        array $selectedSections
    ): void {
        $metadata = [
            'ip' => $request->ip(),
            'device' => $request->userAgent(),
            'selected_sections' => $selectedSections,
            'tin' => $request->input('tin'),
            'document_locator' => $request->input('document_locator'),
            'submission_timestamp' => now()->toDateTimeString(),
        ];

        $amendment->histories()->create([
            'action' => 'submitted',
            'description' => 'Amendment submitted for processing',
            'performed_by' => null,
            'metadata' => $metadata,
        ]);
        
        Log::info('History record created for amendment:', [
            'amendment_id' => $amendment->id,
            'action' => 'submitted',
        ]);
    }
    
    /**
     * Get amendments by TIN
     */
    public function getByTin($tin)
    {
        try {
            $amendments = BusinessAmendment::where('tin', $tin)
                ->orWhere('amendment_tin', $tin)
                ->with(['registration', 'files', 'histories'])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $amendments,
                'count' => $amendments->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get amendments by TIN failed:', [
                'tin' => $tin,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve amendments',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    
    /**
     * Get single amendment by ID
     */
    public function show($id)
    {
        try {
            $amendment = BusinessAmendment::with(['registration', 'files', 'histories'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $amendment,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get amendment by ID failed:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Amendment not found',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Internal server error',
            ], 404);
        }
    }
    
    /**
     * Get amendments by status
     */
    public function getByStatus($status)
    {
        try {
            $validStatuses = ['draft', 'submitted', 'under_review', 'additional_info_required', 'approved', 'rejected', 'processed'];
            
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status',
                    'valid_statuses' => $validStatuses,
                ], 400);
            }
            
            $amendments = BusinessAmendment::where('status', $status)
                ->with(['registration'])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $amendments,
                'count' => $amendments->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get amendments by status failed:', [
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve amendments',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
