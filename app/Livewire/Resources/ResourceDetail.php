<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use App\Services\DuaListService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ResourceDetail extends Component
{
    public Resource $resource;

    public bool $isSaved = false;

    public function mount(string $slug, DuaListService $duaListService): void
    {
        $this->resource = Resource::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->isSaved = $duaListService->isSaved(Auth::user(), $this->resource);
    }

    public function saveToDuaList(DuaListService $duaListService): void
    {
        if ($this->isSaved) {
            return;
        }

        $duaListService->save(Auth::user(), $this->resource);
        $this->isSaved = true;

        session()->flash('success', 'Saved to your Du\'a List.');
    }

    public function removeFromDuaList(DuaListService $duaListService): void
    {
        $item = Auth::user()->duaListItems()
            ->where('resource_id', $this->resource->id)
            ->first();

        if (! $item) {
            $this->isSaved = false;

            return;
        }

        $duaListService->remove(Auth::user(), $item);
        $this->isSaved = false;

        session()->flash('success', 'Removed from your Du\'a List.');
    }

    public function bodyContainsHtml(): bool
    {
        return filled($this->resource->body) && Str::contains($this->resource->body, '<');
    }

    public function render(): View
    {
        return view('livewire.resources.resource-detail')
            ->layout('layouts.app', ['title' => $this->resource->title]);
    }
}
