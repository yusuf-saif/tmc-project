<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Jobs\ImportMembersJob;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import Members')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $path = $data['csv_file'];

                        ImportMembersJob::dispatch($path, auth()->id(), 'local');

                        Notification::make()
                            ->title('Import started')
                            ->body('You will be notified when the import completes.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('ListUsers: import failed', ['error' => $e->getMessage()]);

                        Notification::make()
                            ->title('Import failed')
                            ->body('The file could not be uploaded. Please try again.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
