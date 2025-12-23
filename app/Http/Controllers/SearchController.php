<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Models\User;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search files within user's directory
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'path' => 'nullable|string',
            'type' => 'nullable|string|in:folder,pdf,image,document,spreadsheet,presentation,archive',
        ]);

        $user = $this->getCurrentUser();
        $query = $request->input('q', '');
        $currentPath = $request->input('path', '');
        
        $filters = [];
        if ($request->has('type')) {
            $filters['type'] = $request->input('type');
        }

        try {
            $results = $this->searchService->searchFiles($user, $query, $currentPath, $filters);

            return response()->json([
                'success' => true,
                'query' => $query,
                'filters' => $filters,
                'results' => $results,
                'total_results' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current user from session
     */
    private function getCurrentUser(): User
    {
        $sessionUser = session('user');
        
        if (!$sessionUser) {
            abort(401, 'User not authenticated');
        }

        $user = User::find($sessionUser['id']);
        
        if (!$user) {
            abort(401, 'User not found');
        }

        return $user;
    }
}