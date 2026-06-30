<?php

namespace App\Livewire\Souq;

use App\Models\SouqListing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ListingDetail extends Component
{
    public SouqListing $listing;

    public function mount(string $slug): void
    {
        $this->listing = SouqListing::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function instagramHandle(): ?string
    {
        if (blank($this->listing->instagram)) {
            return null;
        }

        return ltrim($this->listing->instagram, '@');
    }

    public function websiteLabel(): ?string
    {
        if (blank($this->listing->website)) {
            return null;
        }

        return Str::limit($this->listing->website, 36);
    }

    public function render(): View
    {
        return view('livewire.souq.listing-detail')
            ->layout('layouts.app', ['title' => $this->listing->business_name]);
    }
}
