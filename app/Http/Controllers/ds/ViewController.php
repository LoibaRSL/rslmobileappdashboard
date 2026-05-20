<?php

namespace App\Http\Controllers\DS;

use App\Http\Controllers\Controller;

class ViewController extends Controller
{
    public function dashboard()
    {
        return view('ds.dashboard');
    }
    
    public function allRegistrations()
    {
        return view('ds.all-registrations');
    }
    
    public function unassigned()
    {
        return view('ds.unassigned');
    }
    
    public function myAssignments()
    {
        return view('ds.my-assignments');
    }
    
    public function approved()
    {
        return view('ds.approved');
    }
    
    public function rejected()
    {
        return view('ds.rejected');
    }
    
    public function users()
    {
        return view('ds.users.index');
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