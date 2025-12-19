<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DirectFileDownloadTest extends TestCase
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
     * Property 5: Downloads serve original files
     * Feature: web-file-manager, Property 5: Downloads serve original files
     * Validates: Requirements 3.4
     */
    public function test_downloads_serve_original_files()
    {
        // Create test file with specific content
        $originalContent = 'Original file content ' . uniqid() . "\nLine 2\nLine 3";
        $tempFile = tmpfile();
        fwrite($tempFile, $originalContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $fileName = 'download_test_' . uniqid() . '.txt';
        
        // Upload the file
        $uploadedFile = new UploadedFile($tempPath, $fileName, 'text/plain', null, true);
        $results = $this->fileService->uploadFiles($this->user, '', [$uploadedFile]);
        
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        
        // Test download via controller endpoint
        $response = $this->get('/api/files/download?path=' . urlencode($fileName));
        
        $response->assertStatus(200);
        
        // For streamed responses, we need to capture the output
        ob_start();
        $response->sendContent();
        $downloadedContent = ob_get_clean();
        
        // Verify the downloaded content matches original
        $this->assertEquals($originalContent, $downloadedContent);
        
        // Verify content-type header is set correctly
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringStartsWith('text/plain', $contentType);
        
        // Verify content-disposition header suggests correct filename
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString($fileName, $contentDisposition);
        
        // Clean up
        fclose($tempFile);
    }

    /**
     * Test download of binary files preserves exact content
     */
    public function test_binary_file_download_integrity()
    {
        // Create a binary file with specific byte pattern
        $binaryContent = '';
        for ($i = 0; $i < 256; $i++) {
            $binaryContent .= chr($i);
        }
        
        $tempFile = tmpfile();
        fwrite($tempFile, $binaryContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $fileName = 'binary_test_' . uniqid() . '.bin';
        
        // Upload the binary file
        $uploadedFile = new UploadedFile($tempPath, $fileName, 'application/octet-stream', null, true);
        $results = $this->fileService->uploadFiles($this->user, '', [$uploadedFile]);
        
        $this->assertTrue($results[0]['success']);
        
        // Download and verify exact binary content
        $response = $this->get('/api/files/download?path=' . urlencode($fileName));
        
        $response->assertStatus(200);
        
        // Capture streamed content
        ob_start();
        $response->sendContent();
        $downloadedContent = ob_get_clean();
        
        $this->assertEquals($binaryContent, $downloadedContent);
        $this->assertEquals(strlen($binaryContent), strlen($downloadedContent));
        
        // Verify each byte matches
        for ($i = 0; $i < strlen($binaryContent); $i++) {
            $this->assertEquals(ord($binaryContent[$i]), ord($downloadedContent[$i]), "Byte {$i} mismatch");
        }
        
        // Clean up
        fclose($tempFile);
    }

    /**
     * Test download directly from LaCie drive (no intermediate copying)
     */
    public function test_download_streams_from_lacie_drive()
    {
        // Create test file
        $content = 'LaCie drive test content ' . uniqid();
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $fileName = 'lacie_test_' . uniqid() . '.txt';
        
        // Upload file
        $uploadedFile = new UploadedFile($tempPath, $fileName, 'text/plain', null, true);
        $results = $this->fileService->uploadFiles($this->user, '', [$uploadedFile]);
        $this->assertTrue($results[0]['success']);
        
        // Verify file exists on LaCie drive
        $lacieFilePath = "users/{$this->user->id}/files/{$fileName}";
        $this->assertTrue(Storage::disk('lacie')->exists($lacieFilePath));
        
        // Get file info before download
        $originalSize = Storage::disk('lacie')->size($lacieFilePath);
        $originalModified = Storage::disk('lacie')->lastModified($lacieFilePath);
        
        // Download file
        $response = $this->get('/api/files/download?path=' . urlencode($fileName));
        $response->assertStatus(200);
        
        // Verify file still exists on LaCie drive with same properties
        $this->assertTrue(Storage::disk('lacie')->exists($lacieFilePath));
        $this->assertEquals($originalSize, Storage::disk('lacie')->size($lacieFilePath));
        $this->assertEquals($originalModified, Storage::disk('lacie')->lastModified($lacieFilePath));
        
        // Verify downloaded content matches file on drive
        $driveContent = Storage::disk('lacie')->get($lacieFilePath);
        
        // Capture streamed content
        ob_start();
        $response->sendContent();
        $downloadedContent = ob_get_clean();
        
        $this->assertEquals($driveContent, $downloadedContent);
        
        // Clean up
        fclose($tempFile);
    }

    /**
     * Test download of files in subdirectories
     */
    public function test_download_from_subdirectories()
    {
        // Create subdirectory
        $subDir = 'subdir_' . uniqid();
        $this->fileService->createFolder($this->user, '', $subDir);
        
        // Create file in subdirectory
        $content = 'Subdirectory file content ' . uniqid();
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        $fileName = 'subdir_file_' . uniqid() . '.txt';
        
        $uploadedFile = new UploadedFile($tempPath, $fileName, 'text/plain', null, true);
        $results = $this->fileService->uploadFiles($this->user, $subDir, [$uploadedFile]);
        $this->assertTrue($results[0]['success']);
        
        // Download file from subdirectory
        $filePath = $subDir . '/' . $fileName;
        $response = $this->get('/api/files/download?path=' . urlencode($filePath));
        
        $response->assertStatus(200);
        
        // Capture streamed content
        ob_start();
        $response->sendContent();
        $downloadedContent = ob_get_clean();
        
        $this->assertEquals($content, $downloadedContent);
        
        // Clean up
        fclose($tempFile);
    }

    /**
     * Test download error handling for non-existent files
     */
    public function test_download_nonexistent_file_error()
    {
        $response = $this->get('/api/files/download?path=' . urlencode('nonexistent_file.txt'));
        
        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('not found', strtolower($responseData['error']));
    }

    /**
     * Test download error handling for directories
     */
    public function test_download_directory_error()
    {
        // Create a directory
        $dirName = 'test_directory_' . uniqid();
        $this->fileService->createFolder($this->user, '', $dirName);
        
        // Try to download the directory (should fail)
        $response = $this->get('/api/files/download?path=' . urlencode($dirName));
        
        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('not found', strtolower($responseData['error']));
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('lacie')->deleteDirectory("users/{$this->user->id}");
        parent::tearDown();
    }
}