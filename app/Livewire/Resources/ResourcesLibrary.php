<?php

namespace App\Livewire\Resources;

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

    public function getResourcesProperty()
    {
        $query = Resource::query()->published();

        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        if ($this->search !== '') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return $query->orderByDesc('created_at')->paginate(12);
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
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
