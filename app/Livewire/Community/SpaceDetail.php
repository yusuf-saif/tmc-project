<?php

namespace App\Livewire\Community;

use App\Models\CommunitySpace;
use App\Models\Event;
use App\Models\Resource;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SpaceDetail extends Component
{
    public CommunitySpace $space;

    public function mount(string $slug): void
    {
        $this->space = CommunitySpace::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.community.space-detail', [
            'events' => Event::query()->published()->upcoming()->limit(3)->get(),
            'resources' => Resource::query()->published()->limit(3)->get(),
        ])->layout('layouts.app', ['title' => $this->space->name]);
    }
}
