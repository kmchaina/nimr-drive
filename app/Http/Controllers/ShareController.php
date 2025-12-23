<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShareController extends Controller
{
    /**
     * List items shared with the current user
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $shares = Share::with(['owner'])
            ->where('shared_with_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        return response()->json([
            'success' => true,
            'shares' => $shares
        ]);
    }

    /**
     * List items shared by the current user
     */
    public function owned(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $shares = Share::with(['sharedWith'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'shares' => $shares
        ]);
    }

    /**
     * Create a new share
     */
    public function create(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'shared_with_id' => 'required|exists:users,id',
            'access_level' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');
        $sharedWithId = $request->input('shared_with_id');

        // Prevent sharing with self
        if ($user->id == $sharedWithId) {
            return response()->json([
                'success' => false,
                'error' => 'You cannot share items with yourself'
            ], 400);
        }

        // Check if share already exists
        $existingShare = Share::where('user_id', $user->id)
            ->where('shared_with_id', $sharedWithId)
            ->where('path', $path)
            ->first();

        if ($existingShare) {
            $existingShare->update([
                'access_level' => $request->input('access_level'),
                'expires_at' => $request->input('expires_at'),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Share updated successfully',
                'share' => $existingShare->load('sharedWith')
            ]);
        }

        $share = Share::create([
            'user_id' => $user->id,
            'shared_with_id' => $sharedWithId,
            'path' => $path,
            'access_level' => $request->input('access_level'),
            'expires_at' => $request->input('expires_at'),
        ]);

        // Send notification to the recipient
        $itemName = basename($path);
        $ownerName = $user->display_name ?: $user->name;
        \App\Http\Controllers\NotificationController::create(
            $sharedWithId,
            'New file shared',
            "{$ownerName} shared \"{$itemName}\" with you.",
            'share',
            'SHARED_ROOT' // Link to shared items view
        );

        return response()->json([
            'success' => true,
            'message' => 'Item shared successfully',
            'share' => $share->load('sharedWith')
        ]);
    }

    /**
     * Update access level for a share
     */
    public function update(Request $request, Share $share)
    {
        $user = $this->getCurrentUser();

        if ($share->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'access_level' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $share->update([
            'access_level' => $request->input('access_level'),
            'expires_at' => $request->input('expires_at'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Share updated successfully',
            'share' => $share->load('sharedWith')
        ]);
    }

    /**
     * Remove a share
     */
    public function destroy(Share $share)
    {
        $user = $this->getCurrentUser();

        if ($share->user_id !== $user->id && $share->shared_with_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $share->delete();

        return response()->json([
            'success' => true,
            'message' => 'Share removed successfully'
        ]);
    }

    /**
     * Search for users to share with
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $currentUser = $this->getCurrentUser();

        $users = User::where('id', '!=', $currentUser->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('display_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('ad_username', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'display_name', 'email', 'ad_username']);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
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
