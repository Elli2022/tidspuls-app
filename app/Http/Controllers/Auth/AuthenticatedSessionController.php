<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginApiRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function apiLogin(LoginApiRequest $request)
    {
        $credentials = $request->validated();

        $user = User::findForAuthenticationByPersonnummer($credentials['personnummer']);

        if ($user === null || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            return $this->errorResponse(
                'invalid_credentials',
                'Invalid credentials.',
                401
            );
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? 'api-client'
        )->plainTextToken;

        return $this->successResponse([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'personnummer' => $user->personnummer,
            ],
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()?->tokens()->delete();

        return $this->successResponse([
            'message' => 'Logged out successfully.',
        ]);
    }
}
