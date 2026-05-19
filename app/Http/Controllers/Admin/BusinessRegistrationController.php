<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessRegistration;
use App\Models\BusinessContactDetail;
use App\Models\BusinessStructuredPhone;
use App\Models\BusinessAccountantDetail;
use App\Models\BusinessNominatedOfficerDetail;
use App\Models\BusinessDirectorPartner;
use App\Models\BusinessBankDetail;
use App\Models\BusinessMobileMoneyDetail;
use App\Models\BusinessPersonalIdentification;
use App\Models\BusinessSoleTraderDetail;
use App\Models\BusinessVatDetail;
use App\Models\BusinessPayeDetail;
use App\Models\BusinessFbtDetail;
use App\Models\BusinessWhtDetail;
use App\Models\BusinessAntlDetail;
use App\Models\BusinessPlasticLevyDetail;
use App\Models\BusinessSbtDetail;
use App\Models\BusinessDeclarationDetail;
use App\Models\BusinessRegistrationFile;
use App\Models\BusinessSoapIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessRegistrationController extends Controller
{


    /**
     * Display list of business registrations
     */
    public function index(Request $request)
    {
        $query = BusinessRegistration::with(['contactDetails', 'structuredPhones', 'files'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->application_type);
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('type')) {
            if ($request->type === 'individual') {
                $query->where('is_sole_trader', true);
            } elseif ($request->type === 'business') {
                $query->where('is_sole_trader', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('legal_name', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('old_tin', 'like', "%{$search}%")
                  ->orWhere('new_tin', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $registrations = $query->paginate(15)->withQueryString();
        
        // Get statistics
        $stats = [
            'total' => BusinessRegistration::count(),
            'pending' => BusinessRegistration::where('status', 'pending')->count(),
            'approved' => BusinessRegistration::where('status', 'approved')->count(),
            'rejected' => BusinessRegistration::where('status', 'rejected')->count(),
            'submitted' => BusinessRegistration::where('status', 'submitted')->count(),
            'total_today' => BusinessRegistration::whereDate('created_at', today())->count(),
            'individual' => BusinessRegistration::where('is_sole_trader', true)->count(),
            'business' => BusinessRegistration::where('is_sole_trader', false)->count(),
        ];

        return view('admin.registrations.index', compact('registrations', 'stats'));
    }

    /**
     * Show form to create new business registration (for Digital Services)
     */
 

    /**
   

    /**
     * Show detailed view of a registration
     */
    public function show($id)
    {
        $registration = BusinessRegistration::with([
            'contactDetails',
            'structuredPhones',
            'accountantDetails',
            'nominatedOfficerDetails',
            'directorPartners',
            'bankDetails',
            'mobileMoneyDetails',
            'personalIdentification',
            'soleTraderDetails',
            'vatDetails',
            'payeDetails',
            'fbtDetails',
            'whtDetails',
            'antlDetails',
            'plasticLevyDetails',
            'sbtDetails',
            'declarationDetails',
            'files',
            'soapIntegration',
            'user'
        ])->findOrFail($id);

        // Get related applications if any
        $relatedApplications = BusinessRegistration::where('old_tin', $registration->new_tin)
            ->orWhere('new_tin', $registration->old_tin)
            ->where('id', '!=', $id)
            ->limit(5)
            ->get();

        $activities = $this->getActivityLog($id);

        return view('admin.registrations.show', compact('registration', 'activities', 'relatedApplications'));
    }

    /**
     * Show form to edit business registration
     */
    public function edit($id)
    {
        $registration = BusinessRegistration::with([
            'contactDetails',
            'structuredPhones',
            'accountantDetails',
            'nominatedOfficerDetails',
            'directorPartners',
            'bankDetails',
            'mobileMoneyDetails',
            'personalIdentification',
            'soleTraderDetails',
            'vatDetails',
            'payeDetails',
            'fbtDetails',
            'whtDetails',
            'antlDetails',
            'plasticLevyDetails',
            'sbtDetails',
            'declarationDetails'
        ])->findOrFail($id);
        
        return view('admin.registrations.edit', compact('registration'));
    }

    /**
     * Update business registration
     */
    public function update(Request $request, $id)
    {
        $registration = BusinessRegistration::findOrFail($id);

        $validated = $request->validate([
            'legal_name' => 'required|string|max:255',
            'business_type' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'physical_address' => 'required|string',
            'postal_address' => 'nullable|string',
            'application_type' => 'nullable|string',
            'is_sole_trader' => 'boolean',
            'old_tin' => 'nullable|string',
            'new_tin' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'surname' => 'nullable|string',
            'forename' => 'nullable|string',
            'maiden_name' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected,submitted',
        ]);

        try {
            DB::beginTransaction();

            // Update main registration
            $registration->update([
                'legal_name' => $validated['legal_name'],
                'business_type' => $validated['business_type'],
                'email' => $validated['email'],
                'application_type' => $validated['application_type'] ?? $registration->application_type,
                'is_sole_trader' => $validated['is_sole_trader'] ?? $registration->is_sole_trader,
                'old_tin' => $validated['old_tin'] ?? $registration->old_tin,
                'new_tin' => $validated['new_tin'] ?? $registration->new_tin,
                'registration_number' => $validated['registration_number'] ?? $registration->registration_number,
                'surname' => $validated['surname'] ?? $registration->surname,
                'forename' => $validated['forename'] ?? $registration->forename,
                'maiden_name' => $validated['maiden_name'] ?? $registration->maiden_name,
            ]);

            // Update or create contact details
            if ($registration->contactDetails) {
                $registration->contactDetails->update([
                    'physical_address' => $validated['physical_address'],
                    'postal_address' => $validated['postal_address'] ?? $validated['physical_address'],
                    'email' => $validated['email'],
                    'cell_phone' => $validated['phone'],
                ]);
            }

            // Update or create structured phone
            $phone = $registration->structuredPhones()->where('phone_type', 'CEL1')->first();
            if ($phone) {
                $phone->update(['phone_number' => $validated['phone']]);
            }

            // Log the activity
            $this->logActivity($registration->id, 'updated', 'Registration updated by ' . auth()->user()->name);

            DB::commit();

            return redirect()->route('admin.registrations.index')
                ->with('success', 'Registration updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update registration: ' . $e->getMessage());
        }
    }

    /**
     * Delete a registration (soft delete or hard delete)
     */
    public function destroy($id)
    {
        try {
            $registration = BusinessRegistration::findOrFail($id);
            
            // Only allow deletion of pending registrations
            if ($registration->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending registrations can be deleted.');
            }
            
            // Delete related files from storage
            foreach ($registration->files as $file) {
                Storage::disk($file->disk)->delete($file->file_path);
                $file->delete();
            }
            
            $registration->delete();

            return redirect()->route('admin.registrations.index')
                ->with('success', 'Registration deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Registration deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete registration: ' . $e->getMessage());
        }
    }

    /**
     * Approve a registration
     */
    public function approve($id)
    {
        try {
            DB::beginTransaction();

            $registration = BusinessRegistration::findOrFail($id);
            
            if ($registration->status === 'approved') {
                return redirect()->back()->with('warning', 'This registration is already approved.');
            }

            $registration->status = 'approved';
            $registration->approved_at = now();
            $registration->approved_by = auth()->id();
            $registration->save();

            $this->logActivity($id, 'approved', 'Registration approved by ' . auth()->user()->name);

            // Update SOAP integration status if exists
            if ($registration->soapIntegration) {
                $registration->soapIntegration->update([
                    'soap_status' => 'success',
                    'soap_reference' => $registration->reference_number,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.registrations.index')
                ->with('success', 'Registration approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration approval failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve registration: ' . $e->getMessage());
        }
    }

    /**
     * Reject a registration
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $registration = BusinessRegistration::findOrFail($id);
            
            if ($registration->status === 'rejected') {
                return redirect()->back()->with('warning', 'This registration is already rejected.');
            }

            $registration->status = 'rejected';
            $registration->rejected_at = now();
            $registration->rejected_by = auth()->id();
            $registration->rejection_reason = $request->rejection_reason;
            $registration->save();

            $this->logActivity($id, 'rejected', 'Registration rejected by ' . auth()->user()->name . '. Reason: ' . $request->rejection_reason);

            DB::commit();

            return redirect()->route('admin.registrations.index')
                ->with('success', 'Registration rejected successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration rejection failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject registration: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve registrations
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:business_registrations,id'
        ]);

        try {
            DB::beginTransaction();

            $ids = is_string($request->registration_ids) 
                ? json_decode($request->registration_ids, true) 
                : $request->registration_ids;
            
            $count = 0;
            foreach ($ids as $id) {
                $registration = BusinessRegistration::find($id);
                if ($registration && $registration->status === 'pending') {
                    $registration->status = 'approved';
                    $registration->approved_at = now();
                    $registration->approved_by = auth()->id();
                    $registration->save();
                    $this->logActivity($id, 'approved', 'Bulk approved by ' . auth()->user()->name);
                    $count++;
                }
            }

            DB::commit();

            return redirect()->route('admin.registrations.index')
                ->with('success', "{$count} registration(s) approved successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk approval failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to bulk approve registrations.');
        }
    }

    /**
     * Bulk reject registrations
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:business_registrations,id',
            'bulk_rejection_reason' => 'required|string|min:5|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $ids = is_string($request->registration_ids) 
                ? json_decode($request->registration_ids, true) 
                : $request->registration_ids;
            
            $count = 0;
            foreach ($ids as $id) {
                $registration = BusinessRegistration::find($id);
                if ($registration && $registration->status === 'pending') {
                    $registration->status = 'rejected';
                    $registration->rejected_at = now();
                    $registration->rejected_by = auth()->id();
                    $registration->rejection_reason = $request->bulk_rejection_reason;
                    $registration->save();
                    $this->logActivity($id, 'rejected', 'Bulk rejected by ' . auth()->user()->name . '. Reason: ' . $request->bulk_rejection_reason);
                    $count++;
                }
            }

            DB::commit();

            return redirect()->route('admin.registrations.index')
                ->with('success', "{$count} registration(s) rejected successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk rejection failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to bulk reject registrations.');
        }
    }

    /**
     * Export registrations to CSV
     */
    public function export(Request $request)
    {
        $query = BusinessRegistration::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('application_type')) {
            $query->where('application_type', $request->application_type);
        }
        if ($request->filled('type')) {
            if ($request->type === 'individual') {
                $query->where('is_sole_trader', true);
            } elseif ($request->type === 'business') {
                $query->where('is_sole_trader', false);
            }
        }

        $registrations = $query->get();

        $filename = "registrations_" . date('Y-m-d_His') . ".csv";
        
        return response()->streamDownload(function () use ($registrations) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($handle, [
                'Reference No', 'Legal Name', 'Business Type', 'Application Type',
                'Old TIN', 'New TIN', 'Registration No', 'Status', 'Email', 'Phone',
                'Is Sole Trader', 'Created Date', 'Approved/Rejected Date', 'Submitted By'
            ]);
            
            // Add data
            foreach ($registrations as $reg) {
                fputcsv($handle, [
                    $reg->reference_number,
                    $reg->legal_name,
                    $reg->business_type,
                    $reg->application_type,
                    $reg->old_tin ?? 'N/A',
                    $reg->new_tin ?? 'N/A',
                    $reg->registration_number ?? 'N/A',
                    ucfirst($reg->status),
                    $reg->email ?? 'N/A',
                    $reg->phone ?? 'N/A',
                    $reg->is_sole_trader ? 'Yes' : 'No',
                    $reg->created_at->format('Y-m-d H:i:s'),
                    $reg->updated_at->format('Y-m-d H:i:s'),
                    $reg->user ? $reg->user->name : 'System',
                ]);
            }
            
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download attached file
     */
    public function downloadFile($registrationId, $fileId)
    {
        $registration = BusinessRegistration::findOrFail($registrationId);
        $file = BusinessRegistrationFile::where('business_registration_id', $registrationId)
            ->where('id', $fileId)
            ->firstOrFail();
        
        // Check permission
        if (!auth()->user()->hasPermission('registration.view')) {
            abort(403);
        }
        
        $filePath = Storage::disk($file->disk)->path($file->file_path);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File not found.');
        }
        
        return response()->download($filePath, $file->original_filename);
    }

    /**
     * View file in browser
     */
    public function viewFile($registrationId, $fileId)
    {
        $registration = BusinessRegistration::findOrFail($registrationId);
        $file = BusinessRegistrationFile::where('business_registration_id', $registrationId)
            ->where('id', $fileId)
            ->firstOrFail();
        
        if (!auth()->user()->hasPermission('registration.view')) {
            abort(403);
        }
        
        $filePath = Storage::disk($file->disk)->path($file->file_path);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File not found.');
        }
        
        return response()->file($filePath);
    }

    /**
     * Get registration statistics for dashboard (AJAX)
     */
    public function getStatistics(Request $request)
    {
        $stats = [
            'total' => BusinessRegistration::count(),
            'pending' => BusinessRegistration::where('status', 'pending')->count(),
            'approved' => BusinessRegistration::where('status', 'approved')->count(),
            'rejected' => BusinessRegistration::where('status', 'rejected')->count(),
            'today' => BusinessRegistration::whereDate('created_at', today())->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get activity log for a registration
     */
    private function getActivityLog($registrationId)
    {
        // If you have a registration_logs table, query it here
        // For now, return empty collection
        // You can create a business_registration_logs table to track activities
        return collect([]);
    }

    /**
     * Log activity for a registration
     */
    private function logActivity($registrationId, $action, $description)
    {
        // Create an activity log if you have a table
        // You can create a migration for business_registration_logs table
        // For now, we'll just log to Laravel log
        Log::info('Registration Activity', [
            'registration_id' => $registrationId,
            'action' => $action,
            'description' => $description,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber()
    {
        $prefix = 'REG';
        $date = date('Ymd');
        $random = strtoupper(Str::random(6));
        $reference = $prefix . $date . $random;
        
        while (BusinessRegistration::where('reference_number', $reference)->exists()) {
            $random = strtoupper(Str::random(6));
            $reference = $prefix . $date . $random;
        }
        
        return $reference;
    }

    /**
 * Display pending registrations
 */
public function pending(Request $request)
{
    $query = BusinessRegistration::with(['contactDetails', 'structuredPhones', 'files'])
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc');

    // Apply search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('legal_name', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%")
              ->orWhere('old_tin', 'like', "%{$search}%")
              ->orWhere('new_tin', 'like', "%{$search}%");
        });
    }

    $registrations = $query->paginate(15)->withQueryString();
    
    $stats = [
        'total' => BusinessRegistration::count(),
        'pending' => BusinessRegistration::where('status', 'pending')->count(),
        'approved' => BusinessRegistration::where('status', 'approved')->count(),
        'rejected' => BusinessRegistration::where('status', 'rejected')->count(),
        'total_today' => BusinessRegistration::whereDate('created_at', today())->count(),
    ];

    return view('admin.registrations.index', compact('registrations', 'stats'));
}

/**
 * Display approved registrations
 */
public function approved(Request $request)
{
    $query = BusinessRegistration::with(['contactDetails', 'structuredPhones', 'files'])
        ->where('status', 'approved')
        ->orderBy('created_at', 'desc');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('legal_name', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%")
              ->orWhere('new_tin', 'like', "%{$search}%");
        });
    }

    $registrations = $query->paginate(15)->withQueryString();
    
    $stats = [
        'total' => BusinessRegistration::count(),
        'pending' => BusinessRegistration::where('status', 'pending')->count(),
        'approved' => BusinessRegistration::where('status', 'approved')->count(),
        'rejected' => BusinessRegistration::where('status', 'rejected')->count(),
        'total_today' => BusinessRegistration::whereDate('created_at', today())->count(),
    ];

    return view('admin.registrations.index', compact('registrations', 'stats'));
}

/**
 * Display rejected registrations
 */
public function rejected(Request $request)
{
    $query = BusinessRegistration::with(['contactDetails', 'structuredPhones', 'files'])
        ->where('status', 'rejected')
        ->orderBy('created_at', 'desc');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('legal_name', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%")
              ->orWhere('old_tin', 'like', "%{$search}%");
        });
    }

    $registrations = $query->paginate(15)->withQueryString();
    
    $stats = [
        'total' => BusinessRegistration::count(),
        'pending' => BusinessRegistration::where('status', 'pending')->count(),
        'approved' => BusinessRegistration::where('status', 'approved')->count(),
        'rejected' => BusinessRegistration::where('status', 'rejected')->count(),
        'total_today' => BusinessRegistration::whereDate('created_at', today())->count(),
    ];

    return view('admin.registrations.index', compact('registrations', 'stats'));
}
}