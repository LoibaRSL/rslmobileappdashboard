<?php

namespace App\Http\Controllers\DS;

use App\Http\Controllers\Controller;
use App\Models\TinRegistration;
use App\Models\User;
use App\Models\TinAssignmentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TinRegistrationDSController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $stats = [
            'total_pending' => TinRegistration::where('status', 'PENDING')->count(),
            'total_approved' => TinRegistration::where('status', 'APPROVED')->count(),
            'total_rejected' => TinRegistration::where('status', 'REJECTED')->count(),
            'total_under_review' => TinRegistration::where('status', 'UNDER_REVIEW')->count(),
            
            'assigned_to_me' => TinRegistration::where('assigned_to_user_id', $user->id)
                ->whereIn('status', ['PENDING', 'UNDER_REVIEW'])
                ->count(),
            
            'my_approved' => TinRegistration::where('assigned_to_user_id', $user->id)
                ->where('status', 'APPROVED')
                ->count(),
            
            'my_rejected' => TinRegistration::where('assigned_to_user_id', $user->id)
                ->where('status', 'REJECTED')
                ->count(),
            
            'unassigned' => TinRegistration::whereNull('assigned_to_user_id')
                ->where('status', 'PENDING')
                ->count(),
        ];
        
        // Add admin-only stats
        if ($user->isAdmin()) {
            $stats['total_ds_users'] = User::whereHas('roles', function($q) {
                $q->where('name', 'digital_services');
            })->count();
            $stats['total_registrations'] = TinRegistration::count();
            $stats['approval_rate'] = $this->calculateApprovalRate();
        }
        
        return response()->json(['success' => true, 'stats' => $stats]);
    }

    public function getApproved(Request $request): JsonResponse
{
    $perPage = $request->integer('per_page', 15);
    
    $registrations = TinRegistration::with(['assignedTo', 'employers', 'files'])
        ->where('status', 'APPROVED')
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $this->formatRegistrationList($registrations)
    ]);
}

public function getRejected(Request $request): JsonResponse
{
    $perPage = $request->integer('per_page', 15);
    
    $registrations = TinRegistration::with(['assignedTo', 'employers', 'files'])
        ->where('status', 'REJECTED')
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $this->formatRegistrationList($registrations)
    ]);
}
    
    /**
     * Get unassigned registrations
     */
    public function getUnassigned(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        
        $registrations = TinRegistration::with(['employers', 'files'])
            ->unassigned()
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $this->formatRegistrationList($registrations)
        ]);
    }
    
    /**
     * Get registrations assigned to current user
     */
    public function getMyAssignments(Request $request): JsonResponse
    {
        $user = auth()->user();
        $status = $request->get('status');
        
        $query = TinRegistration::with(['employers', 'files'])
            ->assignedToUser($user->id);
        
        if ($status) {
            $query->where('status', strtoupper($status));
        }
        
        $perPage = $request->integer('per_page', 15);
        $registrations = $query->orderBy('assigned_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $this->formatRegistrationList($registrations)
        ]);
    }
    
    /**
     * Get all registrations (admin only)
     */
    public function getAllRegistrations(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }
        
        $query = TinRegistration::with(['assignedTo', 'employers', 'files']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', strtoupper($request->status));
        }
        
        if ($request->has('assigned_to')) {
            $query->where('assigned_to_user_id', $request->assigned_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tin', 'LIKE', "%{$search}%")
                  ->orWhere('ref', 'LIKE', "%{$search}%")
                  ->orWhere('surname', 'LIKE', "%{$search}%")
                  ->orWhere('forenames', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        $perPage = $request->integer('per_page', 15);
        $registrations = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $this->formatRegistrationList($registrations)
        ]);
    }
    
    /**
     * Get single registration details
     */
    public function show(int $id): JsonResponse
    {
        $registration = TinRegistration::with([
            'assignedTo',
            'employers',
            'files',
            'phoneDetails',
            'bankingDetails',
            'mobileMoneyDetails',
            'assignmentHistory.assignedBy',
            'assignmentHistory.assignedTo'
        ])->findOrFail($id);
        
        $user = auth()->user();
        
        // Check authorization
        if (!$user->isAdmin() && 
            $registration->assigned_to_user_id !== null && 
            $registration->assigned_to_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this registration'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'registration' => $this->formatRegistrationDetail($registration)
        ]);
    }
    
    /**
     * Assign registration to self
     */
    public function assignToSelf(int $id): JsonResponse
    {
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $registration = TinRegistration::findOrFail($id);
            
            if (!$registration->canBeAssigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This registration cannot be assigned (already assigned or not pending)'
                ], 422);
            }
            
            $registration->update([
                'assigned_to_user_id' => $user->id,
                'assigned_at' => now(),
                'status' => 'UNDER_REVIEW'
            ]);
            
            // Create history record
            TinAssignmentHistory::create([
                'tin_registration_id' => $registration->id,
                'assigned_by' => $user->id,
                'assigned_to' => $user->id,
                'action' => 'assign',
                'notes' => 'Assigned to self for review'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Registration assigned to you successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign registration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Assign registration to another DS user (admin only)
     */
    public function assignToUser(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500'
        ]);
        
        $currentUser = auth()->user();
        
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can assign to other users'
            ], 403);
        }
        
        DB::beginTransaction();
        
        try {
            $registration = TinRegistration::findOrFail($id);
            $targetUser = User::findOrFail($request->user_id);
            
            if (!$targetUser->isDigitalServices()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only assign to Digital Services users'
                ], 422);
            }
            
            $oldAssignee = $registration->assigned_to_user_id;
            
            $registration->update([
                'assigned_to_user_id' => $targetUser->id,
                'assigned_at' => now(),
                'status' => 'UNDER_REVIEW'
            ]);
            
            TinAssignmentHistory::create([
                'tin_registration_id' => $registration->id,
                'assigned_by' => $currentUser->id,
                'assigned_to' => $targetUser->id,
                'action' => $oldAssignee ? 'reassign' : 'assign',
                'previous_assigned_to' => $oldAssignee,
                'notes' => $request->notes ?? "Assigned by {$currentUser->name}"
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Registration assigned to {$targetUser->name} successfully"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign registration'
            ], 500);
        }
    }
    
    /**
     * Approve registration
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tin' => 'required|string|unique:tin_registrations,tin,' . $id,
            'remarks' => 'nullable|string'
        ]);
        
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $registration = TinRegistration::findOrFail($id);
            
            if (!$user->isAdmin() && $registration->assigned_to_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only approve registrations assigned to you'
                ], 403);
            }
            
            $registration->update([
                'tin' => $request->tin,
                'status' => 'APPROVED',
                'remarks' => $request->remarks,
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Registration approved successfully',
                'tin' => $request->tin
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve registration'
            ], 500);
        }
    }
    
    /**
     * Reject registration
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'remarks' => 'required|string|min:10'
        ]);
        
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $registration = TinRegistration::findOrFail($id);
            
            if (!$user->isAdmin() && $registration->assigned_to_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only reject registrations assigned to you'
                ], 403);
            }
            
            $registration->update([
                'status' => 'REJECTED',
                'remarks' => $request->remarks,
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Registration rejected'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject registration'
            ], 500);
        }
    }
    
    /**
     * Get all DS users for dropdown (admin only)
     */
    public function getDSUsers(): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->isAdmin() && !$user->isDigitalServices()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $users = User::whereHas('roles', function($query) {
                $query->where('name', 'digital_services');
            })
            ->select('id', 'name', 'email', 'department')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
    
    /**
     * Get assignment history for a registration
     */
    public function getAssignmentHistory(int $id): JsonResponse
    {
        $history = TinAssignmentHistory::with(['assignedBy', 'assignedTo'])
            ->where('tin_registration_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }
    
    /**
     * Export registrations to CSV
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();
        
        $query = TinRegistration::with(['assignedTo']);
        
        if (!$user->isAdmin()) {
            $query->assignedToUser($user->id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $registrations = $query->get();
        
        $filename = "tin_registrations_" . now()->format('Ymd_His') . ".csv";
        
        return response()->streamDownload(function() use ($registrations) {
            $handle = fopen('php://output', 'w');
            
            // Headers
            fputcsv($handle, [
                'ID', 'Reference', 'TIN', 'Full Name', 'Email', 'Status', 
                'Assigned To', 'Submitted Date', 'Remarks'
            ]);
            
            foreach ($registrations as $reg) {
                fputcsv($handle, [
                    $reg->id,
                    $reg->ref,
                    $reg->tin ?? 'Not assigned',
                    $reg->forenames . ' ' . $reg->surname,
                    $reg->email,
                    $reg->status,
                    $reg->assignedTo?->name ?? 'Unassigned',
                    $reg->created_at->format('Y-m-d H:i'),
                    $reg->remarks
                ]);
            }
            
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
    
    // Private helper methods
    private function formatRegistrationList($registrations): array
    {
        return $registrations->map(function($reg) {
            return [
                'id' => $reg->id,
                'ref' => $reg->ref,
                'tin' => $reg->tin,
                'full_name' => $reg->forenames . ' ' . $reg->surname,
                'email' => $reg->email,
                'status' => $reg->status,
                'submitted_at' => $reg->created_at->format('Y-m-d H:i:s'),
                'assigned_to' => $reg->assignedTo?->name ?? 'Unassigned',
                'employers_count' => $reg->employers?->count() ?? 0,
                'files_count' => $reg->files?->count() ?? 0
            ];
        })->toArray();
    }
    
    private function formatRegistrationDetail($registration): array
    {
        return [
            'id' => $registration->id,
            'ref' => $registration->ref,
            'tin' => $registration->tin,
            'document_locator' => $registration->document_locator,
            'receive_date' => $registration->receive_date,
            'registration_type' => $registration->registration_type,
            'title' => $registration->title,
            'surname' => $registration->surname,
            'forenames' => $registration->forenames,
            'maiden_name' => $registration->maiden_name,
            'date_of_birth' => $registration->date_of_birth,
            'email' => $registration->email,
            'phone_details' => $registration->phoneDetails,
            'employers' => $registration->employers,
            'banking_details' => $registration->bankingDetails,
            'mobile_money_details' => $registration->mobileMoneyDetails,
            'status' => $registration->status,
            'remarks' => $registration->remarks,
            'assigned_to' => $registration->assignedTo?->name,
            'assigned_at' => $registration->assigned_at,
            'submitted_at' => $registration->created_at->format('Y-m-d H:i:s'),
            'files' => $registration->files->map(function($file) {
                return [
                    'type' => $file->file_type,
                    'name' => $file->file_name,
                    'url' => Storage::url($file->file_path)
                ];
            }),
            'assignment_history' => $registration->assignmentHistory->map(function($history) {
                return [
                    'action' => $history->action,
                    'assigned_by' => $history->assignedBy?->name,
                    'assigned_to' => $history->assignedTo?->name,
                    'notes' => $history->notes,
                    'created_at' => $history->created_at->format('Y-m-d H:i:s')
                ];
            })
        ];
    }
    
    private function calculateApprovalRate(): float
    {
        $total = TinRegistration::whereIn('status', ['APPROVED', 'REJECTED'])->count();
        if ($total === 0) return 0;
        
        $approved = TinRegistration::where('status', 'APPROVED')->count();
        return round(($approved / $total) * 100, 2);
    }
}