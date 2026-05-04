<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('approval_status', 32)->default('draft')->after('note');
            $table->timestamp('submitted_at')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');

            $table->index(['approval_status', 'submitted_at']);
        });

        DB::table('time_entries')->update([
            'approval_status' => 'approved',
            'approved_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['approval_status', 'submitted_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approval_status',
                'submitted_at',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
