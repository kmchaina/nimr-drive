<?php

namespace App\Services;

use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SharingService
{
    /**
     * Check if a user has access to a specific path via sharing
     * 
     * @param User $user The user requesting access
     * @param string $fullPath The full path being requested (e.g., users/owner/files/...)
     * @param string $requiredAccess 'view' or 'edit'
     * @return bool
     */
    public function hasAccess(User $user, string $fullPath, string $requiredAccess = 'view'): bool
    {
        // Normalize the requested path
        $fullPath = ltrim($fullPath, '/\\');

        // Check for direct ownership first
        $userFolderPath = $user->folder_path;
        if (str_starts_with($fullPath, $userFolderPath)) {
            return true;
        }

        // Check if the path is shared with the user
        // We need to find if any parent directory of $fullPath is shared
        
        // Example: shared path is 'users/owner/files/documents'
        // Requested path is 'users/owner/files/documents/reports/2023.pdf'
        
        $shares = Share::where('shared_with_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($shares as $share) {
            $owner = $share->owner;
            if (!$owner) continue;

            $sharedFullPath = $owner->folder_path . '/' . ltrim($share->path, '/');
            $sharedFullPath = rtrim($sharedFullPath, '/');

            // Check if requested path is the shared path or a sub-path
            if ($fullPath === $sharedFullPath || str_starts_with($fullPath, $sharedFullPath . '/')) {
                // Check access level
                if ($requiredAccess === 'view') {
                    return true;
                }
                
                if ($requiredAccess === 'edit' && $share->access_level === 'edit') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all items shared with a user
     */
    public function getSharedWithMe(User $user)
    {
        return Share::with(['owner'])
            ->where('shared_with_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }
}
