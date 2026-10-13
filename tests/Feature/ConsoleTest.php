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
        $this->artisan('marketplace:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner', '--password' => 'secret123'])
            ->assertExitCode(0);

        $this->assertTrue(User::query()->where('email', 'owner@example.com')->firstOrFail()->isSuperAdmin());

        $this->artisan('marketplace:create-admin', ['email' => 'other@example.com', '--name' => 'Other', '--password' => 'short'])
            ->assertExitCode(1);
    }

    public function test_create_admin_prompts_and_can_skip_when_admin_exists(): void
    {
        $this->artisan('marketplace:create-admin')
            ->expectsQuestion('Super Admin email', 'prompted@example.com')
            ->expectsQuestion('Super Admin name', 'Prompted')
            ->expectsQuestion('Password (min. 8 characters, letters and numbers)', 'secret123')
            ->assertExitCode(0);

        $this->assertTrue(User::query()->where('email', 'prompted@example.com')->firstOrFail()->isSuperAdmin());

        $this->artisan('marketplace:create-admin', ['--skip-if-exists' => true])
            ->expectsOutputToContain('already exists')
            ->assertExitCode(0);

        $this->assertSame(1, User::query()->count());
    }
}
