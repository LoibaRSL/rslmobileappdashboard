<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TINApplication;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TINAmendmentController extends Controller
{
    /**
     * Get the logged-in user's TIN record (for prefilling).
     */
    public function getMyTIN()
    {
        $user = Auth::user();

        $tin = TINApplication::where('user_id', $user->id)->latest()->first();

        if (!$tin) {
            return response()->json(['message' => 'No TIN record found for this user'], 404);
        }

        return response()->json($tin);
    }

    /**
     * Update or amend the TIN application
     */
    public function updateTIN(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'surname' => 'required|string|max:100',
            'forname' => 'required|string|max:100',
            'title' => 'required|string',
            'dateOfBirth' => 'required|date',
            'email' => 'nullable|email|max:150',
            'phoneNumber' => 'required|string|max:20',
            'countryOfRes' => 'required|string',
            'maritalStatus' => 'nullable|string',
            'mobileMoney' => 'nullable|string',
            'mobileMoneyNumber' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tin = TINApplication::where('user_id', $user->id)->latest()->first();

        if (!$tin) {
            return response()->json(['message' => 'TIN record not found'], 404);
        }

        // Store uploaded files if any
        $uploadedFiles = [];
        if ($request->hasFile('passport')) {
            $uploadedFiles['passport'] = $request->file('passport')->store('amendments/passports', 'public');
        }

        if ($request->hasFile('proofID')) {
            $uploadedFiles['proofID'] = $request->file('proofID')->store('amendments/id', 'public');
        }

        if ($request->hasFile('otherId')) {
            $uploadedFiles['otherId'] = $request->file('otherId')->store('amendments/other', 'public');
        }

        // Update data
        $tin->update([
            'regType' => 'AMENDMENT',
            'title' => $request->title,
            'surname' => $request->surname,
            'forname' => $request->forname,
            'dateOfBirth' => $request->dateOfBirth,
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
            'countryOfRes' => $request->countryOfRes,
            'maritalStatus' => $request->maritalStatus,
            'mobileMoney' => $request->mobileMoney,
            'mobileMoneyNumber' => $request->mobileMoneyNumber,
            'files' => array_merge($tin->files ?? [], $uploadedFiles),
            'status' => 'Pending Review',
        ]);

        return response()->json([
            'message' => 'TIN amendment submitted successfully.',
            'data' => $tin,
        ]);
    }
}
