<?php

namespace App\Http\Controllers;

use App\Models\TINApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminTINController extends Controller
{
    public function index()
    {
        $applications = TINApplication::latest()->paginate(15);
        return view('admin.applications.index', compact('applications'));
    }

    public function show($id)
    {
        $application = TINApplication::findOrFail($id);
        return view('admin.applications.show', compact('application'));
    }

    public function review(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'comment' => 'nullable|string',
        ]);

        $application = TINApplication::findOrFail($id);
        $application->status = $request->status;
        $application->review_comment = $request->comment;
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = now();
        $application->save();

        if ($request->status === 'APPROVED') {
            // Call SOAP Service
            $soapXml = app('App\Http\Controllers\API\TINRegistrationController')
                ->buildSoapRequest($application->toArray());

            $soapResponse = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMINDEREG',
            ])->withBasicAuth('USER22', 'password22')
              ->withBody($soapXml, 'text/xml')
              ->post('http://uatpsrmap02.lra.org.ls:6500/ouaf/XAIApp/xaiserver/CMINDEREG');

            if ($soapResponse->failed()) {
                Log::error("SOAP Error", ['response' => $soapResponse->body()]);
                return back()->withErrors("SOAP registration failed.");
            }

            $tin = app('App\Http\Controllers\API\TINRegistrationController')
                ->extractTINFromSOAP($soapResponse->body());

            $application->generated_tin = $tin;
            $application->save();
        }

        return redirect()->route('admin.applications.index')->with('success', 'Application reviewed successfully.');
    }
}

