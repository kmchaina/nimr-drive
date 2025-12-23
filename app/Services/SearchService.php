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
    public function searchFiles(User $user, string $query, string $currentPath = '', array $filters = []): array
    {
        $userBasePath = $this->getUserBasePath($user);
        $searchPath = $this->getUserPath($user, $currentPath);
        
        if (!$this->disk->exists($searchPath)) {
            return [];
        }

        // Get starred paths
        $starredPaths = \App\Models\Star::where('user_id', $user->id)->pluck('path')->toArray();

        $results = [];
        $query = strtolower(trim($query));
        
        // If query is empty but we have filters, we still want to search
        if (empty($query) && empty($filters)) {
            return [];
        }

        try {
            // Search recursively through user's directory
            $this->searchRecursive($searchPath, $userBasePath, $query, $results, $starredPaths, $filters);
            
            // Sort results by relevance (exact matches first, then partial matches)
            if (!empty($query)) {
                usort($results, function($a, $b) use ($query) {
                    $aExact = strtolower($a['name']) === $query ? 1 : 0;
                    $bExact = strtolower($b['name']) === $query ? 1 : 0;
                    
                    if ($aExact !== $bExact) {
                        return $bExact - $aExact; // Exact matches first
                    }
                    
                    // Then by name length (shorter names first for partial matches)
                    return strlen($a['name']) - strlen($b['name']);
                });
            } else {
                // If no query, sort by modified date
                usort($results, fn($a, $b) => $b['modified']->timestamp - $a['modified']->timestamp);
            }

            Log::info("Search completed for user {$user->id}: query='{$query}', filters=" . json_encode($filters) . ", results=" . count($results));
            
            return $results;

        } catch (\Exception $e) {
            Log::error("Search failed for user {$user->id}: " . $e->getMessage());
            throw new \Exception("Search failed: " . $e->getMessage());
        }
    }

    /**
     * Recursively search through directories
     */
    private function searchRecursive(string $searchPath, string $userBasePath, string $query, array &$results, array $starredPaths = [], array $filters = []): void
    {
        // Search files in current directory
        foreach ($this->disk->files($searchPath) as $file) {
            $fileName = basename($file);
            
            if ($this->matchesQuery($fileName, $query) && $this->matchesFilters($file, $filters)) {
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
                    'is_starred' => in_array($file, $starredPaths),
                ];
            }
        }

        // Search directories in current directory
        foreach ($this->disk->directories($searchPath) as $directory) {
            $dirName = basename($directory);
            
            // Check if directory name matches
            if ($this->matchesQuery($dirName, $query) && $this->matchesFilters($directory, $filters, true)) {
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
                    'is_starred' => in_array($directory, $starredPaths),
                ];
            }
            
            // Recursively search subdirectories
            $this->searchRecursive($directory, $userBasePath, $query, $results, $starredPaths, $filters);
        }
    }

    /**
     * Check if item matches filters
     */
    private function matchesFilters(string $path, array $filters, bool $isDirectory = false): bool
    {
        if (empty($filters)) return true;

        foreach ($filters as $key => $value) {
            if ($key === 'type' && !empty($value)) {
                if ($isDirectory) {
                    if ($value !== 'folder') return false;
                } else {
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $typeMap = [
                        'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
                        'pdf' => ['pdf'],
                        'document' => ['doc', 'docx', 'txt', 'rtf', 'odt'],
                        'spreadsheet' => ['xls', 'xlsx', 'csv', 'ods'],
                        'presentation' => ['ppt', 'pptx', 'odp'],
                        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
                    ];

                    if (!isset($typeMap[$value])) return true; // Unknown type, let it through
                    if (!in_array($extension, $typeMap[$value])) return false;
                }
            }
        }

        return true;
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
        if (str_starts_with($fullPath, $userBasePath)) {
            return ltrim(str_replace($userBasePath, '', $fullPath), '/');
        }
        
        // For shared paths, return the absolute internal path
        // The UI will handle virtualization via breadcrumbs
        return $fullPath;
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
        if (is_null($path) || empty($path) || $path === '/') {
            return $this->getUserBasePath($user);
        }

        // Handle absolute paths (shared items)
        if (str_starts_with($path, 'users/')) {
            return $path;
        }
        
        // Default to user's own storage
        $basePath = $this->getUserBasePath($user);
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