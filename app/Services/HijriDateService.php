<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HijriDateService
{
    const HIJRI_MONTHS = [
        'Muharram', 'Safar', 'Rabi\' al-Awwal', 'Rabi\' al-Thani',
        'Jumada al-Awwal', 'Jumada al-Thani', 'Rajab', 'Sha\'ban',
        'Ramadan', 'Shawwal', 'Dhu al-Qi\'dah', 'Dhu al-Hijjah',
    ];

    public function nowHijri(): array
    {
        $cal = $this->createCalendar();
        if ($cal) {
            return [
                'year' => $cal->get(\IntlCalendar::FIELD_EXTENDED_YEAR),
                'month' => $cal->get(\IntlCalendar::FIELD_MONTH) + 1,
                'day' => $cal->get(\IntlCalendar::FIELD_DAY_OF_MONTH),
            ];
        }

        return $this->approximateHijri(now());
    }

    public function currentYear(): int
    {
        return $this->nowHijri()['year'];
    }

    public function currentMonth(): int
    {
        return $this->nowHijri()['month'];
    }

    public function monthName(int $month): string
    {
        return self::HIJRI_MONTHS[$month - 1] ?? 'Unknown';
    }

    public function currentMonthName(): string
    {
        return $this->monthName($this->currentMonth());
    }

    public function formatHijriDate(?Carbon $date = null, string $format = 'd M Y'): string
    {
        $date = $date ?? now();
        $hijri = $this->approximateHijri($date);

        $replacements = [
            'd' => str_pad((string) $hijri['day'], 2, '0', STR_PAD_LEFT),
            'j' => (string) $hijri['day'],
            'M' => $this->monthName($hijri['month']),
            'F' => $this->monthName($hijri['month']),
            'm' => str_pad((string) $hijri['month'], 2, '0', STR_PAD_LEFT),
            'n' => (string) $hijri['month'],
            'Y' => (string) $hijri['year'],
            'y' => substr((string) $hijri['year'], -2),
        ];

        $result = '';
        $escaped = false;
        for ($i = 0; $i < strlen($format); $i++) {
            $char = $format[$i];
            if ($char === '\\' && ! $escaped) {
                $escaped = true;

                continue;
            }
            if ($escaped) {
                $result .= $char;
                $escaped = false;

                continue;
            }
            $result .= $replacements[$char] ?? $char;
        }

        return $result;
    }

    public function convertToGregorian(int $year, int $month, int $day): Carbon
    {
        try {
            if (class_exists(\IntlCalendar::class)) {
                $cal = \IntlCalendar::createInstance(null, 'ar_SA@calendar=islamic');
                $cal->set($year, $month - 1, $day);
                $gregorianYear = $cal->get(\IntlCalendar::FIELD_EXTENDED_YEAR);
                $gregorianMonth = $cal->get(\IntlCalendar::FIELD_MONTH) + 1;
                $gregorianDay = $cal->get(\IntlCalendar::FIELD_DAY_OF_MONTH);

                $gregorianCal = \IntlCalendar::createInstance(null, 'gregorian');
                $gregorianCal->set($gregorianYear, $gregorianMonth - 1, $gregorianDay);

                return Carbon::create(
                    $gregorianCal->get(\IntlCalendar::FIELD_YEAR),
                    $gregorianCal->get(\IntlCalendar::FIELD_MONTH) + 1,
                    $gregorianCal->get(\IntlCalendar::FIELD_DAY_OF_MONTH),
                    0, 0, 0
                );
            }
        } catch (\Throwable $e) {
            Log::warning('HijriDateService: IntlCalendar conversion failed, using approximation', [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->convertApproximate($year, $month, $day);
    }

    public function addMonthsHijri(Carbon $from, int $months): Carbon
    {
        $hijri = $this->approximateHijri($from);
        $totalMonths = ($hijri['year'] * 12 + $hijri['month'] - 1) + $months;
        $newYear = (int) floor($totalMonths / 12);
        $newMonth = ($totalMonths % 12) + 1;

        return $this->convertToGregorian($newYear, $newMonth, $hijri['day']);
    }

    public function monthsBetween(Carbon $from, Carbon $to): int
    {
        $fromH = $this->approximateHijri($from);
        $toH = $this->approximateHijri($to);

        $fromTotal = $fromH['year'] * 12 + $fromH['month'] - 1;
        $toTotal = $toH['year'] * 12 + $toH['month'] - 1;

        return max(0, $toTotal - $fromTotal);
    }

    public function addYearHijri(Carbon $from): Carbon
    {
        return $this->addMonthsHijri($from, 12);
    }

    public function addQuarterHijri(Carbon $from): Carbon
    {
        return $this->addMonthsHijri($from, 3);
    }

    public function daysInMonth(int $year, int $month): int
    {
        $start = $this->convertToGregorian($year, $month, 1);
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;
        $end = $this->convertToGregorian($nextYear, $nextMonth, 1);

        return (int) $start->diffInDays($end);
    }

    public function isValidHijri(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        return $day <= $this->daysInMonth($year, $month);
    }

    protected function createCalendar(): ?\IntlCalendar
    {
        try {
            return \IntlCalendar::createInstance(null, 'ar_SA@calendar=islamic');
        } catch (\Throwable $e) {
            Log::warning('IntlCalendar not available, using Hijri approximation', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function approximateHijri(Carbon $date): array
    {
        $gregYear = (int) $date->format('Y');
        $gregMonth = (int) $date->format('n');
        $gregDay = (int) $date->format('j');

        $dayOfYear = (int) $date->format('z') + 1;

        $hijriYear = (int) floor(($gregYear - 622) * 33 / 32);

        $approxDays = ($gregYear - 622) * 354 + $dayOfYear;
        $hijriMonth = (int) (($approxDays % 354) / 29.5) + 1;
        $hijriDay = (int) (($approxDays % 354) % 29.5) + 1;

        if ($hijriMonth > 12) {
            $hijriMonth = 12;
            $hijriDay = min($hijriDay, 30);
        }

        return [
            'year' => max(1, $hijriYear),
            'month' => max(1, min(12, $hijriMonth)),
            'day' => max(1, min(30, $hijriDay)),
        ];
    }

    protected function convertApproximate(int $year, int $month, int $day): Carbon
    {
        $gregYear = (int) floor(($year * 32 / 33) + 622);
        $approxDayOfYear = ($month - 1) * 29.5 + $day;

        return Carbon::createFromFormat('Y-z', "{$gregYear}-0")->addDays((int) $approxDayOfYear)->startOfDay();
    }
}
