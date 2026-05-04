<?php

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\RegisterUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterUserActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_is_created_with_correct_attributes(): void
    {
        $registerUser = app(RegisterUser::class);

        $user = $registerUser->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    #[Test]
    public function user_is_persisted_to_database(): void
    {
        $registerUser = app(RegisterUser::class);

        $user = $registerUser->execute([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    #[Test]
    public function registered_event_is_dispatched(): void
    {
        Event::fake();
        $registerUser = app(RegisterUser::class);

        $registerUser->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        Event::assertDispatched(Registered::class);
    }

    #[Test]
    public function returns_created_user(): void
    {
        $registerUser = app(RegisterUser::class);

        $user = $registerUser->execute([
            'name' => 'Return Test',
            'email' => 'return@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotNull($user->id);
        $this->assertInstanceOf(User::class, $user);
    }
}
