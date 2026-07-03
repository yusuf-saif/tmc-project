<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\AuditLogService;
use App\Settings\SettingsRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $slug = 'settings';

    protected static string $view = 'filament.pages.settings-page';

    public array $settings = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        foreach (SettingsRegistry::all() as $key => $config) {
            $this->settings[$key] = Setting::get($key);
        }

        $this->form->fill($this->settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->buildFormSchema())
            ->statePath('settings');
    }

    protected function buildFormSchema(): array
    {
        $groups = SettingsRegistry::keysByGroup();
        $tabs = [];

        foreach (SettingsRegistry::groups() as $groupKey => $groupLabel) {
            $keys = $groups[$groupKey] ?? [];
            if (empty($keys)) {
                continue;
            }

            $fields = [];
            foreach ($keys as $key) {
                $field = $this->buildField($key);
                if ($field) {
                    $fields[] = $field;
                }
            }

            if (empty($fields)) {
                continue;
            }

            $tabs[] = Tab::make($groupLabel)
                ->schema($fields);
        }

        return [
            Tabs::make('settings')
                ->tabs($tabs)
                ->columnSpanFull(),
        ];
    }

    protected function buildField(string $key): ?Component
    {
        $type = SettingsRegistry::type($key);
        $label = SettingsRegistry::label($key);
        $description = SettingsRegistry::description($key);

        if ($type === null || $label === null) {
            return null;
        }

        $field = match ($type) {
            'int' => TextInput::make($key)
                ->label($label)
                ->numeric()
                ->integer()
                ->helperText($description),
            'string' => TextInput::make($key)
                ->label($label)
                ->maxLength(255)
                ->helperText($description),
            'text' => Textarea::make($key)
                ->label($label)
                ->rows(4)
                ->helperText($description),
            'bool' => Toggle::make($key)
                ->label($label)
                ->helperText($description),
            default => null,
        };

        return $field;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $updatedKeys = [];

        foreach ($data as $key => $value) {
            if (! SettingsRegistry::has($key)) {
                continue;
            }

            Setting::set($key, $value);
            $updatedKeys[] = $key;
        }

        AuditLogService::log('settings_updated', null, [], ['keys' => $updatedKeys]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
