<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileOperationsIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'ad_username' => 'testuser',
            'ad_guid' => '12345678-1234-1234-1234-123456789012',
            'display_name' => 'Test User',
            'quota_bytes' => 1024 * 1024 * 100, // 100MB
            'used_bytes' => 0,
        ]);

        $this->fileService = app(FileService::class);

        // Set up session
        session(['user' => ['id' => $this->user->id]]);

        // Ensure test directory exists
        Storage::disk('lacie')->makeDirectory("users/{$this->user->id}/files");
    }

    /**
     * Property 4: File operations preserve data integrity
     * Feature: web-file-manager, Property 4: File operations preserve data integrity
     * Validates: Requirements 3.1, 3.2, 3.3
     */
    public function test_file_operations_preserve_data_integrity()
    {
        // Test folder creation preserves structure
        $folderName = 'test_folder_' . uniqid();
        $result = $this->fileService->createFolder($this->user, '', $folderName);
        $this->assertTrue($result);
        
        // Verify folder exists
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$folderName}"));
        
        // Test file upload preserves content
        $content = 'Test file content ' . uniqid();
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $fileName = 'test_file_' . uniqid() . '.txt';
        
        $uploadedFile = new UploadedFile($tempPath, $fileName, 'text/plain', null, true);
        $results = $this->fileService->uploadFiles($this->user, $folderName, [$uploadedFile]);
        
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        
        // Verify file content is preserved
        $storedContent = Storage::disk('lacie')->get("users/{$this->user->id}/files/{$folderName}/{$fileName}");
        $this->assertEquals($content, $storedContent);
        
        // Test rename preserves content
        $newFileName = 'renamed_' . $fileName;
        $renameResult = $this->fileService->rename($this->user, "{$folderName}/{$fileName}", $newFileName);
        $this->assertTrue($renameResult);
        
        // Verify renamed file has same content
        $renamedContent = Storage::disk('lacie')->get("users/{$this->user->id}/files/{$folderName}/{$newFileName}");
        $this->assertEquals($content, $renamedContent);
        
        // Verify original file no longer exists
        $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$folderName}/{$fileName}"));
        
        // Test delete removes file completely
        $deleteResult = $this->fileService->delete($this->user, "{$folderName}/{$newFileName}");
        $this->assertTrue($deleteResult);
        
        // Verify file is completely removed
        $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$folderName}/{$newFileName}"));
        
        // Clean up
        fclose($tempFile);
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}/files/{$folderName}");
    }

    /**
     * Test folder operations preserve directory structure
     */
    public function test_folder_operations_preserve_structure()
    {
        // Create nested folder structure
        $parentFolder = 'parent_' . uniqid();
        $childFolder = 'child_' . uniqid();
        
        // Create parent folder
        $result1 = $this->fileService->createFolder($this->user, '', $parentFolder);
        $this->assertTrue($result1);
        
        // Create child folder
        $result2 = $this->fileService->createFolder($this->user, $parentFolder, $childFolder);
        $this->assertTrue($result2);
        
        // Verify nested structure exists
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$parentFolder}"));
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$parentFolder}/{$childFolder}"));
        
        // Rename parent folder
        $newParentName = 'renamed_' . $parentFolder;
        $renameResult = $this->fileService->rename($this->user, $parentFolder, $newParentName);
        $this->assertTrue($renameResult);
        
        // Verify child folder is preserved in renamed parent
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$newParentName}"));
        $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$newParentName}/{$childFolder}"));
        $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$parentFolder}"));
        
        // Clean up
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}/files/{$newParentName}");
    }

    /**
     * Test batch operations maintain consistency
     */
    public function test_batch_operations_maintain_consistency()
    {
        // Create multiple test files
        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $content = "Test content {$i} " . uniqid();
            $tempFile = tmpfile();
            fwrite($tempFile, $content);
            $tempPath = stream_get_meta_data($tempFile)['uri'];
            $fileName = "test_file_{$i}_" . uniqid() . '.txt';
            
            $uploadedFile = new UploadedFile($tempPath, $fileName, 'text/plain', null, true);
            $results = $this->fileService->uploadFiles($this->user, '', [$uploadedFile]);
            
            $this->assertTrue($results[0]['success']);
            $files[] = ['name' => $fileName, 'content' => $content, 'temp' => $tempFile];
        }
        
        // Verify all files exist with correct content
        foreach ($files as $file) {
            $this->assertTrue(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$file['name']}"));
            $storedContent = Storage::disk('lacie')->get("users/{$this->user->id}/files/{$file['name']}");
            $this->assertEquals($file['content'], $storedContent);
        }
        
        // Test batch delete via controller
        $paths = array_map(fn($f) => $f['name'], $files);
        
        $response = $this->deleteJson('/api/files/batch-delete', [
            'paths' => $paths
        ]);
        
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        
        // Verify all files are deleted
        foreach ($files as $file) {
            $this->assertFalse(Storage::disk('lacie')->exists("users/{$this->user->id}/files/{$file['name']}"));
            fclose($file['temp']);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}");
        parent::tearDown();
    }
}