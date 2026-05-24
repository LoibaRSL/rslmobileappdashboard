<?php

namespace App\Http\Controllers\ds;

use App\Http\Controllers\Controller;
use App\Models\TinRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TinRegistrationDSController extends Controller
{
    /**
     * Get all registrations for DataTable
     */
    public function getAllRegistrations(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Check if user has permission to view registrations
        if (!$user->hasPermission('registration.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view registrations'
            ], 403);
        }
        
        $query = TinRegistration::query();
        
        // Digital Services can see all registrations
        // Other roles might see only approved or based on their permissions
        if (!$user->hasPermission('registration.approve') && !$user->hasRole('admin')) {
            // Non-approvers might only see approved registrations or their assigned ones
            $query->where('status', 'APPROVED');
        }
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                  ->orWhere('tin', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('forenames', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('assigned_to') && $request->assigned_to !== '') {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        $total = $query->count();
        
        // Apply pagination for DataTables
        $start = $request->get('start', 0);
        $length = $request->get('length', 25);
        $orderColumn = $request->get('order')[0]['column'] ?? 7;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';
        
        $columns = ['id', 'ref', 'tin', 'surname', 'email', 'assigned_to', 'status', 'created_at'];
        $orderBy = $columns[$orderColumn] ?? 'created_at';
        
        $registrations = $query->orderBy($orderBy, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();
        
        $data = $registrations->map(function($reg) use ($user) {
            return [
                'id' => $reg->id,
                'ref' => $reg->ref,
                'tin' => $reg->tin ?? 'N/A',
                'full_name' => $this->getFullName($reg),
                'email' => $reg->email,
                'assigned_to' => $this->getAssignedToName($reg),
                'status' => $reg->status,
                'submitted_at' => $reg->created_at?->format('Y-m-d H:i:s'),
                'can_approve' => $user->hasPermission('registration.approve'),
                'can_reject' => $user->hasPermission('registration.reject'),
            ];
        });
        
        return response()->json([
            'data' => $data,
            'recordsTotal' => TinRegistration::count(),
            'recordsFiltered' => $total,
            'draw' => intval($request->get('draw', 1)),
        ]);
    }
    
    /**
     * Get single registration details
     */
    public function showRegistration($id): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view registration details'
            ], 403);
        }
        
        $registration = TinRegistration::findOrFail($id);
        
        return response()->json([
            'registration' => [
                'id' => $registration->id,
                'ref' => $registration->ref,
                'tin' => $registration->tin ?? 'Not assigned',
                'title' => $registration->title,
                'surname' => $registration->surname,
                'forenames' => $registration->forenames,
                'full_name' => $this->getFullName($registration),
                'maiden_name' => $registration->maiden_name ?? 'N/A',
                'date_of_birth' => $registration->date_of_birth,
                'email' => $registration->email,
                'phone_number' => $registration->phone_number ?? 'N/A',
                'marital_status' => $this->formatMaritalStatus($registration->marital_status),
                'spouse_name' => $registration->spouse_name ?? 'N/A',
                'spouse_tin' => $registration->spouse_tin ?? 'N/A',
                'status' => $registration->status,
                'remarks' => $registration->remarks ?? 'N/A',
                'submitted_at' => $registration->created_at?->format('Y-m-d H:i:s'),
                'can_approve' => $user->hasPermission('registration.approve'),
                'can_reject' => $user->hasPermission('registration.reject'),
                'physical_address' => $this->getPhysicalAddress($registration),
                'postal_address' => $this->getPostalAddress($registration),
                'bank_details' => $this->getBankDetails($registration),
                'files' => $this->getFiles($registration),
                'registration_type' => $registration->registration_type,
                'receive_date' => $registration->receive_date,
                'effective_date' => $registration->effective_date,
                'country_of_birth' => $registration->country_of_birth,
                'country_of_citizenship' => $registration->country_of_citizenship,
                'country_of_residence' => $registration->country_of_residence,
            ]
        ]);
    }
    
    /**
     * Approve a registration
     */
    public function approveRegistration($id, Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve registrations'
            ], 403);
        }
        
        $registration = TinRegistration::findOrFail($id);
        
        $registration->update([
            'status' => 'APPROVED',
            'remarks' => $request->remarks ?? 'Registration approved by administrator. Registration submitted successfully',
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration approved successfully'
        ]);
    }
    
    /**
     * Reject a registration
     */
    public function rejectRegistration($id, Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.reject')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject registrations'
            ], 403);
        }
        
        $request->validate([
            'remarks' => 'required|string|min:5'
        ]);
        
        $registration = TinRegistration::findOrFail($id);
        
        $registration->update([
            'status' => 'REJECTED',
            'remarks' => 'Registration rejected. Reason: ' . $request->remarks,
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration rejected successfully'
        ]);
    }
    
    /**
     * Assign registration to a DS user
     */
    public function assignRegistration($id, Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.approve') && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign registrations'
            ], 403);
        }
        
        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);
        
        $registration = TinRegistration::findOrFail($id);
        
        $registration->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'UNDER_REVIEW',
            'updated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration assigned successfully'
        ]);
    }
    
    /**
     * Export registrations to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = TinRegistration::query();
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                  ->orWhere('tin', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('forenames', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('assigned_to') && $request->assigned_to !== '') {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        $registrations = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'tin_registrations_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        $callback = function() use ($registrations) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Reference', 'TIN', 'Title', 'Surname', 'Forenames', 'Maiden Name',
                'Email', 'Phone', 'Date of Birth', 'Marital Status', 'Spouse Name', 
                'Physical Address', 'Postal Address', 'Status', 'Submitted Date', 'Remarks'
            ]);
            
            foreach ($registrations as $reg) {
                fputcsv($file, [
                    $reg->id,
                    $reg->ref,
                    $reg->tin ?? 'N/A',
                    $reg->title,
                    $reg->surname,
                    $reg->forenames,
                    $reg->maiden_name ?? 'N/A',
                    $reg->email,
                    $reg->phone_number ?? 'N/A',
                    $reg->date_of_birth,
                    $this->formatMaritalStatus($reg->marital_status),
                    $reg->spouse_name ?? 'N/A',
                    $this->getPhysicalAddress($reg),
                    $this->getPostalAddress($reg),
                    $reg->status,
                    $reg->created_at?->format('Y-m-d H:i:s'),
                    $reg->remarks ?? 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get DS users for filter (users with digital_services or admin role)
     */
    public function getDsUsers(): JsonResponse
    {
        $users = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['digital_services', 'admin']);
        })->get();
        
        return response()->json([
            'success' => true,
            'users' => $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
        ]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->hasPermission('registration.dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $stats = [
            'total' => TinRegistration::count(),
            'pending' => TinRegistration::where('status', 'PENDING')->count(),
            'approved' => TinRegistration::where('status', 'APPROVED')->count(),
            'rejected' => TinRegistration::where('status', 'REJECTED')->count(),
            'under_review' => TinRegistration::where('status', 'UNDER_REVIEW')->count(),
            'today' => TinRegistration::whereDate('created_at', today())->count(),
            'this_week' => TinRegistration::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => TinRegistration::whereMonth('created_at', now()->month)->count(),
        ];
        
        // Recent registrations
        $recent = TinRegistration::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($reg) => [
                'id' => $reg->id,
                'ref' => $reg->ref,
                'full_name' => $this->getFullName($reg),
                'status' => $reg->status,
                'submitted_at' => $reg->created_at?->format('Y-m-d H:i:s'),
            ]);
        
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'recent' => $recent,
        ]);
    }
    
    // ========== Helper Methods ==========
    
    private function getFullName($registration): string
    {
        return trim($registration->title . ' ' . $registration->forenames . ' ' . $registration->surname);
    }
    
    private function getAssignedToName($registration): string
    {
        if (empty($registration->assigned_to)) {
            return 'Unassigned';
        }
        
        $user = \App\Models\User::find($registration->assigned_to);
        return $user?->name ?? 'Unknown';
    }
    
    private function getPhysicalAddress($registration): string
    {
        $parts = array_filter([
            $registration->street_name,
            $registration->village,
            $registration->town,
            $registration->physical_district,
        ]);
        
        return implode(', ', $parts) ?: 'Not provided';
    }
    
    private function getPostalAddress($registration): string
    {
        $parts = array_filter([
            $registration->post_address1,
            $registration->post_type && $registration->post_number ? 
                $registration->post_type . ' ' . $registration->post_number : null,
            $registration->post_city,
        ]);
        
        return implode(', ', $parts) ?: 'Not provided';
    }
    
    private function getBankDetails($registration): array
    {
        return [
            'bank_name' => $registration->bank_name ?? 'Not provided',
            'bank_country' => $registration->bank_country ?? 'Not provided',
            'mobile_money_type' => $registration->mobile_money_type ?? null,
            'mobile_money_number' => $registration->mobile_money_number ?? null,
        ];
    }
    
    private function getFiles($registration): array
    {
        $files = [];
        $fileColumns = [
            'lesotho_id_path' => 'Lesotho National ID',
            'passport_path' => 'Passport',
            'other_id_path' => 'Other Identification',
            'foreign_id_path' => 'Foreign ID',
            'antenuptial_path' => 'Antenuptial Agreement',
        ];
        
        foreach ($fileColumns as $column => $label) {
            if (!empty($registration->$column)) {
                $files[] = [
                    'file_path' => $registration->$column,
                    'file_name' => $label,
                    'file_type' => pathinfo($registration->$column, PATHINFO_EXTENSION),
                ];
            }
        }
        
        return $files;
    }
    
    private function formatMaritalStatus($status): string
    {
        $statuses = [
            'SING' => 'Single',
            'MARR' => 'Married',
            'DIVO' => 'Divorced',
            'SEPA' => 'Separated',
            'WIDO' => 'Widowed',
        ];
        
        return $statuses[$status] ?? $status ?? 'Not specified';
    }
}