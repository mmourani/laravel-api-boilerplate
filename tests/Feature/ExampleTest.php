<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_api_health_check(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
            ]);
    }
}
