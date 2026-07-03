<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MembersImportService
{
    private int $imported = 0;

    private int $skipped = 0;

    private array $skippedEmails = [];

    private array $errors = [];

    public function import(string $csvPath, string $disk = 'public'): array
    {
        $stream = Storage::disk($disk)->readStream($csvPath);
        if (! $stream) {
            return ['imported' => 0, 'skipped' => 0, 'skipped_emails' => [], 'errors' => ['Could not open file']];
        }

        $headers = fgetcsv($stream, escape: '\\');
        if (! $headers) {
            fclose($stream);

            return ['imported' => 0, 'skipped' => 0, 'skipped_emails' => [], 'errors' => ['Empty file']];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);

        if ($this->rowIsData($headers)) {
            $this->processRow($this->mapPositionalRow($headers));
        }

        while (($row = fgetcsv($stream, escape: '\\')) !== false) {
            $data = array_combine($headers, $row);
            if (! $data) {
                continue;
            }

            $this->processRow($data);
        }

        fclose($stream);

        Log::info('MembersImportService: import completed', [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'skipped_emails' => $this->skippedEmails,
            'errors' => $this->errors,
        ]);

        AuditLogService::log(
            action: 'members_imported',
            model: null,
            old: [],
            new: [
                'imported' => $this->imported,
                'skipped' => $this->skipped,
            ],
        );

        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'skipped_emails' => $this->skippedEmails,
            'errors' => $this->errors,
        ];
    }

    private function processRow(array $data): void
    {
        $email = trim((string) ($data['Email'] ?? $data['email'] ?? $data['EMAIL'] ?? ''));

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid or missing email: {$email}";

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->skipped++;
            $this->skippedEmails[] = $email;

            return;
        }

        $name = trim((string) ($data['Full Name'] ?? $data['full_name'] ?? $data['Name'] ?? $data['name'] ?? 'Member'));
        $membershipId = trim((string) ($data['MEMBERSHIP ID'] ?? $data['membership_id'] ?? $data['Membership ID'] ?? ''));
        $hijriDate = trim((string) ($data['Hijri Date'] ?? $data['hijri_date'] ?? $data['Hijri Date'] ?? ''));
        $submittedAt = $hijriDate ? $this->parseHijriDate($hijriDate) : now();

        try {
            DB::transaction(function () use ($email, $name, $membershipId, $submittedAt, $data) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'status' => 'onboarding',
                    'referral_code' => $this->generateReferralCode(),
                    'email_verified_at' => now(),
                ]);

                Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
                $user->assignRole('member');

                MemberProfile::create([
                    'user_id' => $user->id,
                    'display_name' => $name,
                    'nickname' => $this->valueOrNull($data, 'Nickname', 'nickname'),
                    'location_country' => $this->valueOrNull($data, 'Location', 'location', 'location_country') ?? 'Nigeria',
                    'age_group' => $this->normalizeAgeGroup($this->valueOrNull($data, 'Age Group', 'age_group')),
                    'marital_status' => $this->valueOrNull($data, 'Marital Status', 'marital_status'),
                    'phone' => $this->valueOrNull($data, 'Phone Number', 'phone', 'phone_number'),
                    'onboarding_status' => 'active',
                    'membership_id' => $membershipId ?: null,
                    'membership_type' => 'M',
                    'submitted_at' => $submittedAt,
                    'payment_status' => 'free',
                ]);

                $this->syncInterestsGoals($user, $data);
            });

            Password::sendResetLink(['email' => $email]);

            $this->imported++;
        } catch (\Throwable $e) {
            Log::error('MembersImportService: row failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            $this->errors[] = "Failed to import {$email}: {$e->getMessage()}";
        }
    }

    private function syncInterestsGoals(User $user, array $data): void
    {
        $interestNames = $this->parseList($data['Interests'] ?? $data['interests'] ?? '');
        if ($interestNames) {
            $interestIds = Interest::whereIn('name', $interestNames)->pluck('id');
            if ($interestIds->isNotEmpty()) {
                $user->interests()->sync($interestIds);
            }
        }

        $goalNames = $this->parseList($data['Goals'] ?? $data['goals'] ?? '');
        if ($goalNames) {
            $goalIds = Goal::whereIn('name', $goalNames)->pluck('id');
            if ($goalIds->isNotEmpty()) {
                $user->goals()->sync($goalIds);
            }
        }
    }

    private function parseList(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    private function valueOrNull(array $data, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeAgeGroup(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = strtolower(trim($value));

        return match (true) {
            str_contains($value, 'under 18') || str_contains($value, 'under18') => 'under_18',
            str_contains($value, '18') && (str_contains($value, '24') || str_contains($value, '21')) => '18_24',
            str_contains($value, '25') && str_contains($value, '34') => '25_34',
            str_contains($value, '35') && str_contains($value, '44') => '35_44',
            str_contains($value, '45') && str_contains($value, '54') => '45_54',
            str_contains($value, '55') || str_contains($value, '55+') => '55_above',
            default => null,
        };
    }

    private function parseHijriDate(string $value): ?Carbon
    {
        try {
            [$y, $m, $d] = array_pad(array_map('intval', explode('-', $value)), 3, 1);

            return app(HijriDateService::class)->convertToGregorian($y, $m, $d);
        } catch (\Throwable $e) {
            Log::warning('MembersImportService: could not parse hijri date', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return now();
        }
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    private function rowIsData(array $headers): bool
    {
        foreach ($headers as $cell) {
            if (str_contains($cell, '@')) {
                return true;
            }
        }

        return count($headers) > 0 && ! in_array(strtolower($headers[0]), ['membership id', 'full name', 'name', 'email'], true);
    }

    private function mapPositionalRow(array $row): array
    {
        $map = [
            'MEMBERSHIP ID' => fn ($v) => (string) $v,
            'Hijri Date' => fn ($v) => (string) $v,
            'Full Name' => fn ($v) => (string) $v,
            'Nickname' => fn ($v) => (string) $v,
            'Location' => fn ($v) => (string) $v,
            'Age Group' => fn ($v) => (string) $v,
            'Marital Status' => fn ($v) => (string) $v,
            'Interests' => fn ($v) => (string) $v,
            'Goals' => fn ($v) => (string) $v,
            'Email' => fn ($v) => (string) $v,
            'Phone Number' => fn ($v) => (string) $v,
        ];

        $keys = array_keys($map);
        $result = [];

        foreach ($keys as $i => $key) {
            $result[$key] = ($map[$key])(isset($row[$i]) ? (string) $row[$i] : '');
        }

        return $result;
    }
}
