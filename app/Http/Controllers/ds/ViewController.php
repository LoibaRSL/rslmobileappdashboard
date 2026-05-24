<?php

namespace App\Http\Controllers\DS;

use App\Http\Controllers\Controller;
use App\Models\TinRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewController extends Controller
{
    public function dashboard()
    {
        // Get counts for dashboard stats
        $stats = [
            'total' => TinRegistration::count(),
            'pending' => TinRegistration::where('status', 'PENDING')->count(),
            'approved' => TinRegistration::where('status', 'APPROVED')->count(),
            'rejected' => TinRegistration::where('status', 'REJECTED')->count(),
            'under_review' => TinRegistration::where('status', 'UNDER_REVIEW')->count(),
        ];
        
        $recentRegistrations = TinRegistration::orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('ds.dashboard', compact('stats', 'recentRegistrations'));
    }
    
    public function allRegistrations()
    {
        return view('ds.all-registrations');
    }
    
    public function unassigned()
    {
        // In your DB, you don't have assignment tracking yet
        // For now, show all pending registrations or add a 'assigned_to' column
        return view('ds.unassigned');
    }
    
    public function myAssignments()
    {
        // Get assignments for current logged-in user
        $userId = auth()->id();
        $registrations = TinRegistration::where('assigned_to', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        
        return view('ds.my-assignments', compact('registrations'));
    }
    
    public function approved()
    {
        $registrations = TinRegistration::where('status', 'APPROVED')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        
        return view('ds.approved', compact('registrations'));
    }
    
    public function rejected()
    {
        $registrations = TinRegistration::where('status', 'REJECTED')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        
        return view('ds.rejected', compact('registrations'));
    }
    
    public function users()
    {
        $users = User::where('role', 'ds') // or whatever role you use
            ->orWhere('role', 'admin')
            ->get();
        
        return view('ds.users.index', compact('users'));
    }
    
    public function createUser()
    {
        return view('ds.users.create');
    }
    
    public function reports()
    {
        return view('ds.reports');
    }
}