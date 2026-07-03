<?php

namespace App\Services;

use Alkoumi\LaravelHijriDate\Hijri;
use Carbon\Carbon;

class HijriDateService
{
    const HIJRI_MONTHS = [
        'Muharram', 'Safar', 'Rabi\' al-Awwal', 'Rabi\' al-Thani',
        'Jumada al-Awwal', 'Jumada al-Thani', 'Rajab', 'Sha\'ban',
        'Ramadan', 'Shawwal', 'Dhu al-Qi\'dah', 'Dhu al-Hijjah',
    ];

    protected Hijri $hijri;

    public function __construct()
    {
        $this->hijri = new Hijri;
        $this->hijri->setLang('en');
    }

    public function nowHijri(): array
    {
        return $this->hijri->setFromGregorianDMY(now()->day, now()->month, now()->year);
    }

    public function currentYear(): int
    {
        return (int) $this->nowHijri()['year'];
    }

    public function currentMonth(): int
    {
        return (int) $this->nowHijri()['month'];
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
        $hijri = $this->hijri->setFromGregorianDMY($date->day, $date->month, $date->year);

        $replacements = [
            'd' => str_pad((string) $hijri['day'], 2, '0', STR_PAD_LEFT),
            'j' => (string) $hijri['day'],
            'M' => $this->monthName($hijri['month']),
            'F' => $this->monthName($hijri['month']),
            'm' => str_pad((string) $hijri['month'], 2, '0', STR_PAD_LEFT),
            'n' => (string) $hijri['month'],
            'Y' => (string) $hijri['year'],
            'y' => substr((string) $hijri['year'], -2),
            'D' => $date->format('D'),
            'l' => $date->format('l'),
            'H' => $date->format('H'),
            'i' => $date->format('i'),
            's' => $date->format('s'),
            'g' => $date->format('g'),
            'h' => $date->format('h'),
            'a' => $date->format('a'),
            'A' => $date->format('A'),
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

    public function addMonthsHijri(Carbon $from, int $months): Carbon
    {
        $hijri = $this->hijri->setFromGregorianDMY($from->day, $from->month, $from->year);

        $totalMonths = ($hijri['year'] * 12 + $hijri['month'] - 1) + $months;
        $newYear = (int) floor($totalMonths / 12);
        $newMonth = ($totalMonths % 12) + 1;

        $gregDate = Hijri::DateToGregorianFromDMY($hijri['day'], $newMonth, $newYear);

        return Carbon::parse($gregDate)->setTimeFrom($from);
    }

    public function approximateHijri(Carbon $date): array
    {
        return $this->hijri->setFromGregorianDMY($date->day, $date->month, $date->year);
    }

    public function convertToGregorian(int $year, int $month, int $day): Carbon
    {
        $gregDate = Hijri::DateToGregorianFromDMY($day, $month, $year);

        return Carbon::parse($gregDate);
    }
}
