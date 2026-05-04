<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->string('role', 32)->default(UserRole::Employee->value)->after('organization_id');
        });

        $defaultOrg = Organization::query()->create([
            'name' => 'Standardorganisation',
        ]);

        User::query()->whereNull('organization_id')->update([
            'organization_id' => $defaultOrg->id,
            'role' => UserRole::Employee->value,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('organizations');
    }
};
