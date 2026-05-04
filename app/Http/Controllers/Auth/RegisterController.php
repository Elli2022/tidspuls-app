<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterApiRequest;
use App\Models\Organization;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(RegisterApiRequest $request)
    {
        $payload = $request->validated();

        $organizationName = $payload['organization_name'] ?? ($payload['name'].' — organisation');
        unset($payload['organization_name']);

        $organization = Organization::query()->create([
            'name' => $organizationName,
        ]);

        $user = User::query()->create([
            ...$payload,
            'organization_id' => $organization->id,
            'role' => UserRole::Admin,
        ]);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'personnummer' => $user->personnummer,
                'role' => $user->role->value,
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ],
            ],
        ], 201);
    }
}
