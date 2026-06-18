<?php

namespace App\Services;

use App\Models\MembershipSerial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $profile = $user->memberProfile ?? $user->profile;

        if ($profile?->age_group === 'under_18') {
            return 'SM';
        }

        return 'M';
    }

    public static function getCurrentHijriYear(): int
    {
        if (class_exists(\IntlCalendar::class)) {
            $calendar = \IntlCalendar::createInstance(null, 'ar_SA@calendar=islamic');

            return $calendar->get(\IntlCalendar::FIELD_YEAR);
        }

        return (int) floor(((int) date('Y') - 622) * (33 / 32));
    }

    public static function generate(string $membershipType): array
    {
        $membershipType = self::normalizeType($membershipType);
        $hijriYear = self::getCurrentHijriYear();
        $typeCode = self::getTypeCode($membershipType);

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
    }
}
