<x-filament-panels::page>
    <div class="space-y-6">
        <div class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Broadcast Notification</h2>
            <div class="grid gap-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" wire:model="notificationTitle" class="w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Body</label>
                    <textarea wire:model="notificationBody" rows="5" class="w-full rounded-lg border-gray-300 shadow-sm"></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Audience</label>
                    <select wire:model.live="audienceType" class="w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="all">All Members</option>
                        <option value="interest">By Interest</option>
                        <option value="goal">By Goal</option>
                    </select>
                </div>
                @if (in_array($this->audienceType, ['interest', 'goal'], true))
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Selection</label>
                        <select wire:model="audienceValue" multiple class="w-full rounded-lg border-gray-300 shadow-sm">
                            @foreach ($this->audienceOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <p class="text-sm text-gray-600">This will reach {{ $this->previewCount }} sisters</p>
                <div>
                    <x-filament::button wire:click="send" wire:loading.attr="disabled">Send</x-filament::button>
                </div>
                @if ($sent)
                    <p class="text-sm text-green-600">Broadcast queued successfully.</p>
                @endif
            </div>
        </div>

        <div class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Recent Broadcasts</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">Title</th>
                            <th class="py-2 pr-4">Audience</th>
                            <th class="py-2 pr-4">Sent</th>
                            <th class="py-2">Delivery Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->history as $log)
                            <tr>
                                <td class="py-2 pr-4">{{ $log->title }}</td>
                                <td class="py-2 pr-4">{{ ucfirst($log->audience_type) }}</td>
                                <td class="py-2 pr-4">{{ $log->sent_at->format('d M Y H:i') }}</td>
                                <td class="py-2">{{ $log->delivery_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
