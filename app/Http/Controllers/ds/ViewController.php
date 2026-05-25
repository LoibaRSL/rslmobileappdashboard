<?php

namespace App\Http\Controllers\DS;

use App\Http\Controllers\Controller;
use App\Models\BusinessAmendment;
use App\Models\BusinessRegistration;
use App\Models\TinRegistration;
use App\Models\User;

class ViewController extends Controller
{
    public function dashboard()
    {
        // Get counts for dashboard stats
        $stats = [
            'total' => TinRegistration::count() + BusinessRegistration::count(),
            'pending' => TinRegistration::where('status', 'PENDING')->count() + BusinessRegistration::where('status', 'submitted')->count() + BusinessAmendment::where('status', 'submitted')->count(),
            'approved' => TinRegistration::where('status', 'APPROVED')->count() + BusinessRegistration::where('status', 'approved')->count() + BusinessAmendment::where('status', 'approved')->count(),
            'rejected' => TinRegistration::where('status', 'REJECTED')->count() + BusinessRegistration::where('status', 'rejected')->count() + BusinessAmendment::where('status', 'rejected')->count(),
            'under_review' => TinRegistration::where('status', 'UNDER_REVIEW')->count() + BusinessRegistration::where('status', 'under_review')->count() + BusinessAmendment::where('status', 'under_review')->count(),
        ];
        
        $recentRegistrations = TinRegistration::orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('ds.dashboard', compact('stats', 'recentRegistrations'));
    }
    
    public function allRegistrations()
    {
        return $this->registrationList('individual', 'all');
    }
    
    public function unassigned()
    {
        return $this->registrationList('individual', 'unassigned');
    }
    
    public function myAssignments()
    {
        return $this->registrationList('individual', 'my-assignments');
    }
    
    public function approved()
    {
        return $this->registrationList('individual', 'approved');
    }
    
    public function rejected()
    {
        return $this->registrationList('individual', 'rejected');
    }

    public function allBusinessRegistrations()
    {
        return $this->registrationList('business', 'all');
    }

    public function unassignedBusiness()
    {
        return $this->registrationList('business', 'unassigned');
    }

    public function myBusinessAssignments()
    {
        return $this->registrationList('business', 'my-assignments');
    }

    public function approvedBusiness()
    {
        return $this->registrationList('business', 'approved');
    }

    public function rejectedBusiness()
    {
        return $this->registrationList('business', 'rejected');
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

    public function failedSoap()
    {
        return view('ds.failed-soap');
    }

    public function sla()
    {
        return view('ds.sla');
    }

    private function registrationList(string $type, string $scope)
    {
        $typeLabel = $type === 'business' ? 'Business' : 'Individual';
        $scopeLabels = [
            'all' => 'All Submissions',
            'unassigned' => 'Pending (Unassigned)',
            'my-assignments' => 'My Assignments',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        $apiRoutes = [
            'individual' => [
                'all' => 'ds.api.registrations.all',
                'unassigned' => 'ds.api.registrations.unassigned',
                'my-assignments' => 'ds.api.registrations.my-assignments',
                'approved' => 'ds.api.registrations.approved',
                'rejected' => 'ds.api.registrations.rejected',
            ],
            'business' => [
                'all' => 'ds.api.business-registrations.all',
                'unassigned' => 'ds.api.business-registrations.unassigned',
                'my-assignments' => 'ds.api.business-registrations.my-assignments',
                'approved' => 'ds.api.business-registrations.approved',
                'rejected' => 'ds.api.business-registrations.rejected',
            ],
        ];

        return view('ds.registration-list', [
            'registrationType' => $type,
            'registrationTypeLabel' => $typeLabel,
            'scope' => $scope,
            'scopeLabel' => $scopeLabels[$scope],
            'pageTitle' => $typeLabel . ' Registrations - ' . $scopeLabels[$scope],
            'apiUrl' => route($apiRoutes[$type][$scope]),
            'detailBaseUrl' => url($type === 'business' ? 'api/ds/business-registrations' : 'api/ds/registrations'),
            'exportType' => $type,
        ]);
    }
}
