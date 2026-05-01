<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterApiRequest;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(RegisterApiRequest $request)
    {
        $payload = $request->validated();

        $user = User::create($payload);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'personnummer' => $user->personnummer,
            ],
        ], 201);
    }
}
