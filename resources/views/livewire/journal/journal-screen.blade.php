@php use Illuminate\Support\Str; @endphp

<div x-data="{ showModal: false, showDuaForm: $wire.entangle('showDuaForm') }"
     x-on:open-modal.window="showModal = true"
     x-on:close-modal.window="showModal = false"
     class="space-y-6 px-4">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">My Journal</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Keep a private space for your reflections and the du'as you return to most.</p>
    </section>

    <section class="flex items-center gap-5 border-b" style="border-color: var(--border);">
        @foreach (['entries' => 'Entries', 'dua_list' => "Du'a List"] as $value => $label)
            <button type="button" wire:click="$set('tab', '{{ $value }}')" class="border-b-2 pb-3 text-[13px] font-semibold uppercase tracking-[1.2px] transition" style="border-color: {{ $tab === $value ? '#1A6B72' : 'transparent' }}; color: {{ $tab === $value ? '#1A6B72' : '#6B6760' }}; border-radius: 0;">
                {{ $label }}
            </button>
        @endforeach
    </section>

    @if ($tab === 'entries')
        <button type="button" wire:click="openNewEntry"
                style="position: fixed; bottom: 80px; right: max(16px, calc((100vw - 480px) / 2 + 16px));
                       width: 48px; height: 48px; border-radius: 50%;
                       background: #C8A84B; border: none; cursor: pointer;
                       display: flex; align-items:center; justify-content:center;
                       box-shadow: 0 4px 16px rgba(200,168,75,0.4);
                       z-index: 30;">
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
                    <article class="group flex items-start gap-4 rounded-[12px] bg-white p-4" style="border: 1px solid var(--border);">
                        <div class="text-[1.8rem]">{{ $emoji }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[0.875rem] font-semibold text-ink">{{ $entry->entry_date->format('d M Y') }}</p>
                            <p class="mt-1 line-clamp-2 text-[0.8rem] font-light leading-6 text-ink-soft">{{ Str::limit($entry->body, 80) }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-ink-soft opacity-0 transition group-hover:opacity-100 group-hover:text-ink-md">
                            <button type="button" wire:click="openEditEntry({{ $entry->id }})" aria-label="Edit entry">✎</button>
                            <button type="button" wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Delete this entry?" aria-label="Delete entry">🗑</button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <section class="rounded-[12px] bg-white px-6 py-12 text-center" style="border: 1px solid var(--border); padding: 3rem 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mx-auto h-12 w-12 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V18a2.25 2.25 0 0 1-2.25 2.25H7.5A2.25 2.25 0 0 1 5.25 18V5.25A1.5 1.5 0 0 1 6.75 3.75h9.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25h4.5M9 12h3"/></svg>
                <p class="mt-4 font-display text-[1.3rem] text-teal">Your journal is waiting</p>
                <p class="mt-2 text-[0.875rem] font-light leading-7 text-ink-soft">Begin your first reflection, insha'Allah</p>
                <button type="button" wire:click="openNewEntry" class="tmc-button-gold mx-auto mt-5 max-w-[180px]">New Entry</button>
            </section>
        @endif
    @else
        <div class="flex justify-end">
            <button type="button" @click="showDuaForm = ! showDuaForm" class="inline-flex min-h-11 items-center justify-center px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal transition" style="border: 1px solid #1A6B72; border-radius: 2px;">
                + Add Du'a
            </button>
        </div>

        <div x-show="showDuaForm" x-transition class="space-y-3 rounded-[8px] bg-white p-4" style="border: 1px solid var(--border);" x-cloak>
            <textarea wire:model="duaText" class="min-h-[120px] w-full bg-white px-4 py-3 text-sm font-light text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;" placeholder="Type your du'a..."></textarea>
            <input type="text" wire:model="duaLabel" class="w-full bg-white px-4 py-3 text-sm text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;" placeholder="Label (optional)">
            <button type="button" wire:click="saveDuaManual" class="inline-flex min-h-11 items-center justify-center bg-gold px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal-dk transition" style="border-radius: 2px;">
                Save Du'a
            </button>
        </div>

        @if ($this->duaItems->isNotEmpty())
            <div class="space-y-4">
                @foreach ($this->duaItems as $item)
                    <article class="rounded-[8px] bg-white p-4" style="border: 1px solid var(--border);">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1 space-y-3">
                                <p class="text-right font-arabic text-base leading-8 text-ink" dir="rtl">{{ $item->dua_text }}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($item->label)
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium" style="background: var(--teal-lt); color: var(--teal);">{{ $item->label }}</span>
                                    @endif
                                    @if ($item->resource_id)
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium" style="background: var(--gold-pale); color: var(--gold);">From Library</span>
                                    @endif
                                </div>
                                @if ($item->resource)
                                    <a href="{{ route('resources.show', $item->resource->slug) }}" class="text-[11px] text-ink-soft">View original →</a>
                                @endif
                            </div>
                            <button type="button" wire:click="removeDuaItem({{ $item->id }})" wire:confirm="Remove from Du'a List?" class="text-ink-soft">🗑</button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <section class="rounded-[8px] bg-white px-6 py-12 text-center" style="border: 1px solid var(--border);">
                <p class="text-sm font-light leading-7 text-ink-soft">Your Du'a List is empty — save du'as from the Resources library or add your own</p>
            </section>
        @endif
    @endif

    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" x-cloak></div>
    <div x-show="showModal" x-transition class="fixed inset-x-0 bottom-0 z-50 max-h-screen overflow-y-auto rounded-t-[24px] bg-white p-6" x-cloak>
        <div class="space-y-5">
            <div>
                <div class="mx-auto mb-4 h-1 w-8 rounded-full bg-slate-300"></div>
                <h2 class="font-display text-[1.5rem] leading-none text-teal">{{ $editingId ? 'Edit Entry' : 'New Entry' }}</h2>
            </div>

            <div>
                <label class="tmc-label">Date</label>
                <input type="date" wire:model="entryDate" class="w-full bg-white px-4 py-3 text-sm text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;">
            </div>

            <div class="space-y-3">
                <label class="tmc-label">How are you feeling?</label>
                <div class="grid grid-cols-6 gap-2">
                    @foreach (['happy' => '😊', 'grateful' => '🤲', 'reflective' => '🌙', 'sad' => '😔', 'anxious' => '😟', 'neutral' => '😐'] as $value => $emoji)
                        <button type="button" wire:click="$set('mood', '{{ $value }}')" class="flex h-12 w-12 items-center justify-center text-xl transition-all duration-150 ease-in-out" style="border: {{ $mood === $value ? '2px solid #1A6B72' : '1px solid #E2E8F0' }}; background: {{ $mood === $value ? '#D6EDEF' : '#FFFFFF' }}; border-radius: 6px;">
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="tmc-label">Your reflection</label>
                <textarea wire:model="body" class="min-h-[140px] w-full bg-white px-4 py-3 text-base font-light text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;" placeholder="Write your thoughts here..."></textarea>
            </div>

            <button type="button" wire:click="saveEntry" class="inline-flex min-h-11 w-full items-center justify-center bg-gold px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal-dk transition" style="border-radius: 2px;">
                Save Entry
            </button>
            <button type="button" @click="showModal = false" class="text-sm text-teal">Cancel</button>
        </div>
    </div>
</div>
