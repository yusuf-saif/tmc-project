<div
    x-data="{
        showModal: @js($showModal),
        currentIndex: @js($currentIndex),
    }"
    x-effect="showModal = $wire.showModal; currentIndex = $wire.currentIndex"
    wire:poll.5s="loadAnnouncements"
    x-show="showModal"
>
    @isset($currentAnnouncement)
    <template x-if="showModal && $wire.currentAnnouncement">
        <div class="announce-overlay"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="announce-backdrop"
                 @if($currentAnnouncement['dismissible'] ?? false)
                 wire:click="dismiss"
                 @endif
            ></div>

            {{-- Modal --}}
            <div class="announce-modal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-end="opacity-0 scale-95">

                {{-- Type indicator bar --}}
                <div class="announce-type-bar announce-type-{{ $currentAnnouncement['type'] ?? 'info' }}"></div>

                {{-- Content --}}
                <div class="announce-content">
                    {{-- Priority badge --}}
                    @if(($currentAnnouncement['priority'] ?? 'medium') === 'high')
                    <span class="announce-priority announce-priority-high">Urgent</span>
                    @elseif(($currentAnnouncement['priority'] ?? 'medium') === 'medium')
                    <span class="announce-priority announce-priority-medium">Important</span>
                    @endif

                    <h3 class="announce-title">{{ $currentAnnouncement['title'] ?? '' }}</h3>
                    <div class="announce-body">{!! $currentAnnouncement['body'] ?? '' !!}</div>
                </div>

                {{-- Actions --}}
                <div class="announce-actions">
                    @if(($currentAnnouncement['dismissible'] ?? false) && count($announcements) > 1)
                    <button wire:click="dismissAll" wire:loading.attr="disabled" class="announce-dismiss-all">
                        Dismiss All ({{ count($announcements) - $currentIndex }})
                    </button>
                    @else
                    <span></span>
                    @endif

                    <div class="announce-btn-group">
                        @if($currentAnnouncement['dismissible'] ?? false)
                        <button wire:click="dismiss" wire:loading.attr="disabled" class="announce-got-it">
                            Got it
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Counter --}}
                @if(count($announcements) > 1)
                <div class="announce-counter">
                    <span class="announce-counter-text">{{ $currentIndex + 1 }} of {{ count($announcements) }}</span>
                </div>
                @endif
            </div>
        </div>
    </template>
    @endisset
</div>
