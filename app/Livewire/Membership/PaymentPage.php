<?php

namespace App\Livewire\Membership;

use Livewire\Component;

class PaymentPage extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (! $profile || ! in_array($profile->membership_status, ['approved_pending_payment', 'payment_submitted'], true)) {
            $this->redirectRoute('home', navigate: true);

            return;
        }
    }

    public function render()
    {
        $profile = auth()->user()->profile;

        return view('livewire.membership.payment-page', [
            'profile' => $profile,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
