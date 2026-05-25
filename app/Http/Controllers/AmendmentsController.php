<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AmendmentsController extends Controller
{
    public function individual(): View
    {
        return view('ds.registration-list', [
            'registrationType' => 'individual',
            'registrationTypeLabel' => 'Individual Amendment',
            'scope' => 'all',
            'scopeLabel' => 'All Submissions',
            'pageTitle' => 'Individual Amendments',
            'apiUrl' => route('ds.api.amendments.individual'),
            'detailBaseUrl' => url('api/ds/registrations'),
            'exportType' => 'individual_amendment',
        ]);
    }

    public function business(): View
    {
        return view('ds.registration-list', [
            'registrationType' => 'business',
            'registrationTypeLabel' => 'Business Amendment',
            'scope' => 'all',
            'scopeLabel' => 'All Submissions',
            'pageTitle' => 'Business Amendments',
            'apiUrl' => route('ds.api.amendments.business'),
            'detailBaseUrl' => url('api/ds/business-amendments'),
            'exportType' => 'business_amendment',
        ]);
    }

    public function graduation(): View
    {
        return view('pages.empty');
    }
}
