<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    public function test_request_id_header_is_added_when_missing(): void
    {
        $response = $this->get('/healthz');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_existing_request_id_header_is_preserved(): void
    {
        $response = $this->withHeaders([
            'X-Request-ID' => 'req-test-123',
        ])->get('/healthz');

        $response->assertOk();
        $response->assertHeader('X-Request-ID', 'req-test-123');
    }
}
