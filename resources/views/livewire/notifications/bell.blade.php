<div
  x-data="{ open: false }"
  @click.away="open = false"
  class="relative"
>
  <button
    @click="open = !open"
    class="topbar-btn relative"
    aria-label="Notifications"
  >
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="1.5"
         style="width:20px;height:20px;">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31
               A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75
               a8.967 8.967 0 0 1-2.311 6.022c1.766.68 3.559 1.09
               5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0
               m5.714 0a3 3 0 1 1-5.714 0"/>
    </svg>
    @if ($this->unreadCount > 0)
      <span class="notif-badge"></span>
    @endif
  </button>

  <div
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    style="
      position: absolute; right: 0; top: calc(100% + 8px);
      width: 360px; max-width: calc(100vw - 32px);
      background: var(--glass-bg); backdrop-filter: var(--glass-blur);
      border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg); z-index: 100;
      overflow: hidden;
    "
  >
    <div style="padding: 12px 16px; border-bottom: 1px solid var(--border);">
      <p style="font-family: var(--font-display); font-size: 1rem;
                color: var(--teal-dk); margin: 0;">Notifications</p>
    </div>

    <div style="max-height: 400px; overflow-y: auto;">
      @forelse ($this->recent as $notification)
        <div
          @if ($notification->read_at === null)
            wire:click="markAsRead('{{ $notification->id }}')"
          @endif
          style="
            display: flex; gap: 10px; padding: 12px 16px;
            cursor: {{ $notification->read_at === null ? 'pointer' : 'default' }};
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            {{ $notification->read_at === null ? 'background: rgba(26,107,114,0.04);' : '' }}
          "
          onmouseover="this.style.background='var(--ivory)'"
          onmouseout="this.style.background='{{ $notification->read_at === null ? 'rgba(26,107,114,0.04)' : 'transparent' }}'"
        >
          <div style="flex:1;min-width:0;">
            <p style="margin:0;font-weight:600;font-size:0.8125rem;color:var(--ink);">
              {{ data_get($notification->data, 'title', 'Notification') }}
            </p>
            <p style="margin:2px 0 0;font-size:0.75rem;color:var(--ink-soft);">
              {{ data_get($notification->data, 'body', '') }}
            </p>
            <p style="margin:4px 0 0;font-size:0.6875rem;color:var(--ink-soft);opacity:0.6;">
              {{ $notification->created_at->diffForHumans() }}
            </p>
          </div>
          @if ($notification->read_at === null)
            <span style="
              width: 8px; height: 8px; border-radius: 50%;
              background: var(--teal); flex-shrink: 0; margin-top: 4px;
            "></span>
          @endif
        </div>
      @empty
        <div style="padding: 32px 16px; text-align: center;">
          <p style="margin:0;font-size:0.8125rem;color:var(--ink-soft);">No notifications yet</p>
        </div>
      @endforelse
    </div>

    <a href="{{ route('profile', ['tab' => 'notifications']) }}"
       style="
         display: block; padding: 10px 16px; text-align: center;
         font-size: 0.8125rem; color: var(--teal);
         border-top: 1px solid var(--border);
         text-decoration: none; font-weight: 600;
       "
       onmouseover="this.style.background='var(--ivory)'"
       onmouseout="this.style.background='transparent'">
      View All Notifications
    </a>
  </div>
</div>
