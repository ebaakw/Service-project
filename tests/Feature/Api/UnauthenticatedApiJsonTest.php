<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class UnauthenticatedApiJsonTest extends TestCase
{
    public function test_unauthenticated_customer_api_me_returns_json_401(): void
    {
        $response = $this->getJson('/api/v1/customer/me');

        $response->assertUnauthorized();
        $this->assertNotEmpty($response->headers->get('content-type'));
        $this->assertStringContainsString('json', (string) $response->headers->get('content-type'));
    }
}
