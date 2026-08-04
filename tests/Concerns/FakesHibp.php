<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

trait FakesHibp
{
    protected function fakeHibpWithNoBreach(): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 200),
        ]);
    }

    protected function resetHibpFakes(): void
    {
        Http::swap(new Factory);
    }
}
