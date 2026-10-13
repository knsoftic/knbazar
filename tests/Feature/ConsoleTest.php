<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_admin_command(): void
    {
        $this->artisan('marketplace:create-admin', ['email' => 'owner@example.com', '--password' => 'secret123'])
            ->assertExitCode(0);

        $this->assertTrue(User::query()->where('email', 'owner@example.com')->firstOrFail()->isSuperAdmin());

        $this->artisan('marketplace:create-admin', ['email' => 'owner@example.com', '--password' => 'short'])
            ->assertExitCode(1);
    }
}
