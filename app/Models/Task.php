<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'is_done',        // ✅ corrected from 'done' to match your column name
        'priority',
        'due_date',
        'deadline',
        'description',
        'status',
    ];

    protected $casts = [
        'is_done' => 'boolean',     // ✅ ensure this is cast correctly
        'due_date' => 'date',
        'deadline' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);  // If applicable in your schema
    }
}