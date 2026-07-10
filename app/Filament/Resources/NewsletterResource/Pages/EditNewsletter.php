<?php

namespace App\Filament\Resources\NewsletterResource\Pages;

use App\Filament\Resources\NewsletterResource;
use App\Services\AuditLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('Save Draft')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['draft', 'failed']))
                ->action(function () {
                    $data = $this->form->getState();
                    $data['status'] = 'draft';
                    $this->record->update($data);

                    AuditLogService::log('newsletter_updated', $this->record, [], $this->record->only([
                        'subject', 'target_audience', 'status',
                    ]));

                    Notification::make()
                        ->title('Draft saved')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        AuditLogService::log('newsletter_updated', $this->record, [], $this->record->only([
            'subject', 'target_audience', 'status',
        ]));
    }
}
