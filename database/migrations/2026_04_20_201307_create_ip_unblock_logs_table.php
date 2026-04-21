<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ip_unblock_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->string('jail')->nullable();
            $table->boolean('was_blocked');
            $table->boolean('turnstile_valid');
            $table->boolean('unblocked');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index('ip');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_unblock_logs');
    }
};
