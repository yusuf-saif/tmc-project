<?php

namespace App\Livewire\Journal;

use App\Models\JournalEntry;
use App\Services\DuaListService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JournalScreen extends Component
{
    use AuthorizesRequests;

    public string $tab = 'entries';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $entryDate = '';

    public string $mood = '';

    public string $body = '';

    public string $duaText = '';

    public string $duaLabel = '';

    public bool $showDuaForm = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('member'), 403);

        $this->entryDate = now()->format('Y-m-d');
    }

    public function getEntriesProperty()
    {
        return JournalEntry::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('entry_date')
            ->get();
    }

    public function getDuaItemsProperty()
    {
        return Auth::user()->duaListItems()
            ->with('resource')
            ->orderByDesc('created_at')
            ->get();
    }

    public function openNewEntry(): void
    {
        $this->resetEntryForm();
        $this->entryDate = now()->format('Y-m-d');
        $this->showModal = true;
        $this->editingId = null;
    }

    public function openEditEntry(int $id): void
    {
        $entry = JournalEntry::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $this->authorize('view', $entry);

        $this->editingId = $entry->id;
        $this->entryDate = $entry->entry_date->format('Y-m-d');
        $this->mood = $entry->mood;
        $this->body = $entry->body;
        $this->showModal = true;
    }

    public function saveEntry(): void
    {
        $this->validate([
            'entryDate' => ['required', 'date'],
            'mood' => ['required', 'in:happy,grateful,reflective,sad,anxious,neutral'],
            'body' => ['required', 'min:1'],
        ]);

        if ($this->editingId) {
            $entry = JournalEntry::query()
                ->where('user_id', Auth::id())
                ->findOrFail($this->editingId);

            $this->authorize('update', $entry);

            $entry->update([
                'entry_date' => $this->entryDate,
                'mood' => $this->mood,
                'body' => $this->body,
            ]);

            session()->flash('success', 'Your journal entry has been updated.');
        } else {
            $this->authorize('create', JournalEntry::class);

            JournalEntry::query()->create([
                'user_id' => Auth::id(),
                'entry_date' => $this->entryDate,
                'mood' => $this->mood,
                'body' => $this->body,
            ]);

            session()->flash('success', 'Your journal entry has been saved.');
        }

        $this->showModal = false;
        $this->resetEntryForm();
    }

    public function deleteEntry(int $id): void
    {
        $entry = JournalEntry::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $this->authorize('delete', $entry);

        $entry->delete();

        session()->flash('success', 'Journal entry deleted.');
    }

    public function saveDuaManual(DuaListService $duaListService): void
    {
        $this->validate([
            'duaText' => ['required', 'min:3'],
            'duaLabel' => ['nullable', 'max:100'],
        ]);

        $duaListService->saveManual(Auth::user(), $this->duaText, $this->duaLabel ?: null);

        $this->duaText = '';
        $this->duaLabel = '';
        $this->showDuaForm = false;

        session()->flash('success', 'Du\'a added to your list.');
    }

    public function removeDuaItem(int $id): void
    {
        $item = Auth::user()->duaListItems()->findOrFail($id);

        app(DuaListService::class)->remove(Auth::user(), $item);

        session()->flash('success', 'Removed from your Du\'a List.');
    }

    protected function resetEntryForm(): void
    {
        $this->editingId = null;
        $this->mood = '';
        $this->body = '';
    }

    public function render(): View
    {
        return view('livewire.journal.journal-screen')
            ->layout('layouts.app', ['title' => 'My Journal']);
    }
}
