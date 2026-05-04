<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    /**
     * Lista kollegor i samma organisation (admin eller chef).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->role, [UserRole::Admin, UserRole::Manager], true)) {
            return $this->errorResponse('forbidden', 'Du saknar behörighet att lista personal.', 403);
        }

        $members = $user->organization
            ? $user->organization->users()->orderBy('name')->get([
                'id', 'name', 'email', 'role', 'organization_id',
            ])
            : collect();

        return $this->successResponse([
            'members' => $members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role->value,
            ]),
        ]);
    }
}
