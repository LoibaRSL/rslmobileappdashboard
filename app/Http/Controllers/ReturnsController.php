<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReturnsController extends Controller
{
    public function residentTax(): View
    {
        return view('pages.empty');
    }

    public function nonResidentTax(): View
    {
        return view('pages.empty');
    }

    public function vat(): View
    {
        return view('pages.empty');
    }
}
