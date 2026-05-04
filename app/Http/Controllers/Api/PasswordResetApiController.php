<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordApiRequest;
use App\Http\Requests\Api\ResetPasswordApiRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetApiController extends Controller
{
    public function forgot(ForgotPasswordApiRequest $request)
    {
        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_THROTTLED) {
            return $this->errorResponse(
                'password_reset_throttled',
                'Vänta en stund innan du ber om ny länk igen.',
                429
            );
        }

        return $this->successResponse([
            'message' => 'Om det finns ett konto för den här e-postadressen har vi skickat en länk för att återställa lösenordet.',
        ]);
    }

    public function reset(ResetPasswordApiRequest $request)
    {
        $request->validated();

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::reset(
            $credentials,
            function ($user) use ($credentials) {
                $user->forceFill([
                    'password' => $credentials['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse([
                'message' => 'Lösenordet är uppdaterat. Du kan logga in.',
            ]);
        }

        if (in_array($status, [Password::INVALID_TOKEN, Password::INVALID_USER], true)) {
            return $this->errorResponse(
                'invalid_reset_token',
                'Länken är ogiltig eller har gått ut. Begär en ny återställningslänk på inloggningssidan.',
                422
            );
        }

        return $this->errorResponse(
            'password_reset_failed',
            'Kunde inte återställa lösenordet.',
            422
        );
    }
}
