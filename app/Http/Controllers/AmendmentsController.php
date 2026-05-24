<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AmendmentsController extends Controller
{
    public function individual(): View
    {
        return view('pages.empty');
    }

    public function business(): View
    {
        return view('pages.empty');
    }

    public function graduation(): View
    {
        return view('pages.empty');
    }
}
