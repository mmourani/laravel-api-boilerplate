<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task model representing a project task.
 * 
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property boolean $done
 * @property string|null $priority
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \App\Models\Project $project
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'project_id',
        'done',
        'priority',
        'due_date',
    ];
    
    /**
     * Ensure all necessary attributes are visible in API responses.
     * 
     * @var array<int, string>
     */
    protected $hidden = [];
    
    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        
        // No cascading deletes needed for Task model as it doesn't have child relationships
        
        // When creating a task, check if its project is soft-deleted
        static::creating(function ($task) {
            // If the task has a project_id, check if that project is soft-deleted
            if ($task->project_id) {
                $project = Project::withTrashed()->find($task->project_id);
                
                // If the project exists and is soft-deleted, soft-delete this task too
                if ($project && $project->trashed()) {
                    $task->deleted_at = now();
                }
            }
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'done' => 'boolean',
        'due_date' => 'datetime:Y-m-d H:i:s.u',
        'created_at' => 'datetime:Y-m-d H:i:s.u',
        'updated_at' => 'datetime:Y-m-d H:i:s.u',
        'deleted_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    /**
     * The priority values allowed for tasks.
     *
     * @var array<string>
     */
    public const PRIORITIES = ['low', 'medium', 'high'];

    /**
     * Validate a priority value.
     *
     * @param string|null $priority
     * @return bool
     */
    public static function isValidPriority(?string $priority): bool
    {
        return $priority === null || in_array($priority, self::PRIORITIES, true);
    }

    /**
     * Get the project that owns the task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to search for tasks.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if ($search) {
            // Use whereFullText for MySQL, but fall back to LIKE for SQLite
            if (config('database.default') === 'sqlite') {
                return $query->where('title', 'LIKE', "%{$search}%");
            } else {
                return $query->whereFullText('title', $search);
            }
        }
        
        return $query;
    }
    /**
     * Scope a query to filter tasks by priority.
     *
     * @param Builder $query
     * @param string|null $priority
     * @return Builder
     */
    public function scopeByPriority(Builder $query, ?string $priority): Builder
    {
        if ($priority && self::isValidPriority($priority)) {
            return $query->where('priority', $priority);
        }
        
        return $query;
    }

    /**
     * Scope a query to filter tasks by completion status.
     *
     * @param Builder $query
     * @param bool|null $done
     * @return Builder
     */
    public function scopeByStatus(Builder $query, ?bool $done): Builder
    {
        if ($done !== null) {
            return $query->where('done', $done);
        }
        
        return $query;
    }

    /**
     * Scope a query to filter tasks by due date.
     *
     * @param Builder $query
     * @param string|null $date
     * @return Builder
     */
    public function scopeByDueDate(Builder $query, ?string $date): Builder
    {
        if ($date) {
            return $query->whereDate('due_date', $date);
        }
        
        return $query;
    }
}
