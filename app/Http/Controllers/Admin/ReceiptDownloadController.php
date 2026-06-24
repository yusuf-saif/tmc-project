<?php

namespace App\Http\Controllers\Admin;

use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptDownloadController
{
    public function __invoke(Request $request, MemberProfile $memberProfile): StreamedResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        if (! $memberProfile->payment_proof_path) {
            abort(404, 'No receipt found');
        }

        if (! Storage::disk('public')->exists($memberProfile->payment_proof_path)) {
            abort(404, 'Receipt file not found');
        }

        return Storage::disk('public')->download($memberProfile->payment_proof_path);
    }
}
