<?php

namespace App\Livewire\Souq;

use App\Models\SouqListing;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ApplyForm extends Component
{
    use WithFileUploads;

    public string $businessName = '';

    public string $category = '';

    public string $description = '';

    public string $contactEmail = '';

    public string $phone = '';

    public string $website = '';

    public string $instagram = '';

    public $logo = null;

    public bool $submitted = false;

    public bool $hasApproved = false;

    public bool $hasPending = false;

    public ?SouqListing $approvedListing = null;

    public function mount(): void
    {
        $this->contactEmail = (string) auth()->user()->email;
        $this->syncListingState();
    }

    public function submit(): void
    {
        if ($this->hasApproved || $this->hasPending) {
            return;
        }

        $this->validate([
            'businessName' => ['required', 'max:255'],
            'category' => ['required', 'in:fashion,food_catering,health_beauty,education,services,creative,other'],
            'description' => ['required', 'max:300'],
            'contactEmail' => ['required', 'email'],
            'phone' => ['nullable', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $path = $this->logo ? $this->logo->store('souq/logos', 'public') : null;

        SouqListing::query()->create([
            'user_id' => auth()->id(),
            'business_name' => $this->businessName,
            'category' => $this->category,
            'description' => $this->description,
            'contact_email' => $this->contactEmail,
            'phone' => $this->phone ?: null,
            'website' => $this->website ?: null,
            'instagram' => $this->instagram ?: null,
            'logo_path' => $path,
            'status' => 'pending',
        ]);

        $this->submitted = true;
        $this->syncListingState();
    }

    protected function syncListingState(): void
    {
        $this->approvedListing = SouqListing::query()
            ->where('user_id', auth()->id())
            ->where('status', 'approved')
            ->first();

        $pending = SouqListing::query()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        $this->hasApproved = $this->approvedListing !== null;
        $this->hasPending = $pending !== null;
    }

    public function render(): View
    {
        return view('livewire.souq.apply-form')
            ->layout('layouts.app', ['title' => 'List Your Business']);
    }
}
