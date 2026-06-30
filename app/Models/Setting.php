<?php

namespace App\Models;

use App\Settings\SettingsRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'description',
        'updated_by',
        'updated_at',
    ];

    public static function getValue(string $key, string $default = ''): string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = static::query()->where('key', $key)->value('value');

        if ($raw === null) {
            $value = $default ?? SettingsRegistry::default($key);
        } else {
            $value = $raw;
        }

        return SettingsRegistry::cast($key, $value);
    }

    public static function set(string $key, mixed $value, ?string $description = null, ?int $updatedBy = null): self
    {
        if (! SettingsRegistry::has($key)) {
            throw new \InvalidArgumentException("Unknown setting key: {$key}");
        }

        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description ?? SettingsRegistry::description($key),
                'updated_by' => $updatedBy ?? auth()->id(),
                'updated_at' => now(),
            ],
        );
    }
}
