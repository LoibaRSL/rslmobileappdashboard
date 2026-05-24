<?php

namespace App\Http\Controllers\Api\DS;

use App\Http\Controllers\Controller;
use App\Models\TinRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationApiController extends Controller
{
    /**
     * Get all registrations for DataTable
     */
    public function allRegistrations(Request $request)
    {
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
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // For assignment filter - you may need to add assigned_to column
        // For now, this is a placeholder
        if ($request->filled('assigned_to') && $request->assigned_to !== '') {
            // $query->where('assigned_to', $request->assigned_to);
        }
        
        $total = $query->count();
        
        // Apply pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 25);
        
        $registrations = $query->orderBy('created_at', 'desc')
            ->skip($start)
            ->take($length)
            ->get();
        
        $data = $registrations->map(function($reg) {
            return [
                'id' => $reg->id,
                'ref' => $reg->ref,
                'tin' => $reg->tin,
                'full_name' => $this->getFullName($reg),
                'email' => $reg->email,
                'assigned_to' => $this->getAssignedToName($reg),
                'status' => $reg->status,
                'submitted_at' => $reg->created_at ? $reg->created_at->format('Y-m-d H:i:s') : '',
                'created_at' => $reg->created_at,
            ];
        });
        
        return response()->json([
            'data' => $data,
            'recordsTotal' => TinRegistration::count(),
            'recordsFiltered' => $total,
        ]);
    }
    
    /**
     * Get single registration details
     */
    public function showRegistration($id)
    {
        $registration = TinRegistration::findOrFail($id);
        
        // Get employers - if you have an employers table
        $employers = $this->getEmployers($id);
        
        // Get files/documents
        $files = $this->getFiles($registration);
        
        return response()->json([
            'registration' => [
                'id' => $registration->id,
                'ref' => $registration->ref,
                'tin' => $registration->tin,
                'title' => $registration->title,
                'surname' => $registration->surname,
                'forenames' => $registration->forenames,
                'full_name' => $this->getFullName($registration),
                'maiden_name' => $registration->maiden_name,
                'date_of_birth' => $registration->date_of_birth,
                'email' => $registration->email,
                'marital_status' => $registration->marital_status,
                'status' => $registration->status,
                'remarks' => $registration->remarks,
                'submitted_at' => $registration->created_at ? $registration->created_at->format('Y-m-d H:i:s') : '',
                'assigned_to' => $this->getAssignedToName($registration),
                'employers' => $employers,
                'files' => $files,
                // Additional fields
                'phone_number' => $registration->phone_number,
                'physical_address' => $this->getPhysicalAddress($registration),
                'postal_address' => $this->getPostalAddress($registration),
                'spouse_name' => $registration->spouse_name,
                'spouse_tin' => $registration->spouse_tin,
            ]
        ]);
    }
    
    /**
     * Export registrations to CSV
     */
    public function export(Request $request)
    {
        $query = TinRegistration::query();
        
        // Apply same filters as above
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
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $registrations = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'registrations_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() use ($registrations) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID', 'Reference', 'TIN', 'Title', 'Surname', 'Forenames', 
                'Email', 'Status', 'Date of Birth', 'Submitted Date', 'Remarks'
            ]);
            
            foreach ($registrations as $reg) {
                fputcsv($file, [
                    $reg->id,
                    $reg->ref,
                    $reg->tin,
                    $reg->title,
                    $reg->surname,
                    $reg->forenames,
                    $reg->email,
                    $reg->status,
                    $reg->date_of_birth,
                    $reg->created_at ? $reg->created_at->format('Y-m-d H:i:s') : '',
                    $reg->remarks,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get DS users list for filter
     */
    public function getDsUsers()
    {
        // Return users who are DS officers
        $users = User::where('role', 'ds')
            ->orWhere('role', 'admin')
            ->select('id', 'name')
            ->get();
        
        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
    
    // Helper methods
    private function getFullName($registration)
    {
        return trim($registration->title . ' ' . $registration->forenames . ' ' . $registration->surname);
    }
    
    private function getAssignedToName($registration)
    {
        // If you have an assigned_to column, implement this
        // For now, return null or 'Unassigned'
        return 'Unassigned';
    }
    
    private function getEmployers($registrationId)
    {
        // If you have an employers table linked to registrations
        // For now, return empty array
        // You can extract employer info from other fields if available
        return [];
    }
    
    private function getFiles($registration)
    {
        $files = [];
        
        // Check various file path columns
        $fileColumns = [
            'lesotho_id_path' => 'Lesotho ID',
            'passport_path' => 'Passport',
            'other_id_path' => 'Other ID',
            'foreign_id_path' => 'Foreign ID',
            'antenuptial_path' => 'Antenuptial',
        ];
        
        foreach ($fileColumns as $column => $label) {
            if (!empty($registration->$column)) {
                $files[] = [
                    'file_path' => $registration->$column,
                    'file_name' => $label . ' - ' . basename($registration->$column),
                ];
            }
        }
        
        return $files;
    }
    
    private function getPhysicalAddress($registration)
    {
        $parts = [];
        if ($registration->street_name) $parts[] = $registration->street_name;
        if ($registration->village) $parts[] = $registration->village;
        if ($registration->town) $parts[] = $registration->town;
        if ($registration->physical_district) $parts[] = $registration->physical_district;
        
        return implode(', ', $parts) ?: 'N/A';
    }
    
    private function getPostalAddress($registration)
    {
        $parts = [];
        if ($registration->post_address1) $parts[] = $registration->post_address1;
        if ($registration->post_number && $registration->post_type) {
            $parts[] = $registration->post_type . ' ' . $registration->post_number;
        }
        if ($registration->post_city) $parts[] = $registration->post_city;
        
        return implode(', ', $parts) ?: 'N/A';
    }
}