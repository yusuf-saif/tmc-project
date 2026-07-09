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

        if ($user && $user->hasAnyRole(['super_admin', 'admin'])) {
            // Admin: can download any receipt
        } else {
            // Member: can only download their own receipt
            abort_unless($memberProfile->user_id === optional($user)->id, 404);
        }

        if (! $memberProfile->payment_proof_path) {
            abort(404, 'No receipt found');
        }

        if (! Storage::disk('r2')->exists($memberProfile->payment_proof_path)) {
            abort(404, 'Receipt file not found');
        }

        return Storage::disk('r2')->download($memberProfile->payment_proof_path);
    }
}
