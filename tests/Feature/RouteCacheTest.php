<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RouteCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::call('route:clear');

        parent::tearDown();
    }

    public function test_route_cache_succeeds(): void
    {
        $code = Artisan::call('route:cache');

        $this->assertSame(0, $code);
    }
}
