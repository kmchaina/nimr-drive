<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SearchService
{
    protected $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('lacie');
    }

    /**
     * Search files within user's directory
     */
    public function searchFiles(User $user, string $query, string $currentPath = ''): array
    {
        $userBasePath = $this->getUserBasePath($user);
        $searchPath = $this->getUserPath($user, $currentPath);
        
        if (!$this->disk->exists($searchPath)) {
            return [];
        }

        $results = [];
        $query = strtolower(trim($query));
        
        // Return empty results for empty queries
        if (empty($query)) {
            return [];
        }

        try {
            // Search recursively through user's directory
            $this->searchRecursive($searchPath, $userBasePath, $query, $results);
            
            // Sort results by relevance (exact matches first, then partial matches)
            usort($results, function($a, $b) use ($query) {
                $aExact = strtolower($a['name']) === $query ? 1 : 0;
                $bExact = strtolower($b['name']) === $query ? 1 : 0;
                
                if ($aExact !== $bExact) {
                    return $bExact - $aExact; // Exact matches first
                }
                
                // Then by name length (shorter names first for partial matches)
                return strlen($a['name']) - strlen($b['name']);
            });

            Log::info("Search completed for user {$user->id}: query='{$query}', results=" . count($results));
            
            return $results;

        } catch (\Exception $e) {
            Log::error("Search failed for user {$user->id}: " . $e->getMessage());
            throw new \Exception("Search failed: " . $e->getMessage());
        }
    }

    /**
     * Recursively search through directories
     */
    private function searchRecursive(string $searchPath, string $userBasePath, string $query, array &$results): void
    {
        // Search files in current directory
        foreach ($this->disk->files($searchPath) as $file) {
            $fileName = basename($file);
            
            if ($this->matchesQuery($fileName, $query)) {
                $relativePath = $this->getRelativePath($file, $userBasePath);
                $folderPath = dirname($relativePath);
                
                $results[] = [
                    'name' => $fileName,
                    'path' => $relativePath,
                    'folder_path' => $folderPath === '.' ? '' : $folderPath,
                    'type' => 'file',
                    'size' => $this->disk->size($file),
                    'size_formatted' => $this->formatBytes($this->disk->size($file)),
                    'mime_type' => $this->disk->mimeType($file),
                    'modified' => $this->getLastModified($file),
                    'is_directory' => false,
                ];
            }
        }

        // Search directories in current directory
        foreach ($this->disk->directories($searchPath) as $directory) {
            $dirName = basename($directory);
            
            // Check if directory name matches
            if ($this->matchesQuery($dirName, $query)) {
                $relativePath = $this->getRelativePath($directory, $userBasePath);
                $folderPath = dirname($relativePath);
                
                $results[] = [
                    'name' => $dirName,
                    'path' => $relativePath,
                    'folder_path' => $folderPath === '.' ? '' : $folderPath,
                    'type' => 'directory',
                    'size' => null,
                    'size_formatted' => null,
                    'mime_type' => null,
                    'modified' => $this->getLastModified($directory),
                    'is_directory' => true,
                ];
            }
            
            // Recursively search subdirectories
            $this->searchRecursive($directory, $userBasePath, $query, $results);
        }
    }

    /**
     * Check if filename matches search query
     */
    private function matchesQuery(string $fileName, string $query): bool
    {
        $fileName = strtolower($fileName);
        $query = strtolower($query);
        
        // Check for exact match or partial match
        return str_contains($fileName, $query);
    }

    /**
     * Get relative path from user's base directory
     */
    private function getRelativePath(string $fullPath, string $userBasePath): string
    {
        $relativePath = str_replace($userBasePath . '/', '', $fullPath);
        return $relativePath;
    }

    /**
     * Get user's base path
     */
    private function getUserBasePath(User $user): string
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        return "users/{$username}/files";
    }

    /**
     * Get full user path with optional subpath
     */
    private function getUserPath(User $user, ?string $path = ''): string
    {
        $basePath = $this->getUserBasePath($user);
        
        if (is_null($path) || empty($path) || $path === '/') {
            return $basePath;
        }
        
        // Sanitize path
        $path = trim($path, '/');
        $path = str_replace(['..', '\\'], '', $path);
        
        return $basePath . '/' . $path;
    }

    /**
     * Get last modified time
     */
    private function getLastModified(string $path): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::createFromTimestamp($this->disk->lastModified($path));
        } catch (\Exception $e) {
            return \Carbon\Carbon::now();
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}