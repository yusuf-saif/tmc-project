<?php

namespace App\Filament\Pages;

use App\Jobs\BroadcastPushNotification;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\AuditLogService;
use Filament\Pages\Page;

class BroadcastNotifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Broadcast';
    protected static ?string $navigationGroup = 'Communications';
    protected static string $view = 'filament.pages.broadcast-notifications';
    protected static ?string $slug = 'broadcast';

    public string $notificationTitle = '';
    public string $notificationBody = '';
    public string $audienceType = 'all';
    public array $audienceValue = [];
    public bool $sent = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function getAudienceOptionsProperty(): array
    {
        if ($this->audienceType === 'interest') {
            return Interest::query()->active()->pluck('name', 'id')->all();
        }

        if ($this->audienceType === 'goal') {
            return Goal::query()->active()->pluck('name', 'id')->all();
        }

        return [];
    }

    public function getPreviewCountProperty(): int
    {
        if ($this->audienceType === 'all') {
            return User::query()->whereHas('profile', fn ($query) => $query->whereNotNull('onboarding_completed_at'))->count();
        }

        if ($this->audienceType === 'interest') {
            return User::query()
                ->whereHas('profile', fn ($query) => $query->whereNotNull('onboarding_completed_at'))
                ->whereHas('interests', fn ($query) => $query->whereIn('interests.id', $this->audienceValue))
                ->distinct('users.id')
                ->count('users.id');
        }

        return User::query()
            ->whereHas('profile', fn ($query) => $query->whereNotNull('onboarding_completed_at'))
            ->whereHas('goals', fn ($query) => $query->whereIn('goals.id', $this->audienceValue))
            ->distinct('users.id')
            ->count('users.id');
    }

    public function getHistoryProperty()
    {
        return NotificationLog::query()->latest('sent_at')->limit(10)->get();
    }

    public function send(): void
    {
        $this->validate([
            'notificationTitle' => ['required'],
            'notificationBody' => ['required'],
        ]);

        $notificationLog = NotificationLog::query()->create([
            'type' => 'broadcast',
            'title' => $this->notificationTitle,
            'body' => $this->notificationBody,
            'audience_type' => $this->audienceType,
            'audience_value' => $this->audienceValue,
            'sent_by' => auth()->id(),
            'sent_at' => now(),
            'delivery_count' => $this->previewCount,
        ]);

        BroadcastPushNotification::dispatch($notificationLog);
        AuditLogService::log('broadcast_sent', null, [], ['title' => $this->notificationTitle, 'audience' => $this->audienceType]);

        $this->sent = true;
        session()->flash('success', "Broadcast queued — insha'Allah it will reach {$this->previewCount} sisters.");
    }
}
