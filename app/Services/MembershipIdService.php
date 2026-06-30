<?php

namespace App\Services;

use App\Models\MembershipSerial;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipIdService
{
    public static function normalizeType(string $membershipType): string
    {
        return match ($membershipType) {
            'member', 'M' => 'M',
            'student_member', 'junior_member', 'SM' => 'SM',
            'exco', 'E' => 'E',
            default => 'M',
        };
    }

    public static function getTypeCode(string $membershipType): string
    {
        return self::normalizeType($membershipType);
    }

    public static function determineMembershipType(User $user): string
    {
        $profile = $user->memberProfile;

        if ($profile?->age_group === 'under_18') {
            return 'SM';
        }

        return 'M';
    }

    public static function getCurrentHijriYear(): int
    {
        return App::make(HijriDateService::class)->currentYear();
    }

    public static function generate(string $membershipType): array
    {
        $membershipType = self::normalizeType($membershipType);
        $hijriYear = self::getCurrentHijriYear();
        $typeCode = self::getTypeCode($membershipType);

        try {
            return DB::transaction(function () use ($membershipType, $hijriYear, $typeCode) {
                $counter = MembershipSerial::query()
                    ->where('membership_type', $membershipType)
                    ->where('hijri_year', $hijriYear)
                    ->lockForUpdate()
                    ->first();

                if (! $counter) {
                    $counter = MembershipSerial::query()->create([
                        'membership_type' => $membershipType,
                        'hijri_year' => $hijriYear,
                        'last_serial' => 0,
                    ]);
                }

                $counter->increment('last_serial');
                $serial = $counter->last_serial;

                $membershipId = sprintf('TMC-%s-%d-%03d', $typeCode, $hijriYear, $serial);

                return [
                    'membership_id' => $membershipId,
                    'membership_serial' => $serial,
                    'membership_hijri_year' => $hijriYear,
                    'membership_type' => $typeCode,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('MembershipIdService: generation failed', [
                'membership_type' => $membershipType,
                'hijri_year' => $hijriYear,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
