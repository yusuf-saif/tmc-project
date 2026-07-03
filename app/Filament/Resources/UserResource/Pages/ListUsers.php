<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\MembersImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

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
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $disk = config('filament.default_filesystem_disk', 'public');

                    $result = app(MembersImportService::class)->import($data['csv_file'], $disk);

                    Storage::disk($disk)->delete($data['csv_file']);

                    $message = sprintf(
                        'Imported %d members. %d skipped.',
                        $result['imported'],
                        $result['skipped']
                    );

                    if (! empty($result['errors'])) {
                        Notification::make()
                            ->title('Import completed with errors')
                            ->body($message.' '.count($result['errors']).' errors.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import completed')
                            ->body($message)
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
