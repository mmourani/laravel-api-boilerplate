<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all tasks for a given project.
     */
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $query = $project->tasks();

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by completion status
        if ($request->has('done')) {
            $query->where('done', filter_var($request->done, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by due date
        if ($request->has('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        // Sorting
        if ($request->has('sort_by')) {
            $direction = $request->input('direction', 'asc');
            
            // Ensure valid sort direction
            $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';
            
            // Sort by priority needs special handling for custom order
            if ($request->sort_by === 'priority') {
                $sql = "CASE 
                    WHEN priority = 'high' THEN 3 
                    WHEN priority = 'medium' THEN 2 
                    WHEN priority = 'low' THEN 1 
                    ELSE 0 END";
                
                // For descending, high (3) should come first
                // For ascending, low (1) should come first
                $query->orderByRaw($sql . " " . ($direction === 'desc' ? 'DESC' : 'ASC'));
            } else {
                $query->orderBy($request->sort_by, $direction);
            }
        } else {
            $query->latest(); // default: newest first
        }

        return response()->json($query->get());
    }

    /**
     * Store a new task in a project.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task = $project->tasks()->create([
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'done' => false, // Default value
        ]);

        return response()->json($task, 201);
    }

    /**
     * Show a specific task.
     */
    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $project);
        
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        return response()->json($task);
    }

    /**
     * Update an existing task.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);
        
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'done' => 'boolean',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Delete a task.
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('update', $project);
        
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
