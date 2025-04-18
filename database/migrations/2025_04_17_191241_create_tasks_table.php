<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('done')->default(false)->index();
            $table->string('priority', 10)->nullable();
            $table->date('due_date')->nullable()->index();
            
            // Precision timestamps for Laravel 12
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            
            // Add proper indexes for filtering
            $table->index(['project_id', 'done']);
            $table->index(['project_id', 'priority']);
            $table->index(['project_id', 'due_date']);
            
            // Only add full-text indexes for MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText('title');
            }
        });
        
        // Add database-specific constraints
        try {
            if (config('database.default') === 'mysql') {
                DB::statement("ALTER TABLE `tasks` ADD CONSTRAINT `tasks_priority_check` 
                    CHECK (`priority` IN ('low', 'medium', 'high') OR `priority` IS NULL)");
            } else {
                // SQLite constraint
                DB::statement("ALTER TABLE tasks ADD CHECK (
                    priority IN ('low', 'medium', 'high') OR priority IS NULL
                )");
            }
        } catch (\Exception $e) {
            // Log error but don't fail migration if constraint can't be added
            logger()->warning('Failed to add check constraint: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
