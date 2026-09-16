<?php

namespace App\Console\Commands;

use App\Models\Badge;
use Illuminate\Console\Command;

class FixBadgeIconPaths extends Command
{
    protected $signature = 'badges:fix-icon-paths {--dry-run : Show which badges would be updated without changing anything}';

    protected $description = 'Normalize badge icon_path values from full URLs to relative storage keys';

    public function handle(): int
    {
        $badges = Badge::whereNotNull('icon_path')
            ->where('icon_path', '!=', '')
            ->get();

        if ($badges->isEmpty()) {
            $this->info('No badges with icon_path values found.');

            return Command::SUCCESS;
        }

        $needsFix = $badges->filter(fn (Badge $badge) => str_starts_with($badge->icon_path, 'http'));

        if ($needsFix->isEmpty()) {
            $this->info('All badge icon_path values are already relative keys. No fix needed.');
            $this->table(
                ['ID', 'Name', 'icon_path'],
                $badges->map(fn (Badge $b) => [$b->id, $b->name, $b->icon_path])
            );

            return Command::SUCCESS;
        }

        $this->line("Found {$needsFix->count()} badge(s) with full URLs in icon_path.");

        $rows = $needsFix->map(function (Badge $badge) {
            $parsed = parse_url($badge->icon_path);
            $relative = ltrim($parsed['path'] ?? $badge->icon_path, '/');

            return [
                $badge->id,
                $badge->name,
                $badge->icon_path,
                $relative,
            ];
        });

        $this->table(['ID', 'Name', 'Current icon_path', 'Would become'], $rows);

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes made.');

            return Command::SUCCESS;
        }

        foreach ($needsFix as $badge) {
            $parsed = parse_url($badge->icon_path);
            $relative = ltrim($parsed['path'] ?? $badge->icon_path, '/');
            $badge->update(['icon_path' => $relative]);
        }

        $this->info("Fixed {$needsFix->count()} badge icon_path value(s).");

        return Command::SUCCESS;
    }
}
