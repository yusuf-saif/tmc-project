<?php

namespace App\Filament\Resources\InAppAnnouncementResource\Pages;

use App\Filament\Resources\InAppAnnouncementResource;
use App\Services\AuditLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInAppAnnouncement extends CreateRecord
{
    protected static string $resource = InAppAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('Save Draft')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Save as Draft')
                ->modalDescription('This announcement will be saved as a draft and will not be active.')
                ->action(function () {
                    $data = $this->form->getState();
                    $data['created_by'] = auth()->id();
                    $data['updated_by'] = auth()->id();
                    $data['status'] = 'inactive';
                    $record = $this->getModel()::create($data);

                    AuditLogService::log('in_app_announcement_created', $record, [], $record->only([
                        'title', 'type', 'priority', 'status',
                    ]));

                    Notification::make()
                        ->title('Draft saved')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if (! empty($data['start_at'])) {
            $data['status'] = 'inactive';
        } else {
            $data['status'] = 'active';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogService::log('in_app_announcement_created', $this->record, [], $this->record->only([
            'title', 'type', 'priority', 'status',
        ]));
    }
}
