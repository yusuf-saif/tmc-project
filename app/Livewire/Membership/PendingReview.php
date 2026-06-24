<?php

namespace App\Livewire\Membership;

use App\Models\MemberProfile;
use Livewire\Component;

class PendingReview extends Component
{
    public function mount()
    {
        $user = auth()->user();
        $profile = $user->memberProfile ?? $user->profile;

        if (! $profile) {
            return redirect()->route('membership.signup');
        }

        if ($profile instanceof MemberProfile) {
            if (in_array($profile->onboarding_status, ['draft', 'in_progress', 'rejected', 'needs_correction'], true)) {
                return redirect()->route('membership.signup');
            }

            if (in_array($profile->onboarding_status, ['payment_pending', 'payment_processing', 'payment_failed'], true)) {
                return redirect()->route('membership.payment');
            }

            if (in_array($profile->onboarding_status, ['approved', 'active'], true)) {
                return redirect()->route('home');
            }

            if ($profile->onboarding_status === 'pending_review') {
                return;
            }
        } else {
            if (in_array($profile->membership_status, ['draft', 'in_progress', null], true)) {
                return redirect()->route('membership.signup');
            }

            if ($profile->membership_status === 'active') {
                return redirect()->route('home');
            }

            if (in_array($profile->membership_status, ['payment_pending', 'payment_processing', 'payment_failed'], true)) {
                return redirect()->route('membership.payment');
            }

            if (in_array($profile->membership_status, ['rejected', 'needs_correction'], true)) {
                return redirect()->route('membership.signup');
            }
        }
    }

    public function refreshStatus(): void
    {
        $user = auth()->user();
        $profile = $user->memberProfile ?? $user->profile;

        if (! $profile) {
            $this->redirect(route('membership.signup'));

            return;
        }

        $status = $profile instanceof MemberProfile
            ? $profile->onboarding_status
            : $profile->membership_status;

        if (in_array($status, ['payment_pending', 'payment_processing', 'payment_failed'], true)) {
            $this->redirect(route('membership.payment'));

            return;
        }

        if (in_array($status, ['approved', 'active'], true)) {
            $this->redirect(route('home'));

            return;
        }
    }

    public function render()
    {
        return view('livewire.membership.pending-review')
            ->layout('layouts.guest-livewire', [
                'title' => 'Application Submitted',
            ]);
    }
}
