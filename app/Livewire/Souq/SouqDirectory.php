<?php

namespace App\Livewire\Souq;

use App\Models\SouqListing;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SouqDirectory extends Component
{
    use WithPagination;

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sort = 'newest';

    public function getSouqListingsProperty()
    {
        $query = SouqListing::query()
            ->active()
            ->when($this->category !== 'all', fn ($query) => $query->where('category', $this->category))
            ->when($this->search !== '', fn ($query) => $query->where('business_name', 'like', "%{$this->search}%"))
            ->with('owner');

        $query = match ($this->sort) {
            'oldest' => $query->orderBy('created_at'),
            'title_asc' => $query->orderBy('business_name'),
            'title_desc' => $query->orderByDesc('business_name'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.souq.souq-directory')
            ->layout('layouts.app', ['title' => 'The Souq']);
    }
}
