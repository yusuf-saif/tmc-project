<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MembersCsvImportService
{
    private int $imported = 0;

    private int $skipped = 0;

    private array $skippedEmails = [];

    private array $errors = [];

    const HIJRI_MONTH_MAP = [
        'muharram' => 1,
        'safar' => 2,
        'rabi\' al-awwal' => 3,
        'rabi\' al-thani' => 4,
        'rabi al-awwal' => 3,
        'rabi al-thani' => 4,
        'jumada al-awwal' => 5,
        'jumada al-thani' => 6,
        'jumada al-awwal' => 5,
        'jumada al-thani' => 6,
        'rajab' => 7,
        'sha\'ban' => 8,
        'shaban' => 8,
        'ramadan' => 9,
        'shawwal' => 10,
        'dhu al-qi\'dah' => 11,
        'dhu al-qidah' => 11,
        'dhu al-hijjah' => 12,
        'dhul hijjah' => 12,
    ];

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
        $headers = $this->normaliseHeaders($headers);

        while (($row = fgetcsv($stream, escape: '\\')) !== false) {
            $data = array_combine($headers, $row);
            if (! $data) {
                continue;
            }

            $this->processRow($data);
        }

        fclose($stream);

        Log::info('MembersCsvImportService: import completed', [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'skipped_emails' => $this->skippedEmails,
            'errors' => $this->errors,
        ]);

        AuditLogService::log(
            action: 'csv_members_imported',
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

    private function normaliseHeaders(array $headers): array
    {
        $map = [
            'membership_id' => 'membership_id',
            'membership id' => 'membership_id',
            'member_id' => 'membership_id',
            'member id' => 'membership_id',
            'hijri_date' => 'hijri_date',
            'hijri date' => 'hijri_date',
            'hijri' => 'hijri_date',
            'name' => 'name',
            'full name' => 'name',
            'nickname' => 'nickname',
            'nick name' => 'nickname',
            'email' => 'email',
        ];

        return array_map(fn ($h) => $map[strtolower($h)] ?? $h, $headers);
    }

    private function processRow(array $data): void
    {
        $membershipId = trim((string) ($data['membership_id'] ?? ''));
        $hijriDateStr = trim((string) ($data['hijri_date'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $nickname = trim((string) ($data['nickname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if (! $membershipId || ! $name || ! $email) {
            $this->errors[] = 'Row skipped: missing required fields';

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email: {$email}";

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->skipped++;
            $this->skippedEmails[] = $email;

            return;
        }

        if (User::where('member_id', $membershipId)->exists()) {
            $this->skipped++;
            $this->skippedEmails[] = $email;

            return;
        }

        $membershipType = $this->parseMembershipType($membershipId);
        $hijriDate = $hijriDateStr ? $this->parseHijriDateString($hijriDateStr) : null;

        try {
            $user = null;

            DB::transaction(function () use ($email, $name, $nickname, $membershipId, $membershipType, $hijriDate, &$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'member_id' => $membershipId,
                    'password' => Hash::make(Str::random(32)),
                    'status' => 'pending_onboarding',
                    'email_verified_at' => null,
                ]);

                Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
                $user->assignRole('member');

                MemberProfile::create([
                    'user_id' => $user->id,
                    'membership_id' => $membershipId,
                    'membership_type' => $membershipType,
                    'display_name' => $nickname ?: $name,
                    'hijri_join_date' => $hijriDate,
                    'payment_status' => 'free',
                    'onboarding_status' => 'pending_onboarding',
                ]);
            });

            $token = Password::broker()->createToken($user);
            $user->notify(new OnboardingInvitationNotification($token, $membershipId));

            $this->imported++;
        } catch (\Throwable $e) {
            Log::error('MembersCsvImportService: row failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            $this->errors[] = "Failed to import {$email}: {$e->getMessage()}";
        }
    }

    private function parseMembershipType(string $membershipId): string
    {
        $parts = explode('-', $membershipId);

        return $parts[1] ?? 'M';
    }

    private function parseHijriDateString(string $value): ?\Carbon\Carbon
    {
        try {
            $parts = preg_split('/[\s\-]+/', trim($value));
            if (count($parts) < 3) {
                return null;
            }

            $day = (int) array_shift($parts);
            $year = (int) array_pop($parts);
            $monthName = strtolower(implode(' ', $parts));

            $month = self::HIJRI_MONTH_MAP[$monthName] ?? null;
            if (! $month) {
                return null;
            }

            return app(HijriDateService::class)->convertToGregorian($year, $month, $day);
        } catch (\Throwable $e) {
            Log::warning('MembersCsvImportService: could not parse hijri date', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
