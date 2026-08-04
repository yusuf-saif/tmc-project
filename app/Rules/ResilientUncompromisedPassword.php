<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResilientUncompromisedPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        try {
            $hash = strtoupper(sha1($value));
            $response = Http::timeout(5)->get('https://api.pwnedpasswords.com/range/'.substr($hash, 0, 5));

            $suffix = strtoupper(substr($hash, 5));
            foreach (explode("\n", $response->body()) as $line) {
                [$candidate] = explode(':', trim($line));
                if ($candidate === $suffix) {
                    $fail('The :attribute has appeared in a data breach. Please choose a different password.');

                    return;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Skipped HIBP password check for '.$attribute.' due to unavailable service: '.$e->getMessage());
        }
    }
}
