<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FileService;
use App\Services\QuotaService;
use App\Models\User;

class UploadController extends Controller
{
    protected $fileService;
    protected $quotaService;

    public function __construct(FileService $fileService, QuotaService $quotaService)
    {
        $this->fileService = $fileService;
        $this->quotaService = $quotaService;
    }

    /**
     * Handle chunked file upload
     */
    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'chunk' => 'required|integer|min:0',
            'chunks' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'path' => 'nullable|string',
            'size' => 'required|integer|min:1',
            'relative_path' => 'nullable|string',
        ]);

        $user = $this->getCurrentUser();
        $chunk = $request->integer('chunk');
        $chunks = $request->integer('chunks');
        $fileName = $request->string('name');
        $path = $request->string('path', '');
        $totalSize = $request->integer('size');
        $relativePath = $request->string('relative_path', '');

        try {
            // Check quota before starting upload
            if ($chunk === 0 && !$this->quotaService->canUpload($user, $totalSize)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Upload would exceed quota limit',
                    'quota_exceeded' => true,
                ], 400);
            }

            // Generate consistent upload ID based on user, filename and size
            // Use a combination that will be the same for all chunks of the same file
            $uploadId = md5($user->id . '_' . $fileName . '_' . $totalSize . '_' . $relativePath);
            $tempDir = storage_path("app/temp/uploads/{$uploadId}");
            
            // Create temp directory and metadata if it doesn't exist
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
                
                // Create metadata file
                $meta = [
                    'user_id' => $user->id,
                    'file_name' => $fileName,
                    'size' => $totalSize,
                    'chunks' => $chunks,
                    'relative_path' => $relativePath,
                    'created_at' => now()->toISOString(),
                ];
                
                file_put_contents("{$tempDir}/meta.json", json_encode($meta));
            }

            // Store chunk
            $chunkFile = "{$tempDir}/chunk_{$chunk}";
            $request->file('file')->move($tempDir, "chunk_{$chunk}");

            // Check if all chunks are uploaded
            $uploadedChunks = glob("{$tempDir}/chunk_*");
            
            
            if (count($uploadedChunks) === $chunks) {
                // All chunks uploaded, combine them
                try {
                    $result = $this->combineChunks($user, $uploadId, $fileName, $path, $chunks, $totalSize, $relativePath);
                    
                    // Clean up temp files (disabled due to Windows file locking issues)
                    // $this->cleanupTempFiles($tempDir);
                    
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'file' => $result,
                        'message' => "File '{$fileName}' uploaded successfully",
                    ]);
                } catch (\Exception $e) {
                    // Clean up temp files on error (disabled due to Windows file locking issues)
                    // $this->cleanupTempFiles($tempDir);
                    
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'completed' => false,
                'chunk' => $chunk,
                'progress' => round(($chunk + 1) / $chunks * 100, 2),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get upload progress
     */
    public function getProgress(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->string('upload_id');
        $tempDir = storage_path("app/temp/uploads/{$uploadId}");

        if (!is_dir($tempDir)) {
            return response()->json([
                'success' => false,
                'error' => 'Upload not found',
            ], 404);
        }

        $uploadedChunks = glob("{$tempDir}/chunk_*");
        $metaFile = "{$tempDir}/meta.json";
        
        if (!file_exists($metaFile)) {
            return response()->json([
                'success' => false,
                'error' => 'Upload metadata not found',
            ], 404);
        }

        $meta = json_decode(file_get_contents($metaFile), true);
        $progress = round(count($uploadedChunks) / $meta['chunks'] * 100, 2);

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'chunks_uploaded' => count($uploadedChunks),
            'total_chunks' => $meta['chunks'],
            'completed' => count($uploadedChunks) === $meta['chunks'],
        ]);
    }

    /**
     * Cancel upload
     */
    public function cancelUpload(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uploadId = $request->string('upload_id');
        $tempDir = storage_path("app/temp/uploads/{$uploadId}");

        if (is_dir($tempDir)) {
            $this->cleanupTempFiles($tempDir);
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload cancelled',
        ]);
    }



    /**
     * Combine uploaded chunks into final file
     */
    private function combineChunks(User $user, string $uploadId, string $fileName, string $path, int $chunks, int $totalSize, string $relativePath = ''): array
    {
        $tempDir = storage_path("app/temp/uploads/{$uploadId}");
        $finalFile = "{$tempDir}/final_{$fileName}";

        // Combine chunks
        $output = fopen($finalFile, 'wb');
        
        for ($i = 0; $i < $chunks; $i++) {
            $chunkFile = "{$tempDir}/chunk_{$i}";
            if (file_exists($chunkFile)) {
                $chunk = fopen($chunkFile, 'rb');
                stream_copy_to_stream($chunk, $output);
                fclose($chunk);
            } else {
                fclose($output);
                throw new \Exception("Chunk {$i} not found at {$chunkFile}");
            }
        }
        
        fclose($output);

        // Verify file size
        $actualSize = filesize($finalFile);
        if ($actualSize !== $totalSize) {
            throw new \Exception("File size mismatch. Expected: {$totalSize}, Actual: {$actualSize}");
        }

        // Create UploadedFile instance and use existing FileService
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $finalFile,
            $fileName,
            mime_content_type($finalFile),
            null,
            true // Mark as test file to avoid validation errors
        );

        $results = $this->fileService->uploadFiles($user, $path, [$uploadedFile], [$relativePath]);
        
        if (empty($results) || !$results[0]['success']) {
            throw new \Exception($results[0]['error'] ?? 'Upload failed');
        }

        return $results[0];
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTempFiles(string $tempDir): void
    {
        if (!is_dir($tempDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                rmdir($path->getRealPath());
            } else {
                unlink($path->getRealPath());
            }
        }
        
        rmdir($tempDir);
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