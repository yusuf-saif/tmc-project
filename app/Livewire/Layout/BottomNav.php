<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Str;
use Livewire\Attributes\Layout; // for future use if needed
use Livewire\Component;

class BottomNav extends Component
{
    public array $tabs = [];

    public function mount(): void
    {
        $this->tabs = [
            [
                'label' => 'Home',
                'href' => url('/home'),
                'active' => $this->isActive('/home'),
                'icon' => $this->heroicon('home'),
            ],
            [
                'label' => 'Events',
                'href' => url('/events'),
                'active' => $this->isActive('/events'),
                'icon' => $this->heroicon('calendar'),
            ],
            [
                'label' => 'Souq',
                'href' => url('/souq'),
                'active' => $this->isActive('/souq'),
                'icon' => $this->heroicon('building-storefront'),
            ],
            [
                'label' => 'Community',
                'href' => url('/community'),
                'active' => $this->isActive('/community'),
                'icon' => $this->heroicon('users'),
            ],
            [
                'label' => 'Wallet',
                'href' => url('/wallet'),
                'active' => $this->isActive('/wallet'),
                'icon' => $this->heroicon('banknotes'),
            ],
            [
                'label' => 'Journal',
                'href' => url('/journal'),
                'active' => $this->isActive('/journal'),
                'icon' => $this->heroicon('book-open'),
            ],
            [
                'label' => 'Profile',
                'href' => url('/profile'),
                'active' => $this->isActive('/profile'),
                'icon' => $this->heroicon('user'),
            ],
        ];
    }

    protected function isActive(string $pathPrefix): bool
    {
        $current = '/'.trim(request()->path(), '/');
        return $current === $pathPrefix || Str::startsWith($current.'/', trim($pathPrefix, '/').'/');
    }

    protected function heroicon(string $name): string
    {
        // Minimal set of outline icons as inline SVG (24px).
        return match ($name) {
            'home' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 9-9 9 9M4.5 9.75v10.5A1.5 1.5 0 0 0 6 21.75h12a1.5 1.5 0 0 0 1.5-1.5V9.75"/></svg>',
            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Zm0-9h18"/></svg>',
            'building-storefront' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 4.5 3.75h15L21 7.5M3 7.5h18M4.5 7.5V18a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5V7.5M9 21V12h6v9"/></svg>',
            'users' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5a6 6 0 1 0-12 0m18 0a4.5 4.5 0 0 0-7.5-3.495M12 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 2.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>',
            'banknotes' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75h19.5a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75H2.25a.75.75 0 0 0-.75.75v8.25c0 .414.336.75.75.75ZM4.5 9V6.75A1.5 1.5 0 0 1 6 5.25h12a1.5 1.5 0 0 1 1.5 1.5V9M7.5 15a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Zm9-2.25h1.5m-1.5 3h3"/></svg>',
            'book-open' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c-1.5-1.125-3.75-1.125-6 0v10.5c2.25-1.125 4.5-1.125 6 0m0-10.5c1.5-1.125 3.75-1.125 6 0v10.5c-2.25-1.125-4.5-1.125-6 0"/></svg>',
            'user' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 19.5a7.5 7.5 0 0 1 15 0"/></svg>',
            default => '',
        };
    }

    public function render()
    {
        return view('livewire.layout.bottom-nav');
    }
}
