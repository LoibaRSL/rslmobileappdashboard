<?php
// app/Http/Controllers/Api/BusinessRegistrationController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BusinessRegistration;
use App\Models\BusinessRegistrationFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessRegistrationController extends Controller
{
     
       // File type constants - ADD ALL OF THESE
    const FILE_TYPE_PROOF_OF_TRADING = 'proof_of_trading';
    const FILE_TYPE_CONTRACT_VAT = 'contract_vat';
    const FILE_TYPE_PROOF_OF_ID = 'proof_of_id';
    const FILE_TYPE_OTHER_PROOF_OF_ID = 'other_proof_of_id';
    const FILE_TYPE_PROOF_OF_EMPLOYMENT = 'proof_of_employment';
    const FILE_TYPE_ANTENUPTIAL = 'antenuptial';



        public function store(Request $request)
    
    {

           // Debug: Log all files being received
    \Log::info('=== FILE UPLOAD DEBUG START ===');
    \Log::info('All request keys: ', array_keys($request->all()));
    
    $allFiles = $request->allFiles();
    \Log::info('Number of files received: ' . count($allFiles));
    
    foreach ($allFiles as $fieldName => $file) {
        if (is_array($file)) {
            \Log::info('Field ' . $fieldName . ' has ' . count($file) . ' files');
            foreach ($file as $singleFile) {
                \Log::info('  - ' . $singleFile->getClientOriginalName() . ' (' . $singleFile->getSize() . ' bytes)');
            }
        } else {
            \Log::info('Field ' . $fieldName . ': ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
        }
    }
    \Log::info('=== FILE UPLOAD DEBUG END ===');



        // Since we have dynamic field names, we need custom validation
        $validator = $this->createCustomValidator($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Parse application data
            $applicationData = json_decode($request->input('application_data'), true);
            $phoneDetails = $this->extractPhoneDetails($applicationData);
            
            // Create registration record
            $registration = BusinessRegistration::create([
                'application_type' => $applicationData['application_type'] ?? 'New',
                'registration_type' => $applicationData['registration_type'] ?? 'NEW',
                'document_locator' => 'BUS'. date('D') . strtoupper(date('M')) . date('Y'),
                'legal_name' => $applicationData['legal_name'] ?? null,
                'business_type' => $applicationData['business_type'] ?? 'OTH',
                'business_type_display' => $applicationData['business_type_display'] ?? 'Other',
                'title' => $applicationData['title'] ?? null,
                'registration_number' => $applicationData['registration_number'] ?? null,
                'is_sole_trader' => $applicationData['is_sole_trader'] ?? false,
                'name_structure' => $applicationData['name_structure'] ?? null,
                'structured_postal_address' => $applicationData['structured_postal_address'] ?? null,
                'structured_physical_address' => $applicationData['structured_physical_address'] ?? null,
                'structured_phones' => $applicationData['structured_phones'] ?? null,
                'email' => $applicationData['email'] ?? null,
                'trade_details' => $applicationData['trade_details'] ?? null,
                'principal_details' => $applicationData['principal_details'] ?? null,
                'directors_partners' => $applicationData['directors_partners'] ?? null,
                'accountant_details' => $this->extractAccountantDetails($applicationData),
                'nominated_officer_details' => $this->extractNominatedOfficerDetails($applicationData),
                'phone_details' => $phoneDetails,
                'personal_identification' => $applicationData['personal_identification'] ?? null,
                'sole_trader_details' => $applicationData['sole_trader_details'] ?? null,
                'declaration_accepted' => $applicationData['declaration_accepted'] ?? false,
                'declaration_name' => $applicationData['declaration_name'] ?? null,
                'declaration_capacity' => $applicationData['declaration_capacity'] ?? null,
                'declaration_signature' => $applicationData['declaration_signature'] ?? null,
                'declaration_date' => $applicationData['declaration_date'] ?? null,
                'submission_ip' => $request->ip(),
                'submission_device' => $request->userAgent(),
                'status' => 'submitted',
                
                // Initialize file counts
                'proof_of_trading_files_count' => 0,
                'contract_vat_files_count' => 0,
                'proof_of_id_files_count' => 0,
                'other_proof_of_id_files_count' => 0,
                'proof_of_employment_files_count' => 0,
                'has_antenuptial_file' => false,
                'file_attachments' => [],
            ]);

            // Process and store all files
            $this->processAndStoreAllFiles($request, $registration);

            // Create history record
            $registration->histories()->create([
                'action' => 'submitted',
                'description' => 'Application submitted successfully',
                'metadata' => [
                    'ip' => $request->ip(),
                    'device' => $request->userAgent(),
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Business registration submitted successfully',
                'reference_number' => $registration->reference_number,
                'data' => $registration,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            \Log::error('Business registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except(['application_data']), // Don't log full app data
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit business registration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create custom validator for dynamic file fields
     */
    private function createCustomValidator(Request $request)
    {
        $rules = [
            'application_data' => 'required|json',
        ];

        // Add file validation rules for each file in request
        foreach ($request->allFiles() as $fieldName => $file) {
            if (is_array($file)) {
                // Handle array of files (though Flutter seems to send individual fields)
                $rules[$fieldName . '.*'] = 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx';
            } else {
                // Handle single file
                $rules[$fieldName] = 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx';
            }
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Process and store all file types
     */
    private function processAndStoreAllFiles(Request $request, BusinessRegistration $registration): void
    {
        $fileAttachments = [];

        \Log::info('Processing files for registration ID: ' . $registration->id);
        \Log::info('All files in request:', array_keys($request->allFiles()));
        
        // Process each file type
        $this->processProofOfTradingFiles($request, $registration, $fileAttachments);
        $this->processContractVatFiles($request, $registration, $fileAttachments);
        $this->processProofOfIdFiles($request, $registration, $fileAttachments);
        $this->processOtherProofOfIdFiles($request, $registration, $fileAttachments);
        $this->processProofOfEmploymentFiles($request, $registration, $fileAttachments);
        $this->processAntenuptialFile($request, $registration, $fileAttachments);

            \Log::info('Total file attachments processed: ' . count($fileAttachments));
    \Log::info('File attachments:', $fileAttachments);
        
        // Update registration with file metadata
        $registration->update([
            'file_attachments' => $fileAttachments,
            'proof_of_trading_files_count' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_PROOF_OF_TRADING)),
            'contract_vat_files_count' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_CONTRACT_VAT)),
            'proof_of_id_files_count' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_PROOF_OF_ID)),
            'other_proof_of_id_files_count' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_OTHER_PROOF_OF_ID)),
            'proof_of_employment_files_count' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_PROOF_OF_EMPLOYMENT)),
            'has_antenuptial_file' => count(array_filter($fileAttachments, 
                fn($f) => $f['type'] === self::FILE_TYPE_ANTENUPTIAL)) > 0,
        ]);
    }

    /**
     * Process proof_of_trading_{tradeIndex}_{fileIndex} files
     */
   /**
 * Process proof_of_trading_{tradeIndex}_{fileIndex} files
 */
private function processProofOfTradingFiles(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    foreach ($request->allFiles() as $fieldName => $file) {
        if (preg_match('/^proof_of_trading_(\d+)_(\d+)$/', $fieldName, $matches)) {
            $tradeIndex = (int)$matches[1];
            $fileIndex = (int)$matches[2];
            
            $businessRegistrationFile = $this->storeFileInDatabase(
                $file,
                self::FILE_TYPE_PROOF_OF_TRADING,
                $registration->id,
                [
                    'trade_index' => $tradeIndex,
                    'file_index' => $fileIndex,
                    'field_name' => $fieldName,
                ]
            );
            
            $fileAttachments[] = [
                'type' => self::FILE_TYPE_PROOF_OF_TRADING,
                'trade_index' => $tradeIndex,
                'file_index' => $fileIndex,
                'file_id' => $businessRegistrationFile->id,
                'original_name' => $businessRegistrationFile->original_filename,
                'path' => $businessRegistrationFile->file_path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}

/**
 * Process contract_vat_{principalIndex}_{fileIndex} files
 */
private function processContractVatFiles(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    foreach ($request->allFiles() as $fieldName => $file) {
        if (preg_match('/^contract_vat_(\d+)_(\d+)$/', $fieldName, $matches)) {
            $principalIndex = (int)$matches[1];
            $fileIndex = (int)$matches[2];
            
            $businessRegistrationFile = $this->storeFileInDatabase(
                $file,
                self::FILE_TYPE_CONTRACT_VAT,
                $registration->id,
                [
                    'principal_index' => $principalIndex,
                    'file_index' => $fileIndex,
                    'field_name' => $fieldName,
                ]
            );
            
            $fileAttachments[] = [
                'type' => self::FILE_TYPE_CONTRACT_VAT,
                'principal_index' => $principalIndex,
                'file_index' => $fileIndex,
                'file_id' => $businessRegistrationFile->id,
                'original_name' => $businessRegistrationFile->original_filename,
                'path' => $businessRegistrationFile->file_path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}

/**
 * Process proof_of_id_{index} files
 */
private function processProofOfIdFiles(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    foreach ($request->allFiles() as $fieldName => $file) {
        if (preg_match('/^proof_of_id_(\d+)$/', $fieldName, $matches)) {
            $index = (int)$matches[1];
            
            $businessRegistrationFile = $this->storeFileInDatabase(
                $file,
                self::FILE_TYPE_PROOF_OF_ID,
                $registration->id,
                [
                    'index' => $index,
                    'field_name' => $fieldName,
                ]
            );
            
            $fileAttachments[] = [
                'type' => self::FILE_TYPE_PROOF_OF_ID,
                'index' => $index,
                'file_id' => $businessRegistrationFile->id,
                'original_name' => $businessRegistrationFile->original_filename,
                'path' => $businessRegistrationFile->file_path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}

/**
 * Process other_proof_of_id_{index} files
 */
private function processOtherProofOfIdFiles(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    foreach ($request->allFiles() as $fieldName => $file) {
        if (preg_match('/^other_proof_of_id_(\d+)$/', $fieldName, $matches)) {
            $index = (int)$matches[1];
            
            $businessRegistrationFile = $this->storeFileInDatabase(
                $file,
                self::FILE_TYPE_OTHER_PROOF_OF_ID,
                $registration->id,
                [
                    'index' => $index,
                    'field_name' => $fieldName,
                ]
            );
            
            $fileAttachments[] = [
                'type' => self::FILE_TYPE_OTHER_PROOF_OF_ID,
                'index' => $index,
                'file_id' => $businessRegistrationFile->id,
                'original_name' => $businessRegistrationFile->original_filename,
                'path' => $businessRegistrationFile->file_path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}

/**
 * Process proof_of_employment_{employerIndex}_{fileIndex} files
 */
private function processProofOfEmploymentFiles(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    foreach ($request->allFiles() as $fieldName => $file) {
        if (preg_match('/^proof_of_employment_(\d+)_(\d+)$/', $fieldName, $matches)) {
            $employerIndex = (int)$matches[1];
            $fileIndex = (int)$matches[2];
            
            $businessRegistrationFile = $this->storeFileInDatabase(
                $file,
                self::FILE_TYPE_PROOF_OF_EMPLOYMENT,
                $registration->id,
                [
                    'employer_index' => $employerIndex,
                    'file_index' => $fileIndex,
                    'field_name' => $fieldName,
                ]
            );
            
            $fileAttachments[] = [
                'type' => self::FILE_TYPE_PROOF_OF_EMPLOYMENT,
                'employer_index' => $employerIndex,
                'file_index' => $fileIndex,
                'file_id' => $businessRegistrationFile->id,
                'original_name' => $businessRegistrationFile->original_filename,
                'path' => $businessRegistrationFile->file_path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
    }
}

/**
 * Process antenuptial_file
 */
private function processAntenuptialFile(Request $request, BusinessRegistration $registration, array &$fileAttachments): void
{
    if ($request->hasFile('antenuptial_file')) {
        $file = $request->file('antenuptial_file');
        
        $businessRegistrationFile = $this->storeFileInDatabase(
            $file,
            self::FILE_TYPE_ANTENUPTIAL,
            $registration->id,
            [
                'field_name' => 'antenuptial_file',
            ]
        );
        
        $fileAttachments[] = [
            'type' => self::FILE_TYPE_ANTENUPTIAL,
            'file_id' => $businessRegistrationFile->id,
            'original_name' => $businessRegistrationFile->original_filename,
            'path' => $businessRegistrationFile->file_path,
            'uploaded_at' => now()->toDateTimeString(),
        ];
    }
}
    /**
     * Store file in database and filesystem using BusinessRegistrationFile model
     */
    private function storeFileInDatabase($file, string $fileType, int $registrationId, array $additionalMetadata = []): BusinessRegistrationFile
    {
        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = $fileType . '_' . time() . '_' . Str::random(10) . '.' . $extension;
        
        // Store file in storage
        $path = $file->storeAs(
            "business-registrations/{$registrationId}/{$fileType}", 
            $filename, 
            'public'
        );
        
        // Create record in BusinessRegistrationFile
        return BusinessRegistrationFile::create([
            'business_registration_id' => $registrationId,
            'file_type' => $fileType,
            'original_filename' => $originalName,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'metadata' => array_merge([
                'original_name' => $originalName,
                'extension' => $extension,
                'uploaded_at' => now()->toDateTimeString(),
                'stored_filename' => $filename,
            ], $additionalMetadata),
        ]);
    }
    private function extractPhoneDetails(array $data): array
    {
        $phoneDetails = [];
        
        // Check for structured phones
      /*  if (isset($data['structured_phones']) && is_array($data['structured_phones'])) {
            foreach ($data['structured_phones'] as $phone) {
                if (!empty($phone['phoneNumber']) && !empty($phone['phoneType'])) {
                    $phoneDetails[] = [
                        'phoneType' => $phone['phoneType'],
                        'phoneCode' => $phone['phoneCode'] ?? '266',
                        'phoneNumber' => $phone['phoneNumber'],
                        'verified' => $phone['verified'] ?? false,
                        'source' => 'structured_phones',
                    ];
                }
            }
        } */
        
        // Check for phone_details from Flutter form
        if (isset($data['phone_details']) && is_array($data['phone_details'])) {
            foreach ($data['phone_details'] as $phone) {
                $phoneController = $phone['phoneNumber'] ?? null;
                $phoneNumber = is_array($phoneController) ? ($phoneController['text'] ?? '') : (string) $phoneController;
                
                if (!empty($phoneNumber) && !empty($phone['phoneType'])) {
                    $phoneDetails[] = [
                        'phoneType' => $phone['phoneType'],
                        'phoneCode' => $phone['phoneCode'] ?? '266',
                        'phoneNumber' => $phoneNumber,
                        'verified' => $phone['verified'] ?? false,
                        'otpController' => isset($phone['otpController']) ? 'Yes' : 'No',
                        'source' => 'phone_details',
                    ];
                }
            }
        }
        
        // Legacy phone fields
      /*  if (!empty($data['cell_phone'])) {
            $phoneDetails[] = [
                'phoneType' => 'CEL1',
                'phoneCode' => '266',
                'phoneNumber' => $data['cell_phone'],
                'verified' => false,
                'source' => 'legacy_cell_phone',
            ];
        }
        
        if (!empty($data['office_phone'])) {
            $phoneDetails[] = [
                'phoneType' => 'OFC',
                'phoneCode' => '266',
                'phoneNumber' => $data['office_phone'],
                'verified' => false,
                'source' => 'legacy_office_phone',
            ];
        } */
        
        return $phoneDetails;
    }

    private function extractAccountantDetails(array $data): array
    {
        return [
            'name' => $data['accountant_name'] ?? null,
            'tin' => $data['accountant_tin'] ?? null,
            'postal_address' => $data['accountant_postal_address'] ?? null,
            'postal_code' => $data['accountant_postal_code'] ?? null,
            'physical_address' => $data['accountant_physical_address'] ?? null,
            'chief_street_name' => $data['accountant_chief_street_name'] ?? null,
            'village' => $data['accountant_village'] ?? null,
            'town' => $data['accountant_town'] ?? null,
            'district' => $data['accountant_district'] ?? null,
            'office_phone' => $data['accountant_office_phone'] ?? null,
            'cell_phone' => $data['accountant_cell_phone'] ?? null,
            'fax1' => $data['accountant_fax1'] ?? null,
            'fax2' => $data['accountant_fax2'] ?? null,
            'email' => $data['accountant_email'] ?? null,
        ];
    }

    private function extractNominatedOfficerDetails(array $data): ?array
    {
        if (($data['is_sole_trader'] ?? false)) {
            return null;
        }

        return [
            'name' => $data['nominated_officer_name'] ?? null,
            'tin' => $data['nominated_officer_tin'] ?? null,
            'postal_address' => $data['nominated_officer_postal_address'] ?? null,
            'postal_code' => $data['nominated_officer_postal_code'] ?? null,
            'physical_address' => $data['nominated_officer_physical_address'] ?? null,
            'chief_street_name' => $data['nominated_officer_chief_street_name'] ?? null,
            'village' => $data['nominated_officer_village'] ?? null,
            'town' => $data['nominated_officer_town'] ?? null,
            'district' => $data['nominated_officer_district'] ?? null,
            'office_phone' => $data['nominated_officer_office_phone'] ?? null,
            'cell_phone' => $data['nominated_officer_cell_phone'] ?? null,
            'fax1' => $data['nominated_officer_fax1'] ?? null,
            'fax2' => $data['nominated_officer_fax2'] ?? null,
            'email' => $data['nominated_officer_email'] ?? null,
        ];
    }

    private function storeFile($file, string $type, int $registrationId): string
    {
        $path = $file->store("business-registrations/{$registrationId}/{$type}", 'public');
        
        BusinessRegistrationFile::create([
            'business_registration_id' => $registrationId,
            'file_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ],
        ]);

        return $path;
    }
}