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
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Project $project)
    {
        try {
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
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to view tasks in this project'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error retrieving tasks: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Store a new task in a project.
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        try {
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
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to create tasks in this project'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating task: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Show a specific task.
     *
     * @param Project $project
     * @param Task $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project, Task $task)
    {
        try {
            $this->authorize('view', $project);
            
            if ($task->project_id !== $project->id) {
                return response()->json(['message' => 'Task not found in this project'], 404);
            }

            return response()->json($task);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to view this task'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error retrieving task: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Update an existing task.
     *
     * @param Request $request
     * @param Project $project
     * @param Task $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Project $project, Task $task)
    {
        try {
            $this->authorize('update', $project);
            
            if ($task->project_id !== $project->id) {
                return response()->json(['message' => 'Task not found in this project'], 404);
            }

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'done' => 'boolean',
                'priority' => 'sometimes|required|in:low,medium,high',
                'due_date' => 'nullable|date',
            ]);

            $task->update($validated);
            
            return response()->json($task);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to update this task'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating task: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete a task.
     *
     * @param Project $project
     * @param Task $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project, Task $task)
    {
        try {
            $this->authorize('update', $project);
            
            if ($task->project_id !== $project->id) {
                return response()->json(['message' => 'Task not found in this project'], 404);
            }
            
            // This will soft delete the task since the Task model uses SoftDeletes trait
            $task->delete();
            
            return response()->json(['message' => 'Task soft-deleted successfully']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to delete this task'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting task: ' . $e->getMessage()], 500);
        }
    }
}
