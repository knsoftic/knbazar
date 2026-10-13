<?php

namespace Tests\Feature;

use App\Enums\PublisherStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->isCustomer());
        $this->assertNotEmpty($user->username);
    }

    public function test_registration_validates_input(): void
    {
        $this->post('/register', ['name' => 'X', 'email' => 'not-an-email', 'password' => 'short', 'password_confirmation' => 'other'])
            ->assertSessionHasErrors(['name', 'email', 'password', 'terms']);

        $this->assertGuest();
    }

    public function test_registration_can_be_disabled(): void
    {
        $this->setting(['registration_enabled' => false]);

        $this->get('/register')->assertRedirect(route('login'));
        $this->post('/register', [
            'name' => 'Test', 'email' => 'x@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ])->assertForbidden();
    }

    public function test_publisher_registration_requires_approval_when_configured(): void
    {
        $this->setting(['publisher_require_approval' => true]);

        $this->post('/register/publisher', $this->publisherPayload())->assertRedirect(route('publisher.dashboard'));

        $user = User::query()->where('email', 'studio@example.com')->firstOrFail();
        $this->assertTrue($user->isPublisher());
        $this->assertSame(PublisherStatus::Pending, $user->publisherProfile->status);
        $this->assertFalse($user->canSubmitProducts());

        // Pending publishers cannot reach product creation.
        $this->get('/publisher/products/create')->assertRedirect(route('publisher.dashboard'));
    }

    public function test_publisher_registration_without_approval_is_active_immediately(): void
    {
        $this->setting(['publisher_require_approval' => false]);

        $this->post('/register/publisher', $this->publisherPayload());

        $user = User::query()->where('email', 'studio@example.com')->firstOrFail();
        $this->assertSame(PublisherStatus::Approved, $user->publisherProfile->status);
        $this->assertTrue($user->canSubmitProducts());
        $this->get('/publisher/products/create')->assertOk();
    }

    public function test_customer_can_upgrade_to_publisher(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post('/register/publisher', [
            'display_name' => 'Upgraded Studio',
            'username' => 'upgraded',
            'phone' => '+92 300 0000000',
            'country' => 'Pakistan',
            'guidelines' => '1',
        ])->assertRedirect(route('publisher.dashboard'));

        $this->assertTrue($customer->fresh()->isPublisher());
    }

    public function test_reserved_usernames_are_rejected(): void
    {
        $this->post('/register/publisher', ['username' => 'products'] + $this->publisherPayload())
            ->assertSessionHasErrors('username');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->customer()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_redirects_by_role(): void
    {
        $admin = $this->superAdmin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_fails_and_is_rate_limited(): void
    {
        $user = User::factory()->customer()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->customer()->suspended()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user)->get('/account')->assertOk();

        $user->forceFill(['status' => 'suspended'])->save();

        $this->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_link_and_reset(): void
    {
        $user = User::factory()->customer()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success'); // no enumeration

        $token = app('auth.password.broker')->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'newpass123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_and_password_update(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->put('/account/profile', ['name' => 'Renamed', 'email' => $user->email, 'phone' => '+92 300 1111111'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $user->fresh()->name);

        $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'another123', 'password_confirmation' => 'another123'])
            ->assertSessionHasErrors('current_password', null, 'password');

        $this->put('/account/password', ['current_password' => 'password', 'password' => 'another123', 'password_confirmation' => 'another123'])
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, string>
     */
    protected function publisherPayload(): array
    {
        return [
            'name' => 'Studio Owner',
            'email' => 'studio@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'display_name' => 'Studio One',
            'username' => 'studioone',
            'phone' => '+92 300 1234567',
            'country' => 'Pakistan',
            'guidelines' => '1',
        ];
    }
}
