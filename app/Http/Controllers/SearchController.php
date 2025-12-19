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
            'q' => 'required|string|min:1|max:255',
            'path' => 'nullable|string',
        ]);

        $user = $this->getCurrentUser();
        $query = $request->input('q');
        $currentPath = $request->input('path', '');

        try {
            $results = $this->searchService->searchFiles($user, $query, $currentPath);

            return response()->json([
                'success' => true,
                'query' => $query,
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