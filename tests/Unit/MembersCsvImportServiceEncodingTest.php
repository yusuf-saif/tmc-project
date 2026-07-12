<?php

namespace Tests\Unit;

use App\Services\MembersCsvImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MembersCsvImportServiceEncodingTest extends TestCase
{
    private MembersCsvImportService $service;

    private ReflectionMethod $toUtf8;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MembersCsvImportService;
        $this->toUtf8 = new ReflectionMethod(MembersCsvImportService::class, 'toUtf8');
    }

    public function test_converts_windows_1252_right_single_quote_to_apostrophe(): void
    {
        // 0x92 is the Windows-1252 right single quotation mark — invalid UTF-8
        $input = "Naja\x92atu";
        $result = $this->toUtf8->invoke($this->service, $input);

        $this->assertSame("Naja'atu", $result);
    }

    public function test_replaces_utf8_left_curly_quote_with_apostrophe(): void
    {
        // \xE2\x80\x98 is the UTF-8 left single quotation mark (U+2018)
        // This is valid UTF-8, so toUtf8 must still normalize it
        $input = "Sa\xE2\x80\x98ad";
        $result = $this->toUtf8->invoke($this->service, $input);

        $this->assertSame("Sa'ad", $result);
    }

    public function test_replaces_utf8_right_curly_quote_with_apostrophe(): void
    {
        // \xE2\x80\x99 is the UTF-8 right single quotation mark (U+2019)
        $input = "Sa\xE2\x80\x99ad";
        $result = $this->toUtf8->invoke($this->service, $input);

        $this->assertSame("Sa'ad", $result);
    }

    public function test_returns_valid_utf8_unchanged(): void
    {
        $input = "Naja'atu Abdulaziz";
        $result = $this->toUtf8->invoke($this->service, $input);

        $this->assertSame("Naja'atu Abdulaziz", $result);
    }

    public function test_returns_null_unchanged(): void
    {
        $result = $this->toUtf8->invoke($this->service, null);

        $this->assertNull($result);
    }

    public function test_returns_empty_string_unchanged(): void
    {
        $result = $this->toUtf8->invoke($this->service, '');

        $this->assertSame('', $result);
    }

    public function test_handles_mixed_cp1252_and_valid_utf8(): void
    {
        // String with a CP1252 byte in the middle of valid text
        $input = "Habiba Isa Mika\x92il";
        $result = $this->toUtf8->invoke($this->service, $input);

        $this->assertSame("Habiba Isa Mika'il", $result);
    }
}
