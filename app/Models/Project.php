<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s.u',
        'updated_at' => 'datetime:Y-m-d H:i:s.u',
        'deleted_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    /**
     * Get the user that owns the project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        
        // When soft deleting a project, cascade soft delete to associated tasks
        static::deleting(function ($project) {
            $project->tasks()->delete();
        });
        
        // When restoring a project, also restore its soft-deleted tasks
        static::restoring(function ($project) {
            $project->tasks()->onlyTrashed()->restore();
        });
    }

    /**
     * Scope a query to search for projects.
     * Implements cross-database compatible search:
     * - Uses fulltext search for MySQL
     * - Falls back to LIKE queries for SQLite and other databases
     *
     * @param Builder $query
     * @param string $search
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if (!$search) {
            return $query;
        }
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // Use fulltext search for MySQL
            $query->whereFullText(['title', 'description'], $search);
        } else {
            // For SQLite and other databases, use LIKE search with proper escaping
            $searchTerm = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm);
            });
        }
        
        return $query;
    }

    /**
     * Get the tasks for the project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

}
