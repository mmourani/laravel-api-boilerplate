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
        // Create users table first
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name')->index();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at', 6)->nullable();
            $table->string('password');
            $table->rememberToken();
            // Add precision to timestamps for Laravel 12
            $table->timestamps(6);
            
            // Only add full-text indexes once, and only for MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['name', 'email']);
            }
        });

        // Create password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at', 6)->nullable();
            // Add index on token for faster lookups
            $table->index('token');
        });

        // Create sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            // Add index for faster session queries
            $table->index(['user_id', 'last_activity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order to handle dependencies
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
