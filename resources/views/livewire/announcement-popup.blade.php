<div
    x-data="{
        showModal: @js($showModal),
        currentIndex: @js($currentIndex),
    }"
    x-effect="showModal = $wire.showModal; currentIndex = $wire.currentIndex"
    wire:poll.5s="loadAnnouncements"
    style="display:none;"
>
    @isset($currentAnnouncement)
    <template x-if="showModal && $wire.currentAnnouncement">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-end="opacity-0"
            style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;"
        >
            {{-- Backdrop --}}
            <div
                @if($currentAnnouncement['dismissible'] ?? false)
                wire:click="dismiss"
                @endif
                style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);"
            ></div>

            {{-- Modal --}}
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-end="opacity-0 scale-95"
                style="position:relative;z-index:1;width:100%;max-width:420px;
                       background:white;border-radius:16px;overflow:hidden;
                       box-shadow:0 25px 60px rgba(0,0,0,0.3);"
            >
                {{-- Type indicator bar --}}
                <div style="height:4px;width:100%;
                    background:{{ match($currentAnnouncement['type'] ?? 'info') {
                        'warning' => '#EAB308',
                        'success' => '#22C55E',
                        default => '#1A6B72',
                    } }};">
                </div>

                {{-- Content --}}
                <div style="padding:24px 24px 16px;">
                    {{-- Priority badge --}}
                    @if(($currentAnnouncement['priority'] ?? 'medium') === 'high')
                    <span style="display:inline-block;font-size:10px;font-weight:700;
                                 text-transform:uppercase;letter-spacing:1.2px;
                                 color:white;background:#C53030;
                                 padding:3px 8px;border-radius:4px;margin-bottom:10px;">
                        Urgent
                    </span>
                    @elseif(($currentAnnouncement['priority'] ?? 'medium') === 'medium')
                    <span style="display:inline-block;font-size:10px;font-weight:700;
                                 text-transform:uppercase;letter-spacing:1.2px;
                                 color:#92400E;background:#FEF3C7;
                                 padding:3px 8px;border-radius:4px;margin-bottom:10px;">
                        Important
                    </span>
                    @endif

                    {{-- Title --}}
                    <h3 style="font-family:'Nunito',sans-serif;font-size:1.125rem;
                               font-weight:700;color:#1C1A17;margin:0 0 8px;line-height:1.3;">
                        {{ $currentAnnouncement['title'] ?? '' }}
                    </h3>

                    {{-- Body --}}
                    <div style="font-family:'Nunito',sans-serif;font-size:0.875rem;
                                color:#3D3A35;line-height:1.65;margin:0;">
                        {!! $currentAnnouncement['body'] ?? '' !!}
                    </div>
                </div>

                {{-- Actions --}}
                <div style="padding:0 24px 20px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    @if(($currentAnnouncement['dismissible'] ?? false) && count($announcements) > 1)
                    <button
                        wire:click="dismissAll"
                        wire:loading.attr="disabled"
                        style="font-family:'Nunito',sans-serif;font-size:0.8rem;
                               color:#6B6760;background:none;border:none;cursor:pointer;
                               padding:6px 0;text-decoration:underline;">
                        Dismiss All ({{ count($announcements) - $currentIndex }})
                    </button>
                    @else
                    <span></span>
                    @endif

                    <div style="display:flex;gap:8px;">
                        @if($currentAnnouncement['dismissible'] ?? false)
                        <button
                            wire:click="dismiss"
                            wire:loading.attr="disabled"
                            style="font-family:'Nunito',sans-serif;font-size:0.8125rem;
                                   font-weight:600;color:white;
                                   background:#1A6B72;border:none;border-radius:8px;
                                   padding:10px 20px;cursor:pointer;
                                   transition:background 0.2s;"
                            onmouseover="this.style.background='#0D3F44'"
                            onmouseout="this.style.background='#1A6B72'">
                            Got it
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Counter --}}
                @if(count($announcements) > 1)
                <div style="padding:0 24px 16px;text-align:center;">
                    <span style="font-size:11px;color:#6B6760;">
                        {{ $currentIndex + 1 }} of {{ count($announcements) }}
                    </span>
                </div>
                @endif
            </div>
        </div>
    </template>
    @endisset
</div>
