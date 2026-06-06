<?php

namespace App\Livewire\Community;

use App\Models\CommunitySpace;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CommunityHome extends Component
{
    public function render(): View
    {
        return view('livewire.community.community-home', [
            'spaces' => CommunitySpace::query()->active()->ordered()->get(),
        ])->layout('layouts.app', ['title' => 'Our Community']);
    }
}
