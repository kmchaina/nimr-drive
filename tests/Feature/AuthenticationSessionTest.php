<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Services\LdapAuthService;
use App\Models\User;

/**
 * Feature: web-file-manager, Property 10: Authentication creates valid sessions
 * 
 * Property: For any successful authentication, the system should establish a secure session 
 * with proper timeout and security measures
 * 
 * Validates: Requirements 7.2, 7.4
 */
class AuthenticationSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $ldapAuthService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->ldapAuthService = new LdapAuthService();
        
        // Set up test LaCie drive
        config(['filesystems.disks.lacie.root' => storage_path('testing/lacie')]);
        
        // Create test directory structure
        if (!file_exists(storage_path('testing/lacie'))) {
            mkdir(storage_path('testing/lacie'), 0755, true);
        }
        if (!file_exists(storage_path('testing/lacie/users'))) {
            mkdir(storage_path('testing/lacie/users'), 0755, true);
        }
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
     * Property Test: Successful authentication creates valid sessions
     * 
     * For any valid user credentials, authentication should create a secure session
     */
    public function test_successful_authentication_creates_valid_sessions(): void
    {
        // Test with multiple valid user credentials
        $testUsers = [
            ['username' => 'admin', 'password' => 'password'],
            ['username' => 'john.doe', 'password' => 'password123'],
            ['username' => 'jane.smith', 'password' => 'password456'],
        ];

        foreach ($testUsers as $credentials) {
            // Property: Authentication should create a user record
            $user = $this->ldapAuthService->authenticate(
                $credentials['username'], 
                $credentials['password']
            );
            
            $this->assertNotNull($user, "Authentication failed for user: {$credentials['username']}");
            $this->assertInstanceOf(User::class, $user);
            
            // Property: User should have required AD fields
            $this->assertNotNull($user->ad_username);
            $this->assertNotNull($user->name);
            $this->assertNotNull($user->email);
            $this->assertNotNull($user->display_name);
            
            // Property: User should have quota information
            $this->assertGreaterThan(0, $user->quota_bytes);
            $this->assertGreaterThanOrEqual(0, $user->used_bytes);
            
            // Property: User directory should be created on LaCie drive
            $disk = Storage::disk('lacie');
            $userPath = "users/{$user->id}/files";
            $this->assertTrue($disk->exists($userPath), "User directory not created: {$userPath}");
            
            // Property: Last login should be updated
            $this->assertNotNull($user->last_login);
            $this->assertTrue($user->last_login->isToday());
        }
    }

    /**
     * Property Test: Invalid credentials do not create sessions
     * 
     * For any invalid credentials, authentication should fail securely
     */
    public function test_invalid_credentials_do_not_create_sessions(): void
    {
        $invalidCredentials = [
            ['username' => 'admin', 'password' => 'wrongpassword'],
            ['username' => 'nonexistent', 'password' => 'password'],
            ['username' => '', 'password' => 'password'],
            ['username' => 'admin', 'password' => ''],
            ['username' => 'john.doe', 'password' => 'wrongpass'],
        ];

        foreach ($invalidCredentials as $credentials) {
            // Property: Invalid authentication should return null
            $user = $this->ldapAuthService->authenticate(
                $credentials['username'], 
                $credentials['password']
            );
            
            $this->assertNull($user, "Authentication should fail for invalid credentials");
        }
    }

    /**
     * Property Test: Session data contains required security information
     * 
     * For any authenticated user, session should contain proper security data
     */
    public function test_session_data_contains_required_security_information(): void
    {
        // Authenticate a test user
        $user = $this->ldapAuthService->authenticate('admin', 'password');
        $this->assertNotNull($user);

        // Simulate login request
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        // Property: Login should redirect to dashboard
        $response->assertRedirect('/dashboard');

        // Property: Session should contain user data
        $sessionUser = session('user');
        $this->assertNotNull($sessionUser);
        
        // Property: Session should contain required fields
        $requiredFields = ['id', 'username', 'name', 'display_name', 'email', 'quota_bytes', 'used_bytes', 'folder_path'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $sessionUser, "Session missing required field: {$field}");
        }

        // Property: Session data should match user model
        $this->assertEquals($user->id, $sessionUser['id']);
        $this->assertEquals($user->ad_username, $sessionUser['username']);
        $this->assertEquals($user->name, $sessionUser['name']);
        $this->assertEquals($user->email, $sessionUser['email']);
    }

    /**
     * Property Test: User isolation is enforced through path validation
     * 
     * For any authenticated user, path access should be restricted to their folder
     */
    public function test_user_isolation_enforced_through_path_validation(): void
    {
        // Create multiple test users
        $user1 = $this->ldapAuthService->authenticate('admin', 'password');
        $user2 = $this->ldapAuthService->authenticate('john.doe', 'password123');
        
        $this->assertNotNull($user1);
        $this->assertNotNull($user2);

        // Property: User should have access to their own folder
        $user1Path = "users/{$user1->id}/files";
        $user2Path = "users/{$user2->id}/files";
        
        $this->assertTrue(
            $this->ldapAuthService->hasPathAccess($user1, $user1Path),
            "User should have access to their own folder"
        );
        
        $this->assertTrue(
            $this->ldapAuthService->hasPathAccess($user2, $user2Path),
            "User should have access to their own folder"
        );

        // Property: User should NOT have access to other users' folders
        $this->assertFalse(
            $this->ldapAuthService->hasPathAccess($user1, $user2Path),
            "User should not have access to other users' folders"
        );
        
        $this->assertFalse(
            $this->ldapAuthService->hasPathAccess($user2, $user1Path),
            "User should not have access to other users' folders"
        );

        // Property: User should not have access to root or system paths
        $forbiddenPaths = [
            '',
            '/',
            'users',
            'users/',
            'system',
            '../',
            '../../',
            'users/999/files',
        ];

        foreach ($forbiddenPaths as $path) {
            $this->assertFalse(
                $this->ldapAuthService->hasPathAccess($user1, $path),
                "User should not have access to forbidden path: {$path}"
            );
        }
    }

    /**
     * Property Test: Session timeout and security measures
     * 
     * For any session, proper timeout and security measures should be enforced
     */
    public function test_session_timeout_and_security_measures(): void
    {
        // Test login
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        // Property: Session should be created
        $this->assertNotNull(session('user'));

        // Property: Session check endpoint should confirm authentication
        $response = $this->get('/api/session');
        $response->assertStatus(200);
        $response->assertJson(['authenticated' => true]);

        // Property: Logout should clear session
        $response = $this->post('/logout');
        $response->assertRedirect('/login');

        // Property: Session should be cleared after logout
        $this->assertNull(session('user'));

        // Property: Session check should fail after logout
        $response = $this->get('/api/session');
        $response->assertStatus(401);
        $response->assertJson(['authenticated' => false]);
    }

    /**
     * Property Test: User creation and updates work correctly
     * 
     * For any user authentication, user records should be created or updated properly
     */
    public function test_user_creation_and_updates_work_correctly(): void
    {
        $username = 'admin';
        $password = 'password';

        // Property: First authentication should create user
        $user1 = $this->ldapAuthService->authenticate($username, $password);
        $this->assertNotNull($user1);
        
        $originalId = $user1->id;
        $originalCreatedAt = $user1->created_at;

        // Property: Second authentication should update existing user
        $user2 = $this->ldapAuthService->authenticate($username, $password);
        $this->assertNotNull($user2);
        
        // Property: Should be the same user record
        $this->assertEquals($originalId, $user2->id);
        $this->assertEquals($originalCreatedAt, $user2->created_at);
        
        // Property: Last login should be updated
        $this->assertTrue($user2->last_login->isToday());
        
        // Property: User count should not increase
        $this->assertEquals(1, User::where('ad_username', $username)->count());
    }

    /**
     * Property Test: Quota calculations and user folder paths
     * 
     * For any authenticated user, quota and folder path calculations should be correct
     */
    public function test_quota_calculations_and_user_folder_paths(): void
    {
        $user = $this->ldapAuthService->authenticate('admin', 'password');
        $this->assertNotNull($user);

        // Property: User should have default quota
        $defaultQuotaGb = config('ldap.default_quota_gb', 5);
        $expectedQuotaBytes = $defaultQuotaGb * 1024 * 1024 * 1024;
        $this->assertEquals($expectedQuotaBytes, $user->quota_bytes);

        // Property: Folder path should be correctly formatted
        $expectedPath = "users/{$user->id}/files";
        $this->assertEquals($expectedPath, $user->folder_path);

        // Property: Quota usage percentage should be calculated correctly
        $user->used_bytes = $user->quota_bytes / 2; // 50% usage
        $user->save();
        
        $this->assertEquals(50.0, $user->quota_usage_percentage);
        
        // Property: Quota limit checks should work
        $user->used_bytes = $user->quota_bytes * 0.9; // 90% usage
        $user->save();
        
        $this->assertTrue($user->isApproachingQuotaLimit());
        $this->assertFalse($user->hasExceededQuota());
        
        $user->used_bytes = $user->quota_bytes + 1; // Over quota
        $user->save();
        
        $this->assertTrue($user->hasExceededQuota());
    }
}