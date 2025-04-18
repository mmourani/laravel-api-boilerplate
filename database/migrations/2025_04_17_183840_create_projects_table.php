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
        Schema::create('projects', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            
            // Precision timestamps for Laravel 12
            $table->timestamps(6);
            
            // Soft deletes for better data management
            $table->softDeletes('deleted_at', 6);
            
            // Add indexes for search and filtering
            $table->index('title');
            $table->index(['created_at', 'user_id']);
            
            // Only add full-text indexes once, and only for MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['title', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
