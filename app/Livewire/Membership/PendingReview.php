<?php

namespace App\Livewire\Membership;

use Livewire\Component;

class PendingReview extends Component
{
    public function mount()
    {
        return redirect()->route('home');
    }

    public function render()
    {
        return view('livewire.membership.pending-review')
            ->layout('layouts.guest-livewire', [
                'title' => 'Application Submitted',
            ]);
    }
}
