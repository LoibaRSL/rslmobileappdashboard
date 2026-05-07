<?php
// app/Http/Controllers/TinRegistrationController.php

namespace App\Http\Controllers;

use App\Models\TinRegistration;
use App\Models\Employer;
use App\Models\RegistrationFile;
use App\Models\PhoneDetail;
use App\Http\Requests\TinRegistrationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Requests\TinAmendmentRequest;
use Illuminate\Support\Facades\Log;
use App\Models\BankingDetail; // Add this
use App\Models\MobileMoneyDetail; 
use Illuminate\Support\Facades\Validator;

class TinRegistrationsController extends Controller
{
public function register(Request $request): JsonResponse
{
    DB::beginTransaction();
    
    try {
        // Create main registration
        $registration = TinRegistration::create([
            'document_locator' => $request->documentLocator,
            'receive_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'registration_type' => $request->registrationType,
            'legacy_tin' => $request->legacyTIN,
            'title' => $request->title,
            'surname' => $request->surname,
            'forenames' => $request->forenames,
            'maiden_name' => $request->maidenName,
            'date_of_birth' => $request->dateOfBirth,
            'country_of_birth' => $request->countryOfBirth,
            'country_of_citizenship' => $request->countryOfCitizenship,
            'country_of_residence' => $request->countryOfResidence,
            'lesotho_id_number' => $request->lesothoIdNumber,
            'lesotho_id_expiry' => $request->lesothoIdExpiry,
            'country_of_issue' => $request->countryOfIssue,
            'other_id_type' => $request->otherIdType,
            'other_id_number' => $request->otherIdNumber,
            'other_id_expiry' => $request->otherIdExpiry,
            'post_country' => $request->postCountry,
            'post_type' => $request->postType,
            'post_number' => $request->postNumber,
            'post_code' => $request->postCode,
            'post_address1' => $request->postAddress1,
            'post_address2' => $request->postAddress2,
            'post_address3' => $request->postAddress3,
            'post_address4' => $request->postAddress4,
            'post_district' => $request->postDistrict,
            'physical_country' => $request->physicalCountry,
            'street_name' => $request->streetName,
            'nearest_place' => $request->nearestPlace,
            'village' => $request->village,
            'town' => $request->town,
            'physical_district' => $request->physicalDistrict,
            'email' => $request->email,
            'marital_status' => $request->maritalStatus,
            'condition_of_marriage' => $request->conditionOfMarriage,
            'spouse_tin' => $request->spouseTIN,
            'spouse_name' => $request->spouseName,
            'spouse_maiden_name' => $request->spouseMaidenName,
            'spouse_personal_id' => $request->spousePersonalId,
            'printed_name' => $request->printedName,
            'declaration_accepted' => true,
        ]);

        // Generate Reference
        $ref = $registration->generateTIN();
        $registration->update(['ref' => $ref]);

        // Save Phone Details (Multiple)
        if ($request->has('phoneDetails') && is_array($request->phoneDetails)) {
            foreach ($request->phoneDetails as $phoneData) {
                // Only create if we have a phone number
                if (!empty($phoneData['phoneNumber'])) {
                    PhoneDetail::create([
                        'tin_registration_id' => $registration->id,
                        'phone_type' => $phoneData['phoneType'] ?? 'CEL1',
                        'phone_code' => $phoneData['phoneCode'] ?? '+266',
                        'phone_number' => $phoneData['phoneNumber'],
                    ]);
                }
            }
        }

        // Save Mobile Money Details (Multiple)
        if ($request->has('mobileMoneyDetails') && is_array($request->mobileMoneyDetails)) {
            foreach ($request->mobileMoneyDetails as $mobileData) {
                // Only create if we have a mobile money number
                if (!empty($mobileData['mobileMoneyNumber'])) {
                    MobileMoneyDetail::create([
                        'tin_registration_id' => $registration->id,
                        'mobile_money_type' => $mobileData['mobileMoneyType'] ?? null,
                        'mobile_money_number' => $mobileData['mobileMoneyNumber'] ?? null,
                    ]);
                }
            }
        }

        // Save Banking Details (Multiple)
        if ($request->has('bankingDetails') && is_array($request->bankingDetails)) {
            foreach ($request->bankingDetails as $index =>$bankingData) {
                // Only create if we have account number
                if (!empty($bankingData['accountNumber'])) {
                  $bankDetails =  BankingDetail::create([
                        'tin_registration_id' => $registration->id,
                        'bank_code' => $bankingData['bankCode'] ?? null,
                        'bank_country' => $bankingData['bankCountry'] ?? 'LS',
                        'account_holder_name' => $bankingData['accountHolderName'] ?? null,
                        'branch' => $bankingData['branch'] ?? null,
                        'account_number' => $bankingData['accountNumber'] ?? null,
                        'account_type' => $bankingData['accountType'] ?? null,
                        'swift_code' => $bankingData['swiftCode'] ?? null,
                        'branch_code' => $bankingData['branchCode'] ?? null,
                    ]);

                                        // Handle Bank Details file upload
                    if ($request->hasFile("bankingDetails.$index.proofFile")) {
                        $file = $request->file("bankingDetails.$index.proofFile");
                        $path = $this->storeFile($file, 'proof_file', $registration->id);
                        
                        $bankDetails->update(['file_path' => $path]);
                        
                        RegistrationFile::create([
                            'tin_registration_id' => $registration->id,
                            'file_type' => 'proof_file',
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }
        }

        // Save employers
        if ($request->has('employers') && is_array($request->employers)) {
            foreach ($request->employers as $index => $employerData) {
                // Only create employer if name is not empty
                if (!empty($employerData['name'])) {
                    $employer = Employer::create([
                        'tin_registration_id' => $registration->id,
                        'employer_name' => $employerData['name'],
                    ]);

                    // Handle employer file upload
                    if ($request->hasFile("employers.$index.file")) {
                        $file = $request->file("employers.$index.file");
                        $path = $this->storeFile($file, 'employer_proof', $registration->id);
                        
                        $employer->update(['file_path' => $path]);
                        
                        RegistrationFile::create([
                            'tin_registration_id' => $registration->id,
                            'file_type' => 'employer_proof',
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }
        }

        // Handle file uploads
        $this->handleFileUploads($request, $registration);

        // Send email verification
        $this->sendEmailVerification($registration);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted successfully',
            'ref' => $ref,
            'registration_id' => $registration->id,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Registration failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}
   /**
     * Get user data by TIN for amendment
     */
    public function getUserDataByTin($tin): JsonResponse
    {
        try {
            Log::info("Fetching user data for TIN: {$tin}");

            $registration = TinRegistration::with(['employers', 'files'])
                ->where('tin', $tin)
                ->first();

            if (!$registration) {
                Log::warning("TIN not found: {$tin}");
                return response()->json([
                    'success' => false,
                    'message' => 'TIN not found or invalid',
                ], 404);
            }

            // Check if there's a pending amendment
            if ($registration->hasPendingAmendment()) {
                Log::warning("Pending amendment exists for TIN: {$tin}");
                return response()->json([
                    'success' => false,
                    'message' => 'You have a pending amendment. Please wait for approval before submitting another.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatRegistrationData($registration),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch user data for TIN {$tin}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit TIN amendment - Update existing record directly
     */
    /**
 * Submit TIN amendment - Update existing record directly
 */
/**
 * Submit TIN amendment - Update existing record directly
 */
/**
 * Submit TIN amendment - Update existing record or create new if TIN not found
 */
public function amend(Request $request): JsonResponse
{
    DB::beginTransaction();

   

    try {
        Log::info("Starting amendment process for TIN: " . $request->tin);

        // Validate required fields (remove exists validation for TIN)
        $validator = validator($request->all(), [
            'tin' => 'required|string', // Remove exists validation
            'title' => 'required|in:MR,MRS,MISS,MS',
            'surname' => 'required|string|max:255',
            'forenames' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'required|email',
            
            // Make employers optional
            'employers' => 'sometimes|array',
            'employers.*.name' => 'sometimes|string|max:255',
            
            // Other optional fields
            'selectedSections' => 'sometimes|string',
            'phoneDetails' => 'sometimes|array',
            'bankingDetails' => 'sometimes|array',
            'mobileMoneyDetails' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            Log::info("Starting amendment process for TIN: " . $validator->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Try to find the existing registration, or create new if not found
        $registration = TinRegistration::where('tin', $request->tin)->first();

        if (!$registration) {
            Log::info("TIN not found: {$request->tin}. Creating new registration as amendment.");
            
            // Create new registration with amendment type
            $registration = TinRegistration::create([
                'tin' => $request->tin,
                'registration_type' => 'AMND',
                'receive_date' => now()->format('Y-m-d'),
                'effective_date' => now()->format('Y-m-d'),
                
                // Personal Information
                'title' => $request->title,
                'surname' => $request->surname,
                'forenames' => $request->forenames,
                'maiden_name' => $request->maiden_name ?? null,
                'date_of_birth' => $request->date_of_birth,
                
                // Identification
                'country_of_birth' => $request->country_of_birth ?? null,
                'country_of_citizenship' => $request->country_of_citizenship ?? null,
                'country_of_residence' => $request->country_of_residence ?? null,
                'passport_number' => $request->passport_number ?? null,
                'passport_expiry_date' => $request->passport_expiry_date ?? null,
                'lesotho_id_number' => $request->passport_number ?? null,
                'lesotho_id_expiry' => $request->passport_expiry_date ?? null,
                'country_of_issue' => $request->country_of_issue ?? null,
                'other_id_type' => $request->other_id_type ?? null,
                'other_id_number' => $request->other_id_number ?? null,
                'other_id_expiry' => $request->other_id_expiry ?? null,
                
                // Correspondence - Postal Address
                'post_country' => $request->post_country ?? null,
                'post_type' => $request->post_type ?? null,
                'post_number' => $request->post_number ?? null,
                'post_code' => $request->post_code ?? null,
                'post_address1' => $request->post_address1 ?? null,
                'post_address2' => $request->post_address2 ?? null,
                'post_address3' => $request->post_address3 ?? null,
                'post_address4' => $request->post_address4 ?? null,
                'post_district' => $request->post_district ?? null,
                
                // Correspondence - Physical Address
                'physical_country' => $request->physical_country ?? null,
                'street_name' => $request->street_name ?? null,
                'nearest_place' => $request->nearest_place ?? null,
                'village' => $request->village ?? null,
                'town' => $request->town ?? null,
                'physical_district' => $request->physical_district ?? null,
                
                // Contact
                'phone_number' => $request->phone_number ?? null,
                'email' => $request->email,
                'email_verified' => false,
                
                // Marital
                'marital_status' => $request->marital_status ?? null,
                'condition_of_marriage' => $request->condition_of_marriage ?? null,
                'spouse_tin' => $request->spouse_tin ?? null,
                'spouse_name' => $request->spouse_name ?? null,
                'spouse_maiden_name' => $request->spouse_maiden_name ?? null,
                'spouse_personal_id' => $request->spouse_personal_id ?? null,
                
                // Declaration
                'printed_name' => $request->printed_name ?? null,
                'declaration_accepted' => true,
                
                // Amendment tracking
                'status' => 'PENDING',
                'amendment_submitted_at' => now(),
                'amendment_notes' => 'New registration created via amendment submission on ' . now()->format('Y-m-d H:i:s'),
            ]);
            
            // Generate reference for new registration
            $ref = $registration->generateTIN();
            $registration->update(['ref' => $ref]);
            
            $isNewRegistration = true;
        } else {
            // Check for pending amendments only if it's an existing registration
            if ($registration->hasPendingAmendment()) {
                 Log::info("Form Has pending amendment");
                return response()->json([
                    'success' => false,
                    'message' => 'You have a pending amendment. Please wait for approval.',
                ], 422);
            }
            
            $isNewRegistration = false;
        }

        // Get selected sections from request
        $selectedSections = $request->selectedSections ?? '';
        $selectedSectionsArray = $selectedSections ? explode(',', $selectedSections) : [];

        Log::info("Selected sections for amendment: " . $selectedSections);

        // If it's an existing registration, update it
        if (!$isNewRegistration) {
            // Update the registration with new data
            $updateData = [
                 'tin' => $request->tin,
                'registration_type' => 'AMND',
                'receive_date' => now()->format('Y-m-d'),
                'effective_date' => now()->format('Y-m-d'),
                
                // Personal Information
                'title' => $request->title,
                'surname' => $request->surname,
                'forenames' => $request->forenames,
                'maiden_name' => $request->maiden_name ?? null,
                'date_of_birth' => $request->date_of_birth,
                
                // Identification
                'country_of_birth' => $request->country_of_birth ?? null,
                'country_of_citizenship' => $request->country_of_citizenship ?? null,
                'country_of_residence' => $request->country_of_residence ?? null,
                'passport_number' => $request->passport_number ?? null,
                'passport_expiry_date' => $request->passport_expiry_date ?? null,
                'lesotho_id_number' => $request->passport_number ?? null,
                'lesotho_id_expiry' => $request->passport_expiry_date ?? null,
                'country_of_issue' => $request->country_of_issue ?? null,
                'other_id_type' => $request->other_id_type ?? null,
                'other_id_number' => $request->other_id_number ?? null,
                'other_id_expiry' => $request->other_id_expiry ?? null,
                
                // Correspondence - Postal Address
                'post_country' => $request->post_country ?? null,
                'post_type' => $request->post_type ?? null,
                'post_number' => $request->post_number ?? null,
                'post_code' => $request->post_code ?? null,
                'post_address1' => $request->post_address1 ?? null,
                'post_address2' => $request->post_address2 ?? null,
                'post_address3' => $request->post_address3 ?? null,
                'post_address4' => $request->post_address4 ?? null,
                'post_district' => $request->post_district ?? null,
                
                // Correspondence - Physical Address
                'physical_country' => $request->physical_country ?? null,
                'street_name' => $request->street_name ?? null,
                'nearest_place' => $request->nearest_place ?? null,
                'village' => $request->village ?? null,
                'town' => $request->town ?? null,
                'physical_district' => $request->physical_district ?? null,
                
                // Contact
                'phone_number' => $request->phone_number ?? null,
                'email' => $request->email,
                'email_verified' => false,
                
                // Marital
                'marital_status' => $request->marital_status ?? null,
                'condition_of_marriage' => $request->condition_of_marriage ?? null,
                'spouse_tin' => $request->spouse_tin ?? null,
                'spouse_name' => $request->spouse_name ?? null,
                'spouse_maiden_name' => $request->spouse_maiden_name ?? null,
                'spouse_personal_id' => $request->spouse_personal_id ?? null,
                
                // Declaration
                'printed_name' => $request->printed_name ?? null,
                'declaration_accepted' => true,
                
                // Amendment tracking
                'status' => 'PENDING',
                'amendment_submitted_at' => now(),
                'amendment_notes' => 'New registration created via amendment submission on ' . now()->format('Y-m-d H:i:s'),

            ];

            // Only update fields for selected sections
            $finalUpdateData = [];
            if (empty($selectedSectionsArray)) {
                // If no sections selected, update all fields (backward compatibility)
                $finalUpdateData = $updateData;
            } else {
                // Only update fields for selected sections
                foreach ($selectedSectionsArray as $section) {
                    switch ($section) {
                        case 'personal':
                            $finalUpdateData['title'] = $updateData['title'];
                            $finalUpdateData['surname'] = $updateData['surname'];
                            $finalUpdateData['forenames'] = $updateData['forenames'];
                            $finalUpdateData['maiden_name'] = $updateData['maiden_name'];
                            $finalUpdateData['date_of_birth'] = $updateData['date_of_birth'];
                            break;
                            
                        case 'identification':
                            $finalUpdateData['country_of_birth'] = $updateData['country_of_birth'];
                            $finalUpdateData['country_of_citizenship'] = $updateData['country_of_citizenship'];
                            $finalUpdateData['country_of_residence'] = $updateData['country_of_residence'];
                            $finalUpdateData['passport_number'] = $updateData['passport_number'];
                            $finalUpdateData['passport_expiry_date'] = $updateData['passport_expiry_date'];
                            break;
                            
                        case 'correspondence':
                            $finalUpdateData['post_country'] = $updateData['post_country'];
                            $finalUpdateData['post_type'] = $updateData['post_type'];
                            $finalUpdateData['post_number'] = $updateData['post_number'];
                            $finalUpdateData['post_code'] = $updateData['post_code'];
                            $finalUpdateData['post_address1'] = $updateData['post_address1'];
                            $finalUpdateData['post_address2'] = $updateData['post_address2'];
                            $finalUpdateData['post_address3'] = $updateData['post_address3'];
                            $finalUpdateData['post_address4'] = $updateData['post_address4'];
                            $finalUpdateData['post_district'] = $updateData['post_district'];
                            $finalUpdateData['physical_country'] = $updateData['physical_country'];
                            $finalUpdateData['street_name'] = $updateData['street_name'];
                            $finalUpdateData['nearest_place'] = $updateData['nearest_place'];
                            $finalUpdateData['village'] = $updateData['village'];
                            $finalUpdateData['town'] = $updateData['town'];
                            $finalUpdateData['physical_district'] = $updateData['physical_district'];
                            $finalUpdateData['phone_number'] = $updateData['phone_number'];
                            $finalUpdateData['email'] = $updateData['email'];
                            break;
                            
                        case 'employment':
                            // Employment data is handled separately in employers table
                            break;
                            
                        case 'banking':
                            // Banking data is handled separately in banking_details table
                            break;
                    }
                }
                
                // Always update these fields regardless of sections
                $finalUpdateData['registration_type'] = $updateData['registration_type'];
                $finalUpdateData['printed_name'] = $updateData['printed_name'];
                $finalUpdateData['declaration_accepted'] = $updateData['declaration_accepted'];
                $finalUpdateData['status'] = $updateData['status'];
                $finalUpdateData['amendment_submitted_at'] = $updateData['amendment_submitted_at'];
                $finalUpdateData['amendment_notes'] = $updateData['amendment_notes'];
            }

            $registration->update($finalUpdateData);
        }

        // Handle employers - only if employment section is selected or no sections specified
        if (empty($selectedSectionsArray) || in_array('employment', $selectedSectionsArray)) {
            // Only delete existing employers if it's an existing registration
            if (!$isNewRegistration) {
                $registration->employers()->delete();
            }
            
            if ($request->has('employers') && is_array($request->employers)) {
                foreach ($request->employers as $index => $employerData) {
                    // Only create employer if name is not empty
                    if (!empty($employerData['name'])) {
                        $employer = Employer::create([
                            'tin_registration_id' => $registration->id,
                            'employer_name' => $employerData['name'],
                        ]);

                        // Handle employer file upload
                        if ($request->hasFile("employers.$index.file")) {
                            $file = $request->file("employers.$index.file");
                            $path = $this->storeFile($file, 'employer_proof', $registration->id);
                            
                            $employer->update(['file_path' => $path]);
                            
                            RegistrationFile::create([
                                'tin_registration_id' => $registration->id,
                                'file_type' => 'employer_proof',
                                'file_path' => $path,
                                'file_name' => $file->getClientOriginalName(),
                            ]);
                        }
                    }
                }
            }
        }

        // Handle Phone Details - only if correspondence section is selected or no sections specified
        if (empty($selectedSectionsArray) || in_array('correspondence', $selectedSectionsArray)) {
            if (!$isNewRegistration) {
                $registration->phoneDetails()->delete();
            }
            
            if ($request->has('phoneDetails') && is_array($request->phoneDetails)) {
                foreach ($request->phoneDetails as $phoneData) {
                    // Only create if we have a phone number
                    if (!empty($phoneData['phoneNumber'])) {
                        PhoneDetail::create([
                            'tin_registration_id' => $registration->id,
                            'phone_type' => $phoneData['phoneType'] ?? 'CEL1',
                            'phone_code' => $phoneData['phoneCode'] ?? '+266',
                            'phone_number' => $phoneData['phoneNumber'],
                        ]);
                    }
                }
            }
        }

        // Handle Banking Details - only if banking section is selected or no sections specified
        if (empty($selectedSectionsArray) || in_array('banking', $selectedSectionsArray)) {
            if (!$isNewRegistration) {
                $registration->bankingDetails()->delete();
            }
            
            if ($request->has('bankingDetails') && is_array($request->bankingDetails)) {
                foreach ($request->bankingDetails as $index => $bankingData) {
                    if (!empty($bankingData['accountNumber'])) {
                      $bankDetails = BankingDetail::create([
                            'tin_registration_id' => $registration->id,
                            'bank_code' => $bankingData['bankCode'] ?? null,
                            'bank_country' => $bankingData['bankCountry'] ?? 'LS',
                            'account_holder_name' => $bankingData['accountHolderName'] ?? null,
                            'branch' => $bankingData['branch'] ?? null,
                            'account_number' => $bankingData['accountNumber'] ?? null,
                            'account_type' => $bankingData['accountType'] ?? null,
                            'swift_code' => $bankingData['swiftCode'] ?? null,
                            'branch_code' => $bankingData['branchCode'] ?? null,
                        ]);

                        if ($request->hasFile("bankingDetails.$index.proofFile")) {
                            $file = $request->file("bankingDetails.$index.proofFile");
                            $path = $this->storeFile($file, 'proof_file', $registration->id);
                            
                            $bankDetails->update(['file_path' => $path]);
                            
                            RegistrationFile::create([
                                'tin_registration_id' => $registration->id,
                                'file_type' => 'proof_file',
                                'file_path' => $path,
                                'file_name' => $file->getClientOriginalName(),
                            ]);
                        }
                    }
                }
            }
        }

        // Handle Mobile Money Details - only if banking section is selected or no sections specified
        if (empty($selectedSectionsArray) || in_array('banking', $selectedSectionsArray)) {
            if (!$isNewRegistration) {
                $registration->mobileMoneyDetails()->delete();
            }
            
            if ($request->has('mobileMoneyDetails') && is_array($request->mobileMoneyDetails)) {
                foreach ($request->mobileMoneyDetails as $mobileData) {
                    if (!empty($mobileData['mobileMoneyNumber'])) {
                        MobileMoneyDetail::create([
                            'tin_registration_id' => $registration->id,
                            'mobile_money_type' => $mobileData['mobileMoneyType'] ?? null,
                            'mobile_money_number' => $mobileData['mobileMoneyNumber'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Handle file uploads for amendment
        $this->handleFileUploads($request, $registration);

        // Send email verification for amendment
        $this->sendEmailVerification($registration);

        DB::commit();

        Log::info("Amendment submitted successfully for TIN: {$request->tin}");

        return response()->json([
            'success' => true,
            'message' => $isNewRegistration ? 'Registration created successfully via amendment submission' : 'Amendment submitted successfully',
            'ref' => $registration->ref,
            'registration_id' => $registration->id,
            'is_new_registration' => $isNewRegistration,
        ], $isNewRegistration ? 201 : 200);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Amendment failed: ' . $e->getMessage());
        Log::error('Amendment error trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Submission failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    /**
     * Check if user has pending amendment
     */
    public function checkPendingAmendment($tin): JsonResponse
    {
        try {
            $registration = TinRegistration::where('tin', $tin)->first();

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'has_pending_amendment' => $registration->hasPendingAmendment(),
                'status' => $registration->status,
                'amendment_submitted_at' => $registration->amendment_submitted_at,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to check pending amendment for TIN {$tin}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check amendment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format registration data for response
     */
    private function formatRegistrationData(TinRegistration $registration): array
    {
        return [
            'title' => $registration->title,
            'tin' => $registration->tin,
            'surname' => $registration->surname,
            'forenames' => $registration->forenames,
            'maiden_name' => $registration->maiden_name,
            'date_of_birth' => $registration->date_of_birth?->format('Y-m-d'),
            'country_of_birth' => $registration->country_of_birth,
            'country_of_citizenship' => $registration->country_of_citizenship,
            'country_of_residence' => $registration->country_of_residence,
            'lesotho_id_number' => $registration->lesotho_id_number,
            'lesotho_id_expiry' => $registration->lesotho_id_expiry?->format('Y-m-d'),
            'country_of_issue' => $registration->country_of_issue,
            'other_id_type' => $registration->other_id_type,
            'other_id_number' => $registration->other_id_number,
            'other_id_expiry' => $registration->other_id_expiry?->format('Y-m-d'),
            'post_country' => $registration->post_country,
            'post_type' => $registration->post_type,
            'post_number' => $registration->post_number,
            'post_code' => $registration->post_code,
            'post_address1' => $registration->post_address1,
            'post_address2' => $registration->post_address2,
            'post_address3' => $registration->post_address3,
            'post_address4' => $registration->post_address4,
            'post_district' => $registration->post_district,
            'physical_country' => $registration->physical_country,
            'street_name' => $registration->street_name,
            'nearest_place' => $registration->nearest_place,
            'village' => $registration->village,
            'town' => $registration->town,
            'physical_district' => $registration->physical_district,
            //'phone_type' => $registration->phone_type,
            //'phone_code' => $registration->phone_code,
            //'phone_number' => $registration->phone_number,
                    // Phone Details
        'phone_details' => $registration->phoneDetails->map(function($phone) {
            return [
                'phone_type' => $phone->phone_type,
                'phone_code' => $phone->phone_code,
                'phone_number' => $phone->phone_number,
                'full_phone_number' => $phone->full_phone_number,
                'phone_type_text' => $phone->phone_type_text,
            ];
        })->toArray(),
            'email' => $registration->email,
            'marital_status' => $registration->marital_status,
            'condition_of_marriage' => $registration->condition_of_marriage,
            'spouse_tin' => $registration->spouse_tin,
            'spouse_name' => $registration->spouse_name,
            'spouse_maiden_name' => $registration->spouse_maiden_name,
            'spouse_personal_id' => $registration->spouse_personal_id,
            // Add arrays for banking and mobile money
        'banking_details' => $registration->bankingDetails->map(function($bank) {
            return [
                'bank_code' => $bank->bank_code,
                'bank_name' => $bank->bank_name, // This uses the accessor
                'bank_country' => $bank->bank_country,
                'account_holder_name' => $bank->account_holder_name,
                'branch' => $bank->branch,
                'account_number' => $bank->account_number,
                'account_type' => $bank->account_type,
                'swift_code' => $bank->swift_code,
                'branch_code' => $bank->branch_code,
            ];
        })->toArray(),
        
        'mobile_money_details' => $registration->mobileMoneyDetails->map(function($mobile) {
            return [
                'mobile_money_type' => $mobile->mobile_money_type,
                'mobile_money_number' => $mobile->mobile_money_number,
            ];
        })->toArray(),
            'printed_name' => $registration->printed_name,
            'status' => $registration->status,

            'employers' => $registration->employers->map(function($employer) {
                return [
                    'name' => $employer->employer_name,
                    'file_path' => $employer->file_path,
                ];
            })->toArray(),
            'files' => $registration->files->map(function($file) {
                return [
                    'file_type' => $file->file_type,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path,
                ];
            })->toArray(),
        ];
    }


private function generateAmendmentDocumentLocator(): string
{
    $date = now();
    $day = $date->format('d');
    $month = strtoupper($date->format('M'));
    $year = $date->format('Y');
    $random = Str::random(4);
    
    return "AMD{$day}{$month}{$year}{$random}";
}

    private function handleFileUploads($request, $registration): void
    {
        $fileMappings = [
            'lesothoId' => 'lesotho_id',
            'passport' => 'passport',
            'otherId' => 'other_id',
            'foreignId' => 'foreign_id',
            'antenuptial' => 'antenuptial'
            
        ];

        foreach ($fileMappings as $requestKey => $fileType) {
            if ($request->hasFile($requestKey)) {
                $file = $request->file($requestKey);
                $path = $this->storeFile($file, $fileType, $registration->id);
                
                RegistrationFile::create([
                    'tin_registration_id' => $registration->id,
                    'file_type' => $fileType,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }
    }

    private function storeFile($file, $type, $registrationId): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '.' . $extension;
        $path = "registrations/{$registrationId}/{$type}/{$filename}";
        
        Storage::disk('public')->put($path, file_get_contents($file));
        
        return $path;
    }

    private function sendEmailVerification($registration): void
    {
        $verificationCode = Str::random(6);
        $registration->update(['email_verification_code' => $verificationCode]);

        // Here you would integrate with your email service
        // Mail::to($registration->email)->send(new EmailVerificationMail($verificationCode));
        
        // For now, we'll log it
        \Log::info("Email verification code for {$registration->email}: {$verificationCode}");
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $registration = TinRegistration::where('email', $request->email)
            ->where('email_verification_code', $request->code)
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 422);
        }

        $registration->update([
            'email_verified' => true,
            'email_verification_code' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
        ]);
    }

    public function checkStatus($ref): JsonResponse
    {
        $registration = TinRegistration::where('ref', $ref)
            ->orWhere('ref', $ref)
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registration' => [
                'tin' => $registration->tin,
                'status' => $registration->status,
                'document_locator' => $registration->document_locator,
                'full_name' => "{$registration->forenames} {$registration->surname}",
                'submitted_at' => $registration->created_at->format('Y-m-d H:i:s'),
                'remarks' => $registration->remarks,
            ],
        ]);
    }

    public function getRegistration($id): JsonResponse
    {
        $registration = TinRegistration::with(['employers', 'files'])->find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registration' => $registration,
        ]);
    }
}