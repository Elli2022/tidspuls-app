<?php

use App\Models\User;
use App\Support\PersonnummerNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (User::query()->cursor() as $user) {
            $canonical = PersonnummerNormalizer::canonical($user->personnummer);
            if ($canonical === null || $canonical === $user->personnummer) {
                continue;
            }

            $conflict = DB::table('users')
                ->where('personnummer', $canonical)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($conflict) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'personnummer' => $canonical,
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
