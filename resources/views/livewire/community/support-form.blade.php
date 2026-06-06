<div class="space-y-6">
    @if ($submitted)
        <section class="space-y-4 rounded-[8px] bg-white p-6 text-center" style="border: 1px solid var(--border);">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">{{ $this->heading() }}</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">JazakAllahu Khairan — your application has been submitted! We'll be in touch insha'Allah.</p>
            <a href="{{ route('community') }}" class="tmc-link">Back to Community</a>
        </section>
    @elseif ($existing)
        <section class="space-y-4 rounded-[8px] p-6" style="background: var(--gold-pale); border: 1px solid rgba(200, 168, 75, 0.25);">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">{{ $this->heading() }}</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">Your application is under review — JazakAllahu Khairan for your interest. We'll be in touch insha'Allah.</p>
            <a href="{{ route('community') }}" class="tmc-link">Back to Community</a>
        </section>
    @else
        <section class="space-y-6">
            <div>
                <h1 class="font-display text-[1.8rem] leading-none text-teal">{{ $this->heading() }}</h1>
            </div>

            <div class="space-y-4 rounded-[8px] bg-white p-5" style="border: 1px solid var(--border);">
                <div>
                    <label class="tmc-label">Name</label>
                    <input type="text" wire:model="name" class="tmc-input">
                    @error('name') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tmc-label">Email</label>
                    <input type="email" wire:model="email" class="tmc-input">
                    @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tmc-label">{{ $this->skillsLabel() }}</label>
                    <textarea wire:model="skillsOrFocus" class="min-h-[120px] w-full bg-white px-4 py-3 text-sm font-light text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;"></textarea>
                    @error('skillsOrFocus') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tmc-label">Why do you want to get involved?</label>
                    <textarea wire:model="motivation" class="min-h-[120px] w-full bg-white px-4 py-3 text-sm font-light text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;"></textarea>
                    @error('motivation') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="tmc-label">Your Availability</label>
                    <input type="text" wire:model="availability" placeholder="e.g. Weekends, evenings, flexible" class="tmc-input">
                    @error('availability') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="submit" class="tmc-button-gold w-full">Submit Application</button>
            </div>
        </section>
    @endif
</div>
