<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function registrationIndividual(): View
    {
        return view('pages.empty');
    }

    public function registrationBusiness(): View
    {
        return view('pages.empty');
    }

    public function exportRegistration(): Response
    {
        return response('reference,type,status,created_at' . PHP_EOL, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="registration-report.csv"',
        ]);
    }

    public function amendmentsIndividual(): View
    {
        return view('pages.empty');
    }

    public function amendmentsBusiness(): View
    {
        return view('pages.empty');
    }

    public function amendmentsGraduation(): View
    {
        return view('pages.empty');
    }
}
