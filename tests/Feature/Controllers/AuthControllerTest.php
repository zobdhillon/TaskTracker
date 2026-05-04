<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function registering_dispatches_registered_event(): void
    {
        Event::fake();

        $password = Str::password();

        $response = $this->post(route('register.post'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        Event::assertDispatched(Registered::class);
    }

    #[Test]
    public function registered_user_is_created_in_database(): void
    {
        $password = Str::password();
        $email = 'newuser@example.com';

        $this->post(route('register.post'), [
            'name' => 'Test User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'Test User',
        ]);
    }

    #[Test]
    public function registration_redirects_to_dashboard_after_success(): void
    {
        $password = Str::password();

        $response = $this->post(route('register.post'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect();
    }

    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'missing name' => [
                ['name' => '', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'password'],
                'name'
            ],
            'missing email' => [
                ['name' => 'John', 'email' => '', 'password' => 'password', 'password_confirmation' => 'password'],
                'email'
            ],
            'invalid email' => [
                ['name' => 'John', 'email' => 'not-an-email', 'password' => 'password', 'password_confirmation' => 'password'],
                'email'
            ],
            'missing password' => [
                ['name' => 'John', 'email' => 'test@example.com', 'password' => '', 'password_confirmation' => ''],
                'password'
            ],
            'password mismatch' => [
                ['name' => 'John', 'email' => 'test@example.com', 'password' => 'password123', 'password_confirmation' => 'different'],
                'password'
            ],
            'password too short' => [
                ['name' => 'John', 'email' => 'test@example.com', 'password' => 'short', 'password_confirmation' => 'short'],
                'password'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidRegistrationDataProvider')]
    public function registration_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $response = $this->post(route('register.post'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $password = Str::password();
        $response = $this->post(route('register.post'), [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertInvalid('email');
    }

    #[Test]
    public function password_reset_email_is_sent(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirectBackWithoutErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function password_reset_shows_message_for_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirectBackWithoutErrors();
        Notification::assertNothingSent();
    }

    #[Test]
    public function password_reset_requires_valid_email_format(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'not-an-email',
        ]);

        $response->assertInvalid('email');
    }

    #[Test]
    public function password_reset_requires_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => '',
        ]);

        $response->assertInvalid('email');
    }

    #[Test]
    public function user_can_view_password_reset_form_without_token(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
    }

    #[Test]
    public function user_can_view_password_reset_form_with_token(): void
    {
        $token = 'dummy-token';
        $email = 'test@example.com';

        $response = $this->get(route('password.reset', ['token' => $token, 'email' => $email]));

        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
        $response->assertViewHas('token', $token);
    }

    #[Test]
    public function password_reset_with_invalid_token_fails(): void
    {
        $user = User::factory()->create();
        $password = Str::password();

        $response = $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function password_reset_fails_with_mismatched_passwords(): void
    {
        $response = $this->post(route('password.store'), [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertInvalid('password');
    }

    #[Test]
    public function password_reset_requires_all_fields(): void
    {
        $response = $this->post(route('password.store'), [
            'token' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertInvalid(['token', 'email', 'password']);
    }
}
