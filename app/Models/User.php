<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime:Y-m-d H:i:s.u',
        'password' => 'hashed',
        'deleted_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Bootstrap the model's events.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($user) {
            // Don't cascade soft deletes to projects - only for force delete
            if (!$user->isForceDeleting()) {
                return;
            }
            
            // Force delete projects for a permanent user deletion
            $user->projects()->forceDelete();
        });
    }

    /**
     * Get all projects belonging to the user.
     *
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Scope a query to search for users.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        if ($search) {
            // Use basic LIKE for SQLite or whereFullText for other drivers
            if (config('database.default') === 'sqlite') {
                return $query->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%");
            }
            
            return $query->whereFullText(['name', 'email'], $search);
        }
        
        return $query;
    }
}
