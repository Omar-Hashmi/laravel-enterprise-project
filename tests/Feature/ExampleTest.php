<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_sent_to_the_login_screen(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('login');
    }
}
