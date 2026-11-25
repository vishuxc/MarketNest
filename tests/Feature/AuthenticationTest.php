<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_both_authentication_forms(): void
    {
        $this->get(route('register'))->assertOk()->assertSeeText('Create your account');
        $this->get(route('login'))->assertOk()->assertSeeText('Log in to MarketNest');
    }

    public function test_registration_leaves_user_signed_out_until_manual_login(): void
    {
        $message = 'Account created successfully! Please log in to continue.';

        $this->post(route('register.store'), [
            'name' => 'Local Seller',
            'email' => 'Seller@example.com',
            'password' => 'local-password',
            'password_confirmation' => 'local-password',
        ])->assertRedirectToRoute('login')->assertSessionHas('status', $message);

        $user = User::where('email', 'seller@example.com')->sole();

        $this->assertGuest();
        $this->assertNotSame('local-password', $user->password);
        $this->assertTrue(Hash::check('local-password', $user->password));
        $this->get(route('login'))->assertOk()->assertSeeText($message);

        $this->post(route('login.store'), [
            'email' => 'seller@example.com',
            'password' => 'local-password',
        ])->assertRedirectToRoute('home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_is_rejected_and_safe_input_is_preserved(): void
    {
        User::factory()->create(['email' => 'seller@example.com']);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Local Seller',
            'email' => 'SELLER@example.com',
            'password' => 'local-password',
            'password_confirmation' => 'local-password',
        ])->assertRedirectToRoute('register')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('name', 'Local Seller')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_registration_data_is_rejected(): void
    {
        $this->post(route('register.store'), [
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Local Seller',
            'email' => 'seller@example.com',
            'password' => 'local-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_authenticates_user_and_honors_remember_me(): void
    {
        $user = User::factory()->create(['email' => 'seller@example.com']);
        $initialSessionId = session()->getId();

        $this->post(route('login.store'), [
            'email' => 'Seller@example.com',
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirectToRoute('home')->assertCookie(auth()->guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($initialSessionId, session()->getId());
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirectToRoute('login')->assertSessionHasErrors([
            'email' => 'The email address or password is incorrect.',
        ]);

        $this->assertGuest();
    }

    public function test_guest_returning_from_login_reaches_the_intended_sell_page(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->get(route('listings.create'))->assertRedirectToRoute('login');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('listings.create');
        $this->get(route('listings.create'))->assertOk()->assertSeeText('Publish a listing');
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $user = User::factory()->create(['email' => 'seller@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors(['email' => 'The email address or password is incorrect.']);
        }

        $this->post(route('login.store'), [
            'email' => 'Seller@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $this->actingAs(User::factory()->create())->withSession([
            'private_marker' => true,
            '_token' => 'previous-token',
        ]);
        $initialSessionId = session()->getId();

        $this->post(route('logout'))->assertRedirectToRoute('home')
            ->assertSessionMissing('private_marker')->assertSessionHas('status');

        $this->assertGuest();
        $this->assertNotSame($initialSessionId, session()->getId());
        $this->assertNotSame('previous-token', session()->token());
    }

    public function test_guests_cannot_log_out(): void
    {
        $this->post(route('logout'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_users_cannot_open_or_submit_guest_forms(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))->assertRedirectToRoute('home');
        $this->get(route('register'))->assertRedirectToRoute('home');
        $this->post(route('login.store'))->assertRedirectToRoute('home');
        $this->post(route('register.store'))->assertRedirectToRoute('home');
    }
}
