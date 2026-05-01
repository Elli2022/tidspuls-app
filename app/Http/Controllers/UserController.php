<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\ChangePasswordApiRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function changePassword(ChangePasswordApiRequest $request)
    {
        $user = $request->user();
        $payload = $request->validated();

        if ($user === null || ! Hash::check($payload['current_password'], $user->password)) {
            return $this->errorResponse(
                'invalid_current_password',
                'Current password does not match.',
                422
            );
        }

        $user->update([
            'password' => $payload['new_password'],
        ]);

        return $this->successResponse([
            'message' => 'Password changed successfully.',
        ]);
    }
}
