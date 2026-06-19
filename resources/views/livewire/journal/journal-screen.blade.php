@php use Illuminate\Support\Str; @endphp

<div x-data="{ showModal: false, showDuaForm: $wire.entangle('showDuaForm') }"
     x-on:open-modal.window="showModal = true"
     x-on:close-modal.window="showModal = false"
     class="space-y-6 px-4">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">My Journal</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Keep a private space for your reflections and the du'as you return to most.</p>
    </section>

    <section class="journal-tabs">
        @foreach (['entries' => 'Entries', 'dua_list' => "Du'a List"] as $value => $label)
            <button type="button" wire:click="$set('tab', '{{ $value }}')"
                    class="journal-tab {{ $tab === $value ? 'journal-tab-active' : 'journal-tab-inactive' }}">
                {{ $label }}
            </button>
        @endforeach
    </section>

    @if ($tab === 'entries')
        <button type="button" wire:click="openNewEntry" class="journal-fab-btn" aria-label="New entry">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="white" stroke-width="2" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </button>

        @if ($this->entries->isNotEmpty())
            <div class="space-y-4">
                @foreach ($this->entries as $entry)
                    @php
                        $emoji = match ($entry->mood) {
                            'happy' => '😊',
                            'grateful' => '🤲',
                            'reflective' => '🌙',
                            'sad' => '😔',
                            'anxious' => '😟',
                            default => '😐',
                        };
                    @endphp
                    <article class="journal-entry-card">
                        <div class="journal-mood">{{ $emoji }}</div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:0.875rem;font-weight:600;color:var(--ink);">{{ $entry->entry_date->format('d M Y') }}</p>
                            <p style="margin-top:4px;font-size:0.8rem;font-weight:300;color:var(--ink-soft);line-height:1.6;">{{ Str::limit($entry->body, 80) }}</p>
                        </div>
                        <div class="journal-entry-actions">
                            <button type="button" wire:click="openEditEntry({{ $entry->id }})"
                                    class="journal-action-btn" aria-label="Edit entry">✎</button>
                            <button type="button" wire:click="deleteEntry({{ $entry->id }})"
                                    wire:confirm="Delete this entry?"
                                    class="journal-action-btn" aria-label="Delete entry">🗑</button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <section class="card empty-state" style="padding:3rem 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mx-auto h-12 w-12 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V18a2.25 2.25 0 0 1-2.25 2.25H7.5A2.25 2.25 0 0 1 5.25 18V5.25A1.5 1.5 0 0 1 6.75 3.75h9.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25h4.5M9 12h3"/></svg>
                <p class="mt-4 font-display text-[1.3rem] text-teal">Your journal is waiting</p>
                <p class="mt-2 text-[0.875rem] font-light leading-7 text-ink-soft">Begin your first reflection, insha'Allah</p>
                <button type="button" wire:click="openNewEntry" class="tmc-button-gold mx-auto mt-5 max-w-[180px]">New Entry</button>
            </section>
        @endif
    @else
        <div class="flex justify-end">
            <button type="button" @click="showDuaForm = ! showDuaForm" class="journal-dua-add-btn"
                    style="border:1.5px solid var(--teal);border-radius:var(--radius-sm);">
                + Add Du'a
            </button>
        </div>

        <div x-show="showDuaForm" x-transition class="space-y-3" x-cloak>
            <div class="journal-dua-card">
                <textarea wire:model="duaText" class="input-textarea" placeholder="Type your du'a..." style="min-height:100px;margin-bottom:8px;"></textarea>
                <input type="text" wire:model="duaLabel" class="input" placeholder="Label (optional)" style="margin-bottom:8px;">
                <button type="button" wire:click="saveDuaManual" class="btn-gold-full">Save Du'a</button>
            </div>
        </div>

        @if ($this->duaItems->isNotEmpty())
            <div class="space-y-4">
                @foreach ($this->duaItems as $item)
                    <article class="journal-dua-card">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1 space-y-3">
                                <p class="text-right font-arabic text-base leading-8 text-ink" dir="rtl">{{ $item->dua_text }}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($item->label)
                                        <span class="journal-dua-label" style="background:var(--teal-lt);color:var(--teal);">{{ $item->label }}</span>
                                    @endif
                                    @if ($item->resource_id)
                                        <span class="journal-dua-label" style="background:var(--gold-pale);color:var(--gold);">From Library</span>
                                    @endif
                                </div>
                                @if ($item->resource)
                                    <a href="{{ route('resources.show', $item->resource->slug) }}" class="text-[11px] text-ink-soft">View original →</a>
                                @endif
                            </div>
                            <button type="button" wire:click="removeDuaItem({{ $item->id }})"
                                    wire:confirm="Remove from Du'a List?" class="journal-action-btn">🗑</button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <section class="card empty-state">
                <p class="text-sm font-light leading-7 text-ink-soft">Your Du'a List is empty — save du'as from the Resources library or add your own</p>
            </section>
        @endif
    @endif

    {{-- Modal Backdrop --}}
    <div x-show="showModal" x-transition.opacity class="modal-backdrop" x-cloak></div>
    {{-- Modal Drawer --}}
    <div x-show="showModal" x-transition class="modal-drawer" x-cloak>
        <div class="drawer-handle"></div>
        <div class="space-y-5">
            <div>
                <h2 class="font-display text-[1.5rem] leading-none text-teal">{{ $editingId ? 'Edit Entry' : 'New Entry' }}</h2>
            </div>

            <div>
                <label class="tmc-label">Date</label>
                <input type="date" wire:model="entryDate" class="input">
            </div>

            <div class="space-y-3">
                <label class="tmc-label">How are you feeling?</label>
                <div class="journal-mood-grid">
                    @foreach (['happy' => '😊', 'grateful' => '🤲', 'reflective' => '🌙', 'sad' => '😔', 'anxious' => '😟', 'neutral' => '😐'] as $value => $emoji)
                        <button type="button" wire:click="$set('mood', '{{ $value }}')"
                                class="journal-mood-btn {{ $mood === $value ? 'journal-mood-active' : 'journal-mood-inactive' }}">
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="tmc-label">Your reflection</label>
                <textarea wire:model="body" class="input-textarea" style="min-height:140px;" placeholder="Write your thoughts here..."></textarea>
            </div>

            <button type="button" wire:click="saveEntry" class="btn-gold-full">Save Entry</button>
            <button type="button" @click="showModal = false" class="text-sm text-teal">Cancel</button>
        </div>
    </div>
</div>
