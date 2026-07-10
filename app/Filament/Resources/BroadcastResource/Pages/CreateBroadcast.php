<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use App\Jobs\SendBroadcastNotificationJob;
use App\Services\AuditLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBroadcast extends CreateRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('Save Draft')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Save as Draft')
                ->modalDescription('This broadcast will be saved as a draft and will not be sent or scheduled.')
                ->action(function () {
                    $data = $this->form->getState();
                    $data['created_by'] = auth()->id();
                    $data['status'] = 'draft';
                    $record = $this->getModel()::create($data);

                    AuditLogService::log('broadcast_created', $record, [], $record->only([
                        'title', 'target_audience', 'status',
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

        if (! empty($data['send_at'])) {
            $data['status'] = 'queued';
        } else {
            $data['status'] = 'queued';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogService::log('broadcast_created', $this->record, [], $this->record->only([
            'title', 'target_audience', 'status',
        ]));

        if (empty($this->record->send_at)) {
            SendBroadcastNotificationJob::dispatch($this->record);
        }
    }
}
