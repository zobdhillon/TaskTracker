<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RegisterUser;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Redirector $redirector,
        private readonly UrlGenerator $url,
        private readonly Factory $view
    ) {}

    public function showLoginForm(): View
    {
        return $this->view->make('auth.login');
    }

    public function showRegisterForm(): View
    {
        return $this->view->make('auth.register');
    }

    public function register(RegisterRequest $request, RegisterUser $registerUser)
    {

        $user = $registerUser->execute($request->validated());

        $this->auth->login($user);

        return $this->redirector->intended($this->url->route('dashboard', absolute: false));
    }

    public function login(LoginRequest $request)
    {

        if ($this->auth->attempt($request->only(['email', 'password']), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirector->intended(route('dashboard', absolute: false));
        }

        return $this->redirector->back()
            ->withInput($request->except('password'))
            ->withErrors(['email' => 'The provided credentials are incorrect.']);
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirector->to('/');
    }
}
