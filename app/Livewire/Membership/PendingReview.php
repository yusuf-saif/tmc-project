<?php

namespace App\Livewire\Membership;

use App\Models\MemberProfile;
use Livewire\Component;

class PendingReview extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        $profile = $user->memberProfile ?? $user->profile;

        if (! $profile) {
            $this->redirectRoute('membership.onboarding', navigate: true);

            return;
        }

        if ($profile instanceof MemberProfile) {
            if (in_array($profile->onboarding_status, ['draft', 'in_progress', 'rejected', 'needs_correction'], true)) {
                $this->redirectRoute('membership.onboarding', navigate: true);

                return;
            }

            if (in_array($profile->onboarding_status, ['approved_pending_payment', 'payment_submitted'], true)) {
                $this->redirectRoute('membership.payment', navigate: true);

                return;
            }

            if (in_array($profile->onboarding_status, ['approved', 'active'], true)) {
                $this->redirectRoute('home', navigate: true);

                return;
            }

            if ($profile->onboarding_status === 'pending_review') {
                return;
            }
        } else {
            if (in_array($profile->membership_status, ['draft', 'in_progress', null], true)) {
                $this->redirectRoute('membership.onboarding', navigate: true);

                return;
            }

            if ($profile->membership_status === 'active') {
                $this->redirectRoute('home', navigate: true);

                return;
            }

            if (in_array($profile->membership_status, ['approved_pending_payment', 'payment_submitted'], true)) {
                $this->redirectRoute('membership.payment', navigate: true);

                return;
            }

            if (in_array($profile->membership_status, ['rejected', 'needs_correction'], true)) {
                $this->redirectRoute('membership.onboarding', navigate: true);

                return;
            }
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
