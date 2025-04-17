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

        $query = $project->tasks(); // ← remove `->query()`

        // Filters
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('done')) {
            $query->where('done', filter_var($request->done, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        // Sorting
        if ($request->has('sort_by')) {
            $direction = $request->get('direction', 'asc');
            $query->orderBy($request->sort_by, $direction);
        } else {
            $query->latest(); // default: newest first
        }

        return $query->get();
    }
    /**
     * Store a new task in a project.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'done' => 'boolean',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task = $project->tasks()->create($validated);

        return response()->json($task, 201);
    }

    /**
     * Show a specific task.
     */
    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $project);

        return response()->json($task);
    }

    /**
     * Update an existing task.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'done' => 'boolean',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task->update($request->only(['title', 'done', 'priority', 'due_date']));

        return response()->json($task);
    }

    /**
     * Delete a task.
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $project);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
