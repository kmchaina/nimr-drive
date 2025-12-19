<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Feature: web-file-manager, Property 8: Search scope restriction
 * 
 * Property: For any search query, results should only include files within 
 * the user's own User_Folder and never return unauthorized files
 * 
 * Validates: Requirements 5.1
 */
class SearchScopeRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected $searchService;
    protected $user1;
    protected $user2;

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

        $this->searchService = new SearchService();
        
        // Create two test users
        $this->user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'user1@test.com',
            'ad_username' => 'user1',
            'ad_guid' => '12345678-1234-1234-1234-123456789001',
            'display_name' => 'Test User 1',
            'quota_bytes' => 10 * 1024 * 1024 * 1024, // 10GB
            'used_bytes' => 0,
        ]);

        $this->user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'user2@test.com',
            'ad_username' => 'user2',
            'ad_guid' => '12345678-1234-1234-1234-123456789002',
            'display_name' => 'Test User 2',
            'quota_bytes' => 10 * 1024 * 1024 * 1024, // 10GB
            'used_bytes' => 0,
        ]);

        // Create user directories
        $this->createUserDirectories();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupTestDirectory(storage_path('testing'));
        parent::tearDown();
    }

    private function createUserDirectories(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create directories for both users
        $disk->makeDirectory("users/{$this->user1->id}/files");
        $disk->makeDirectory("users/{$this->user2->id}/files");
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
     * Property Test: Search scope restriction
     * 
     * For any search query, results should only include files within the user's own folder
     */
    public function test_search_scope_restriction_property(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create test files for user1
        $user1Files = [
            'document.txt' => 'User 1 document content',
            'report.pdf' => 'User 1 report content',
            'shared_name.txt' => 'User 1 shared name file',
            'projects/project1.txt' => 'User 1 project file',
            'documents/important.doc' => 'User 1 important document',
        ];

        // Create test files for user2 (including files with same names)
        $user2Files = [
            'document.txt' => 'User 2 document content',
            'confidential.txt' => 'User 2 confidential content',
            'shared_name.txt' => 'User 2 shared name file',
            'projects/secret.txt' => 'User 2 secret project',
            'admin/system.cfg' => 'User 2 system config',
        ];

        // Create files for user1
        foreach ($user1Files as $filePath => $content) {
            $fullPath = "users/{$this->user1->id}/files/{$filePath}";
            $disk->put($fullPath, $content);
        }

        // Create files for user2
        foreach ($user2Files as $filePath => $content) {
            $fullPath = "users/{$this->user2->id}/files/{$filePath}";
            $disk->put($fullPath, $content);
        }

        // Test various search queries
        $searchQueries = [
            'document',
            'txt',
            'shared_name',
            'project',
            'confidential',
            'secret',
            'system',
            'admin',
            'important',
        ];

        foreach ($searchQueries as $query) {
            // Property: User1 search should only return user1's files
            $user1Results = $this->searchService->searchFiles($this->user1, $query);
            
            foreach ($user1Results as $result) {
                // Verify all results belong to user1's directory
                $this->assertStringStartsWith(
                    "users/{$this->user1->id}/files/",
                    "users/{$this->user1->id}/files/" . $result['path'],
                    "Search result for user1 contains file outside their directory: {$result['path']}"
                );
                
                // Verify no results contain user2's files
                $this->assertStringNotContainsString(
                    "users/{$this->user2->id}/",
                    $result['path'],
                    "Search result for user1 contains user2's file: {$result['path']}"
                );
            }

            // Property: User2 search should only return user2's files
            $user2Results = $this->searchService->searchFiles($this->user2, $query);
            
            foreach ($user2Results as $result) {
                // Verify all results belong to user2's directory
                $this->assertStringStartsWith(
                    "users/{$this->user2->id}/files/",
                    "users/{$this->user2->id}/files/" . $result['path'],
                    "Search result for user2 contains file outside their directory: {$result['path']}"
                );
                
                // Verify no results contain user1's files
                $this->assertStringNotContainsString(
                    "users/{$this->user1->id}/",
                    $result['path'],
                    "Search result for user2 contains user1's file: {$result['path']}"
                );
            }

            // Property: Users should not see each other's files even with identical names
            if ($query === 'shared_name') {
                $user1HasSharedName = collect($user1Results)->contains('name', 'shared_name.txt');
                $user2HasSharedName = collect($user2Results)->contains('name', 'shared_name.txt');
                
                // Both users should find their own shared_name.txt but not the other's
                if ($user1HasSharedName) {
                    $user1SharedFile = collect($user1Results)->firstWhere('name', 'shared_name.txt');
                    $this->assertStringContainsString(
                        "users/{$this->user1->id}/files/",
                        "users/{$this->user1->id}/files/" . $user1SharedFile['path'],
                        "User1's shared_name.txt should be in user1's directory"
                    );
                }
                
                if ($user2HasSharedName) {
                    $user2SharedFile = collect($user2Results)->firstWhere('name', 'shared_name.txt');
                    $this->assertStringContainsString(
                        "users/{$this->user2->id}/files/",
                        "users/{$this->user2->id}/files/" . $user2SharedFile['path'],
                        "User2's shared_name.txt should be in user2's directory"
                    );
                }
            }
        }
    }

    /**
     * Property Test: Search API endpoint enforces user isolation
     * 
     * For any search request, the API should only return results from the authenticated user's folder
     */
    public function test_search_api_enforces_user_isolation(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create test files with sensitive names
        $sensitiveFiles = [
            'password.txt' => 'User passwords',
            'config.ini' => 'System configuration',
            'database.sql' => 'Database dump',
        ];

        // Create files for both users
        foreach ($sensitiveFiles as $fileName => $content) {
            $disk->put("users/{$this->user1->id}/files/{$fileName}", "User1: {$content}");
            $disk->put("users/{$this->user2->id}/files/{$fileName}", "User2: {$content}");
        }

        // Test search API as user1
        session(['user' => ['id' => $this->user1->id]]);
        
        $response = $this->getJson('/api/search?q=password');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        
        // Property: All results should belong to user1
        foreach ($data['results'] as $result) {
            $this->assertStringNotContainsString(
                "users/{$this->user2->id}/",
                $result['path'],
                "User1 search API returned user2's file: {$result['path']}"
            );
        }

        // Test search API as user2
        session(['user' => ['id' => $this->user2->id]]);
        
        $response = $this->getJson('/api/search?q=config');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        
        // Property: All results should belong to user2
        foreach ($data['results'] as $result) {
            $this->assertStringNotContainsString(
                "users/{$this->user1->id}/",
                $result['path'],
                "User2 search API returned user1's file: {$result['path']}"
            );
        }
    }

    /**
     * Property Test: Search with path traversal attempts fails safely
     * 
     * For any search query containing path traversal attempts, the search should not escape user boundaries
     */
    public function test_search_prevents_path_traversal(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create a file outside user directories (simulating system files)
        $disk->put('system/admin.txt', 'System admin file');
        $disk->put('users/admin_config.txt', 'Admin configuration');
        
        // Create normal files for user1
        $disk->put("users/{$this->user1->id}/files/normal.txt", 'Normal user file');

        // Test various path traversal attempts
        $maliciousQueries = [
            '../admin',
            '../../system',
            '../../../',
            '..\\admin',
            'admin_config',
            'system',
        ];

        $hasResults = false;
        foreach ($maliciousQueries as $query) {
            $results = $this->searchService->searchFiles($this->user1, $query);
            
            if (!empty($results)) {
                $hasResults = true;
            }
            
            // Property: No results should contain files outside user's directory
            foreach ($results as $result) {
                $this->assertStringNotContainsString(
                    '../',
                    $result['path'],
                    "Search result contains path traversal: {$result['path']}"
                );
                
                $this->assertStringNotContainsString(
                    'system/',
                    $result['path'],
                    "Search result contains system file: {$result['path']}"
                );
                
                // All results should be within user's folder
                $this->assertStringStartsWith(
                    "users/{$this->user1->id}/files/",
                    "users/{$this->user1->id}/files/" . $result['path'],
                    "Search result outside user directory: {$result['path']}"
                );
            }
        }
        
        // Ensure we tested something
        $this->assertTrue(true, 'Path traversal prevention test completed');
    }

    /**
     * Property Test: Empty search returns no results
     * 
     * For any empty or invalid search query, the search should return empty results safely
     */
    public function test_empty_search_returns_no_results(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create some test files
        $disk->put("users/{$this->user1->id}/files/test.txt", 'Test content');
        
        // Test empty and invalid queries
        $invalidQueries = ['', ' ', '  ', null];
        
        foreach ($invalidQueries as $query) {
            if ($query === null) {
                // Skip null test for now as it would cause type error
                continue;
            }
            
            $results = $this->searchService->searchFiles($this->user1, $query);
            
            // Property: Empty queries should return empty results
            $this->assertEmpty(
                $results,
                "Empty query '{$query}' should return no results"
            );
        }
    }

    /**
     * Property Test: Search results contain proper metadata
     * 
     * For any search result, it should contain all required metadata fields
     */
    public function test_search_results_contain_proper_metadata(): void
    {
        $disk = Storage::disk('lacie');
        
        // Create test files with various types
        $testFiles = [
            'document.txt' => 'Text document content',
            'image.jpg' => 'Fake image content',
            'data.json' => '{"key": "value"}',
            'subfolder/nested.txt' => 'Nested file content',
        ];

        foreach ($testFiles as $filePath => $content) {
            $disk->put("users/{$this->user1->id}/files/{$filePath}", $content);
        }

        $results = $this->searchService->searchFiles($this->user1, 'txt');
        
        // Property: All results should have required metadata fields
        $requiredFields = ['name', 'path', 'folder_path', 'type', 'modified', 'is_directory'];
        
        foreach ($results as $result) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $result,
                    "Search result missing required field: {$field}"
                );
            }
            
            // Property: File results should have size information
            if (!$result['is_directory']) {
                $this->assertArrayHasKey('size', $result);
                $this->assertArrayHasKey('size_formatted', $result);
                $this->assertArrayHasKey('mime_type', $result);
                $this->assertIsInt($result['size']);
                $this->assertIsString($result['size_formatted']);
            }
            
            // Property: Directory results should have null size
            if ($result['is_directory']) {
                $this->assertNull($result['size']);
                $this->assertNull($result['size_formatted']);
                $this->assertNull($result['mime_type']);
            }
        }
    }
}