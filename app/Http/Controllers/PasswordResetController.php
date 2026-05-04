<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use SensitiveParameter;

// use Illuminate\Http\Request;

class PasswordResetController
{
    public function showPasswordResetRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetEmail(SendPasswordResetEmailRequest $request)
    {
        $email = (string) $request->input('email');

        $status = Password::sendResetLink(['email' => $email]);

        return back()->with('status', 'if the email exists, a password reset link has been sent');
    }

    public function showPasswordResetForm(#[SensitiveParameter] string $token, Request $request)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->input('email')]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $requestData = $request->validated();

        $status = Password::reset(
            $requestData,
            function (User $user, #[SensitiveParameter] string $newPassword) {
                $user->password = Hash::make($newPassword);
                $user->remember_token = Str::random(60);
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        logger()->debug('Password reset failed', ['status' => $status, 'email' => $requestData['email'] ?? null]);

        return back()->withInput($request->only('email'))->withErrors(['email' => 'Failed to reset your password.']);
    }
}
