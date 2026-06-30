<?php

namespace App\Livewire\Home;

use App\Services\CoinsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class HomeDashboard extends Component
{
    public array $phrases = [
        'May Allah increase you in goodness',
        'Begin with bismillah — gentle and steady',
        'Barakah follows sincere intention',
        'Small consistent deeds are beloved',
        'Seek knowledge — it lights the path',
        'Make time for quiet remembrance',
        'Alhamdulillah for this moment',
    ];

    public function getGreeting(): string
    {
        $user = Auth::user();
        $first = trim(Str::of($user?->name ?? 'Sister')->before(' '));

        return "Assalamu Alaykum, {$first}";
    }

    public function getDailyPhrase(): string
    {
        $index = Carbon::now()->dayOfWeek; // 0..6

        return $this->phrases[$index] ?? $this->phrases[0];
    }

    protected function upcomingEvents(): array
    {
        if (! Schema::hasTable('events')) {
            return [];
        }

        return DB::table('events')
            ->where('status', 'published')
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->limit(3)
            ->get()
            ->all();
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.home.home-dashboard', [
            'greeting' => $this->getGreeting(),
            'dailyPhrase' => $this->getDailyPhrase(),
            'balance' => $user ? CoinsService::getBalance($user) : 0,
            'events' => $this->upcomingEvents(),
            'onboardingStatus' => $user->profile?->onboarding_status,
        ])->layout('layouts.app', [
            'title' => 'Home',
        ]);
    }
}
