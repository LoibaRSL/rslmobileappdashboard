<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TinRegistrationController extends TinRegistrationsController
{
    public function create(): View
    {
        return view('pages.empty');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->register($request);
    }

    public function pending(): View
    {
        return view('pages.empty');
    }

    public function approved(): View
    {
        return view('pages.empty');
    }

    public function rejected(): View
    {
        return view('pages.empty');
    }

    public function show($id): JsonResponse
    {
        return $this->getRegistration($id);
    }
}
