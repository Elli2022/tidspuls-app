<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('clocked_in_at');
            $table->timestamp('clocked_out_at')->nullable();
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'clocked_in_at']);
            $table->index(['user_id', 'clocked_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
