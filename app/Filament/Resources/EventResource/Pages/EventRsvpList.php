<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class EventRsvpList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.event-rsvp-list';

    public Event $record;

    public function mount(int|string $record): void
    {
        $this->record = Event::query()->findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->rsvps()->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rsvp_at')
                    ->dateTime('D M Y H:i'),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->dateTime('D M Y H:i'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->action(function () {
                    $records = $this->record->rsvps()->with('user')->get();

                    return response()->streamDownload(function () use ($records): void {
                        $output = fopen('php://output', 'w');
                        fputcsv($output, ['Name', 'Email', 'RSVP Date', 'Cancelled']);

                        foreach ($records as $record) {
                            fputcsv($output, [
                                $record->user?->name,
                                $record->user?->email,
                                $record->rsvp_at,
                                $record->cancelled_at,
                            ]);
                        }

                        fclose($output);
                    }, 'rsvps.csv');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Event RSVPs';
    }
}
