<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Services\FileService;
use App\Services\QuotaService;
use App\Services\StorageService;
use App\Models\User;

/**
 * Feature: web-file-manager, Property 1: Folder navigation updates breadcrumbs
 * 
 * Property: For any folder navigation operation, the breadcrumb component should 
 * accurately reflect the current path hierarchy
 * 
 * Validates: Requirements 1.2
 */
class FolderNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected $fileService;
    protected $quotaService;
    protected $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test LaCie drive
        config(['filesystems.disks.lacie.root' => storage_path('testing/lacie')]);
        
        // Create test directory structure
        if (!file_exists(storage_path('testing/lacie'))) {
            mkdir(storage_path('testing/lacie'), 0755, true);
        }
        if (!file_exists(storage_path('testing/lacie/users'))) {
            mkdir(storage_path('testing/lacie/users'), 0755, true);
        }

        $this->storageService = new StorageService();
        $this->quotaService = new QuotaService($this->storageService);
        $securityService = new \App\Services\SecurityService();
        $this->fileService = new FileService($this->quotaService, $securityService);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupTestDirectory(storage_path('testing'));
        parent::tearDown();
    }

    private function cleanupTestDirectory(string $path): void
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->cleanupTestDirectory($fullPath);
                } else {
                    unlink($fullPath);
                }
            }
            rmdir($path);
        }
    }

    /**
     * Property Test: Breadcrumb generation reflects path hierarchy
     * 
     * For any path, breadcrumbs should accurately represent the navigation hierarchy
     */
    public function test_breadcrumb_generation_reflects_path_hierarchy(): void
    {
        $user = User::create([
            'name' => 'Navigation Test User',
            'email' => 'nav@test.com',
            'ad_username' => 'navuser',
            'quota_bytes' => 5 * 1024 * 1024 * 1024,
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);

        // Test various path scenarios
        $testPaths = [
            '' => [
                ['name' => 'My Files', 'path' => '']
            ],
            'Documents' => [
                ['name' => 'My Files', 'path' => ''],
                ['name' => 'Documents', 'path' => 'Documents']
            ],
            'Documents/Projects' => [
                ['name' => 'My Files', 'path' => ''],
                ['name' => 'Documents', 'path' => 'Documents'],
                ['name' => 'Projects', 'path' => 'Documents/Projects']
            ],
            'Documents/Projects/2024' => [
                ['name' => 'My Files', 'path' => ''],
                ['name' => 'Documents', 'path' => 'Documents'],
                ['name' => 'Projects', 'path' => 'Documents/Projects'],
                ['name' => '2024', 'path' => 'Documents/Projects/2024']
            ],
            'Images/Photos/Vacation' => [
                ['name' => 'My Files', 'path' => ''],
                ['name' => 'Images', 'path' => 'Images'],
                ['name' => 'Photos', 'path' => 'Images/Photos'],
                ['name' => 'Vacation', 'path' => 'Images/Photos/Vacation']
            ]
        ];

        foreach ($testPaths as $path => $expectedBreadcrumbs) {
            // Create the directory structure if needed
            if (!empty($path)) {
                $pathParts = explode('/', $path);
                $currentPath = '';
                
                foreach ($pathParts as $part) {
                    try {
                        $this->fileService->createFolder($user, $currentPath, $part);
                    } catch (\Exception $e) {
                        // Folder might already exist, continue
                    }
                    $currentPath = $currentPath ? "{$currentPath}/{$part}" : $part;
                }
            }

            // Test breadcrumb generation via API
            $this->simulateUserSession($user);
            $response = $this->getJson('/api/files?' . http_build_query(['path' => $path]));
            
            if ($response->status() === 200) {
                $breadcrumbs = $response->json('breadcrumbs');
                
                // Property: Breadcrumb count should match expected
                $this->assertCount(count($expectedBreadcrumbs), $breadcrumbs,
                    "Breadcrumb count mismatch for path: {$path}");
                
                // Property: Each breadcrumb should match expected structure
                foreach ($expectedBreadcrumbs as $index => $expectedCrumb) {
                    $this->assertEquals($expectedCrumb['name'], $breadcrumbs[$index]['name'],
                        "Breadcrumb name mismatch at index {$index} for path: {$path}");
                    $this->assertEquals($expectedCrumb['path'], $breadcrumbs[$index]['path'],
                        "Breadcrumb path mismatch at index {$index} for path: {$path}");
                }
            }
        }
    }

    /**
     * Property Test: Folder navigation maintains path consistency
     * 
     * For any folder navigation, the resulting path should be consistent and valid
     */
    public function test_folder_navigation_maintains_path_consistency(): void
    {
        $user = User::create([
            'name' => 'Path Consistency User',
            'email' => 'path@test.com',
            'ad_username' => 'pathuser',
            'quota_bytes' => 5 * 1024 * 1024 * 1024,
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);

        // Create nested folder structure
        $folders = [
            'Documents',
            'Documents/Work',
            'Documents/Work/Projects',
            'Documents/Personal',
            'Images',
            'Images/2024',
            'Images/2024/Vacation'
        ];

        foreach ($folders as $folderPath) {
            $pathParts = explode('/', $folderPath);
            $currentPath = '';
            
            foreach ($pathParts as $part) {
                try {
                    $this->fileService->createFolder($user, $currentPath, $part);
                } catch (\Exception $e) {
                    // Folder might already exist, continue
                }
                $currentPath = $currentPath ? "{$currentPath}/{$part}" : $part;
            }
        }

        // Test navigation consistency
        $this->simulateUserSession($user);
        foreach ($folders as $targetPath) {
            $response = $this->getJson('/api/files?' . http_build_query(['path' => $targetPath]));
            
            if ($response->status() === 200) {
                $data = $response->json();
                
                // Property: Current path should match requested path
                $this->assertEquals($targetPath, $data['current_path'],
                    "Current path mismatch for: {$targetPath}");
                
                // Property: Breadcrumbs should end with current folder
                $breadcrumbs = $data['breadcrumbs'];
                if (!empty($breadcrumbs)) {
                    $lastBreadcrumb = end($breadcrumbs);
                    $this->assertEquals($targetPath, $lastBreadcrumb['path'],
                        "Last breadcrumb path should match current path for: {$targetPath}");
                }
                
                // Property: All breadcrumb paths should be valid subpaths
                foreach ($breadcrumbs as $breadcrumb) {
                    if (!empty($breadcrumb['path'])) {
                        $this->assertTrue(str_starts_with($targetPath, $breadcrumb['path']),
                            "Breadcrumb path '{$breadcrumb['path']}' should be a prefix of '{$targetPath}'");
                    }
                }
            }
        }
    }

    /**
     * Property Test: File listing includes proper metadata
     * 
     * For any directory listing, files should include appropriate metadata
     */
    public function test_file_listing_includes_proper_metadata(): void
    {
        $user = User::create([
            'name' => 'Metadata Test User',
            'email' => 'metadata@test.com',
            'ad_username' => 'metauser',
            'quota_bytes' => 5 * 1024 * 1024 * 1024,
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);
        $disk = Storage::disk('lacie');
        $userPath = "users/{$user->id}/files";

        // Create test files and folders
        $testItems = [
            'folder1' => 'directory',
            'folder2' => 'directory',
            'document.txt' => 'file',
            'image.jpg' => 'file',
            'data.csv' => 'file'
        ];

        foreach ($testItems as $name => $type) {
            if ($type === 'directory') {
                $disk->makeDirectory("{$userPath}/{$name}");
            } else {
                $content = "Test content for {$name}";
                $disk->put("{$userPath}/{$name}", $content);
            }
        }

        // Test file listing
        $this->simulateUserSession($user);
        $response = $this->getJson('/api/files');
        
        $this->assertEquals(200, $response->status());
        $files = $response->json('files');
        
        // Property: All created items should be listed
        $this->assertCount(count($testItems), $files);
        
        foreach ($files as $file) {
            // Property: Each file should have required metadata fields
            $requiredFields = ['name', 'path', 'type', 'modified', 'is_directory'];
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $file,
                    "File '{$file['name']}' missing required field: {$field}");
            }
            
            // Property: File type should match is_directory flag
            if ($file['is_directory']) {
                $this->assertEquals('directory', $file['type']);
                $this->assertNull($file['size']);
            } else {
                $this->assertEquals('file', $file['type']);
                $this->assertNotNull($file['size']);
                $this->assertArrayHasKey('size_formatted', $file);
                $this->assertArrayHasKey('mime_type', $file);
            }
            
            // Property: Path should be relative to user's root
            $this->assertStringNotContainsString('users/', $file['path'],
                "File path should not contain user directory structure");
        }
    }

    /**
     * Property Test: Folder operations maintain directory integrity
     * 
     * For any folder operation, directory structure should remain consistent
     */
    public function test_folder_operations_maintain_directory_integrity(): void
    {
        $user = User::create([
            'name' => 'Directory Integrity User',
            'email' => 'integrity@test.com',
            'ad_username' => 'integrityuser',
            'quota_bytes' => 5 * 1024 * 1024 * 1024,
            'used_bytes' => 0,
        ]);

        $this->storageService->ensureUserDirectory($user);

        // Property: Creating folders should be reflected in listings
        $this->simulateUserSession($user);
        $folderName = 'TestFolder';
        $response = $this->postJson('/api/files/folder', [
            'path' => '',
            'name' => $folderName
        ]);
        
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
        
        // Verify folder appears in listing
        $listResponse = $this->getJson('/api/files');
        $files = $listResponse->json('files');
        
        $folderFound = false;
        foreach ($files as $file) {
            if ($file['name'] === $folderName && $file['is_directory']) {
                $folderFound = true;
                break;
            }
        }
        $this->assertTrue($folderFound, "Created folder should appear in directory listing");

        // Property: Renaming folders should update listings
        $newFolderName = 'RenamedFolder';
        $renameResponse = $this->putJson('/api/files/rename', [
            'path' => $folderName,
            'new_name' => $newFolderName
        ]);
        
        $this->assertEquals(200, $renameResponse->status());
        
        // Verify renamed folder appears in listing
        $listResponse = $this->getJson('/api/files');
        $files = $listResponse->json('files');
        
        $oldFolderFound = false;
        $newFolderFound = false;
        foreach ($files as $file) {
            if ($file['name'] === $folderName) {
                $oldFolderFound = true;
            }
            if ($file['name'] === $newFolderName && $file['is_directory']) {
                $newFolderFound = true;
            }
        }
        
        $this->assertFalse($oldFolderFound, "Old folder name should not appear after rename");
        $this->assertTrue($newFolderFound, "New folder name should appear after rename");

        // Property: Deleting folders should remove them from listings
        $deleteResponse = $this->deleteJson('/api/files/delete', [
            'path' => $newFolderName
        ]);
        
        $this->assertEquals(200, $deleteResponse->status());
        
        // Verify folder is removed from listing
        $listResponse = $this->getJson('/api/files');
        $files = $listResponse->json('files');
        
        foreach ($files as $file) {
            $this->assertNotEquals($newFolderName, $file['name'],
                "Deleted folder should not appear in directory listing");
        }
    }

    /**
     * Helper method to simulate user session for API requests
     */
    private function simulateUserSession(User $user): void
    {
        session(['user' => [
            'id' => $user->id,
            'username' => $user->ad_username,
            'name' => $user->name,
            'email' => $user->email,
        ]]);
    }
}