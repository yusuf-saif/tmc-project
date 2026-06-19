<div class="space-y-6">
    <a href="{{ route('profile') }}" class="inline-flex items-center text-[13px] font-medium text-teal">&larr; Profile</a>
    <h1 class="font-display text-[1.8rem] leading-none text-teal">Notifications</h1>

    <section class="space-y-4 rounded-[8px] bg-white p-5" style="border:1px solid var(--border);">
        @foreach ([
            'events' => 'Events & Halaqahs',
            'announcements' => 'Announcements',
            'coins' => 'Coins & Rewards',
            'community' => 'Community Updates',
        ] as $key => $label)
            <label class="flex items-center justify-between border-b pb-4 last:border-b-0 last:pb-0" style="border-color:var(--border);">
                <span class="text-sm text-ink">{{ $label }}</span>
                <button type="button" wire:click="$toggle('{{ $key }}')"
                        class="toggle-switch {{ $$key ? 'bg-teal' : 'bg-slate-300' }}">
                    <span class="toggle-knob {{ $$key ? 'toggle-knob-on' : 'toggle-knob-off' }}"></span>
                </button>
            </label>
        @endforeach

        <button type="button" wire:click="save" class="btn-gold-full"
                wire:loading.attr="disabled">
            Save Preferences
        </button>
    </section>
</div>
