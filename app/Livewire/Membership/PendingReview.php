<?php

namespace App\Livewire\Membership;

use Livewire\Component;

class PendingReview extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (! $profile) {
            $this->redirectRoute('membership.onboarding', navigate: true);

            return;
        }

        if (in_array($profile->membership_status, ['draft', null], true)) {
            $this->redirectRoute('membership.onboarding', navigate: true);

            return;
        }

        if ($profile->membership_status === 'active') {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        if ($profile->membership_status === 'approved_pending_payment') {
            $this->redirectRoute('membership.payment', navigate: true);

            return;
        }

        if (in_array($profile->membership_status, ['rejected', 'needs_correction'], true)) {
            $this->redirectRoute('membership.onboarding', navigate: true);

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
