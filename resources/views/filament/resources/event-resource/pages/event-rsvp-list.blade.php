<x-filament-panels::page>
    <div class="space-y-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ $record->title }}</h2>
            <p class="text-sm text-gray-600">RSVPs for this event, including cancelled responses.</p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
