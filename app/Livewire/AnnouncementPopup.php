<?php

namespace App\Livewire;

use App\Models\InAppAnnouncement;
use App\Services\NotificationService;
use Livewire\Component;

class AnnouncementPopup extends Component
{
    public $announcements = [];

    public $currentIndex = 0;

    public $showModal = false;

    public $currentAnnouncement = null;

    protected function getNotificationService(): NotificationService
    {
        return app(NotificationService::class);
    }

    public function mount(): void
    {
        $this->loadAnnouncements();
    }

    public function loadAnnouncements(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->resetState();

            return;
        }

        $this->announcements = $this->getNotificationService()
            ->getVisibleAnnouncementsForUser($user)
            ->toArray();

        $this->currentIndex = 0;

        if (count($this->announcements) > 0) {
            $this->showNext();
        } else {
            $this->resetState();
        }
    }

    public function showNext(): void
    {
        if ($this->currentIndex >= count($this->announcements)) {
            $this->resetState();

            return;
        }

        $next = $this->announcements[$this->currentIndex] ?? null;

        if (! is_array($next) || empty($next['id'])) {
            $this->resetState();

            return;
        }

        $this->currentAnnouncement = $next;
        $this->showModal = true;
    }

    public function dismiss(): void
    {
        if (! is_array($this->currentAnnouncement) || empty($this->currentAnnouncement['id'])) {
            $this->resetState();

            return;
        }

        $user = auth()->user();

        if (! $user) {
            $this->resetState();

            return;
        }

        $announcement = InAppAnnouncement::find($this->currentAnnouncement['id']);

        if ($announcement) {
            $this->getNotificationService()->dismissAnnouncement($user, $announcement);
        }

        $this->currentIndex++;
        $this->showNext();
    }

    public function dismissAll(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->resetState();

            return;
        }

        foreach ($this->announcements as $announcement) {
            if (is_array($announcement) && ! empty($announcement['id'])) {
                $model = InAppAnnouncement::find($announcement['id']);
                if ($model) {
                    $this->getNotificationService()->dismissAnnouncement($user, $model);
                }
            }
        }

        $this->resetState();
    }

    protected function resetState(): void
    {
        $this->announcements = [];
        $this->currentIndex = 0;
        $this->showModal = false;
        $this->currentAnnouncement = null;
    }

    public function render()
    {
        return view('livewire.announcement-popup');
    }
}
