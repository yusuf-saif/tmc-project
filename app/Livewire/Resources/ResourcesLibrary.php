<?php

namespace App\Livewire\Resources;

use App\Models\Category;
use App\Models\Resource;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ResourcesLibrary extends Component
{
    use WithPagination;

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sort = 'newest';

    public function getResourcesProperty()
    {
        $query = Resource::query()->published()->with('category');

        if ($this->category !== 'all') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }

        if ($this->search !== '') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        $query = match ($this->sort) {
            'oldest' => $query->orderBy('created_at'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate(12);
    }

    public function getCategoriesProperty()
    {
        return Category::ordered()->get();
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

    public function render()
    {
        return view('livewire.resources.resources-library')
            ->layout('layouts.app', ['title' => 'Resources']);
    }
}
