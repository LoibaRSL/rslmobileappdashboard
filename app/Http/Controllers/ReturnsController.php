<?php

namespace App\Http\Controllers;

use App\Models\RiitReturn;
use App\Models\RiitReturnAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReturnsController extends Controller
{
    public function residentTax(): View
    {
        $returns = RiitReturn::withCount('attachments')
            ->latest()
            ->paginate(25);

        $stats = [
            'total' => RiitReturn::count(),
            'soap_success' => RiitReturn::where('soap_status', 'success')->count(),
            'soap_failed' => RiitReturn::where('soap_status', 'failed')->count(),
            'pending' => RiitReturn::where('soap_status', 'pending')->count(),
        ];

        return view('returns.riit-index', compact('returns', 'stats'));
    }

    public function showResidentTax(RiitReturn $return): View
    {
        $return->load('attachments');

        return view('returns.riit-show', ['return' => $return]);
    }

    public function showResidentTaxAttachment(RiitReturn $return, RiitReturnAttachment $attachment)
    {
        if ($attachment->riit_return_id !== $return->id) {
            abort(404);
        }

        $disk = $attachment->disk ?? 'public';
        if (!Storage::disk($disk)->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found');
        }

        return response()->file(Storage::disk($disk)->path($attachment->file_path), [
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_filename) . '"',
        ]);
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
