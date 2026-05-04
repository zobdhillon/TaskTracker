<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{

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
}
