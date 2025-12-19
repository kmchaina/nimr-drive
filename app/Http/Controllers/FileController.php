<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FileService;
use App\Services\QuotaService;
use App\Services\ErrorHandlerService;
use App\Models\User;

class FileController extends Controller
{
    protected $fileService;
    protected $quotaService;
    protected $errorHandler;

    public function __construct(FileService $fileService, QuotaService $quotaService, ErrorHandlerService $errorHandler)
    {
        $this->fileService = $fileService;
        $this->quotaService = $quotaService;
        $this->errorHandler = $errorHandler;
    }

    /**
     * List files and folders in current directory
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        $path = $request->input('path', '') ?? '';
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 100);

        try {
            $result = $this->fileService->listDirectory($user, $path, $page, $perPage);
            $quotaInfo = $this->quotaService->getQuotaInfo($user);

            return response()->json([
                'success' => true,
                'files' => $result['items'],
                'pagination' => $result['pagination'],
                'current_path' => $path,
                'breadcrumbs' => $this->generateBreadcrumbs($path),
                'quota' => $quotaInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                $this->errorHandler->handleFileSystemError($e),
                400
            );
        }
    }

    /**
     * Create a new folder
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path', '');
        $folderName = $request->input('name');

        try {
            $result = $this->fileService->createFolder($user, $path, $folderName);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => "Folder '{$folderName}' created successfully",
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create folder',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rename a file or folder
     */
    public function rename(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');
        $newName = $request->input('new_name');

        try {
            $result = $this->fileService->rename($user, $path, $newName);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => "Renamed to '{$newName}' successfully",
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to rename',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a file or folder
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');

        try {
            $result = $this->fileService->delete($user, $path);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to delete',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download a file
     */
    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');

        try {
            return $this->fileService->downloadFile($user, $path);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Recalculate user's quota usage
     */
    public function recalculateQuota(Request $request)
    {
        $user = $this->getCurrentUser();

        try {
            $actualUsage = $this->quotaService->recalculateUsage($user);
            $quotaInfo = $this->quotaService->getQuotaInfo($user);

            return response()->json([
                'success' => true,
                'message' => 'Quota recalculated successfully',
                'quota' => $quotaInfo,
                'recalculated_usage' => $actualUsage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get quota information
     */
    public function quota(Request $request)
    {
        $user = $this->getCurrentUser();

        try {
            $quotaInfo = $this->quotaService->getQuotaInfo($user);

            return response()->json([
                'success' => true,
                'quota' => $quotaInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file information
     */
    public function info(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path');

        try {
            $fileInfo = $this->fileService->getFileInfo($user, $path);

            return response()->json([
                'success' => true,
                'file' => $fileInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Batch delete files and folders
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'required|string',
        ]);

        $user = $this->getCurrentUser();
        $paths = $request->input('paths');
        $results = [];

        foreach ($paths as $path) {
            try {
                $result = $this->fileService->delete($user, $path);
                $results[] = [
                    'path' => $path,
                    'success' => $result,
                    'message' => $result ? 'Deleted successfully' : 'Failed to delete'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'path' => $path,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Deleted {$successCount} of {$totalCount} items",
            'results' => $results,
        ]);
    }

    /**
     * Upload files
     */
    public function upload(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'file|max:' . (1024 * 1024 * 100), // 100MB max per file
        ]);

        $user = $this->getCurrentUser();
        $path = $request->input('path') ?? '';
        $files = $request->file('files');

        try {
            $results = $this->fileService->uploadFiles($user, $path, $files);
            $quotaInfo = $this->quotaService->getQuotaInfo($user);

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Uploaded {$successCount} of {$totalCount} files successfully",
                'results' => $results,
                'quota' => $quotaInfo,
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

    /**
     * Generate breadcrumbs for navigation
     */
    private function generateBreadcrumbs(string $path): array
    {
        $breadcrumbs = [
            ['name' => 'My Files', 'path' => '']
        ];

        if (!empty($path) && $path !== '/') {
            $pathParts = explode('/', trim($path, '/'));
            $currentPath = '';

            foreach ($pathParts as $part) {
                if (!empty($part)) {
                    $currentPath .= ($currentPath ? '/' : '') . $part;
                    $breadcrumbs[] = [
                        'name' => $part,
                        'path' => $currentPath
                    ];
                }
            }
        }

        return $breadcrumbs;
    }
}
