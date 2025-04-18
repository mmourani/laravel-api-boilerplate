    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15); // Default to 15 items per page

        $query = Project::where('user_id', $user->id);
        
        // Apply search if provided
        if ($search) {
            $query->search($search);
        }
        
        // Paginate results
        $projects = $query->paginate($perPage);
        
        // Debug: Log the pagination structure to help identify issues
        if (app()->environment('testing')) {
            \Log::info('Pagination structure', [
                'has_meta' => isset($projects['meta']),
                'structure' => array_keys((array)$projects->toArray()),
                'driver' => \DB::connection()->getDriverName()
            ]);
        }
        
        // Use the resource collection to ensure proper pagination meta data
        return response()->json($projects);
    }
