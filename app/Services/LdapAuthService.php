<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LdapAuthService
{
    private $ldapConnection = null;
    private $ldapHost;
    private $ldapScheme;
    private $ldapPort;
    private $ldapBaseDn;
    private $ldapBindDn;
    private $ldapBindPassword;
    private $ldapAccountSuffix;
    private $ldapStartTls;
    private $ldapFollowReferrals;
    private $ldapNetworkTimeout;
    private $ldapTimeLimit;
    private $allowedGroupDns;

    public function __construct()
    {
        $this->ldapHost = config('ldap.host', 'NIMRHQS');
        $this->ldapScheme = config('ldap.scheme', 'ldap');
        $this->ldapPort = config('ldap.port', 389);
        $this->ldapBaseDn = config('ldap.base_dn', 'dc=domain,dc=local');
        $this->ldapBindDn = config('ldap.bind_dn');
        $this->ldapBindPassword = config('ldap.bind_password');
        $this->ldapAccountSuffix = config('ldap.account_suffix', '');
        $this->ldapStartTls = (bool) config('ldap.start_tls', false);
        $this->ldapFollowReferrals = (bool) config('ldap.follow_referrals', false);
        $this->ldapNetworkTimeout = (int) config('ldap.network_timeout', 5);
        $this->ldapTimeLimit = (int) config('ldap.time_limit', 5);
        $this->allowedGroupDns = (array) config('ldap.allowed_group_dns', []);
    }

    /**
     * Authenticate user against Active Directory
     */
    public function authenticate(string $username, string $password): ?User
    {
        // For development/testing without LDAP extension
        if (!extension_loaded('ldap')) {
            Log::info('LDAP extension not loaded, using mock authentication');
            return $this->mockAuthentication($username, $password);
        }

        Log::info("LDAP auth attempt for user: {$username}");

        try {
            // Connect to LDAP server
            if (!$this->connect()) {
                Log::error('LDAP connection failed', [
                    'host' => $this->ldapHost,
                    'port' => $this->ldapPort,
                ]);
                return null;
            }

            Log::info('LDAP connection successful');

            // Authenticate user (two strategies):
            // 1) If a service bind is configured: search DN -> bind as user DN
            // 2) Otherwise: try UPN bind (username + suffix) then search user info
            $userDn = null;

            if ($this->ldapBindDn && $this->ldapBindPassword) {
                Log::info('Using service account bind strategy');
                $userDn = $this->findUserDn($username);
                if (!$userDn) {
                    Log::warning("User not found in AD: {$username}");
                    return null;
                }

                if (!@ldap_bind($this->ldapConnection, $userDn, $password)) {
                    Log::warning("Authentication failed for user: {$username}", [
                        'ldap_error' => ldap_error($this->ldapConnection),
                    ]);
                    return null;
                }
            } else {
                $upn = $this->buildUpn($username);
                $bindSuccess = false;

                // Try UPN format first (user@domain.local)
                if ($upn) {
                    Log::info("Attempting UPN bind", ['upn' => $upn]);
                    if (@ldap_bind($this->ldapConnection, $upn, $password)) {
                        Log::info('UPN bind successful');
                        $bindSuccess = true;
                    } else {
                        Log::info("UPN bind failed, will try other formats", [
                            'upn' => $upn,
                            'ldap_error' => ldap_error($this->ldapConnection),
                        ]);
                    }
                }

                // Try DOMAIN\username format (NetBIOS)
                if (!$bindSuccess) {
                    // Extract NetBIOS domain from account suffix or base DN
                    $netbiosDomain = $this->getNetBiosDomain();
                    if ($netbiosDomain) {
                        $downLevelName = "{$netbiosDomain}\\{$username}";
                        Log::info("Attempting down-level logon name bind", ['name' => $downLevelName]);
                        if (@ldap_bind($this->ldapConnection, $downLevelName, $password)) {
                            Log::info('Down-level logon name bind successful');
                            $bindSuccess = true;
                        } else {
                            Log::info("Down-level bind failed", [
                                'name' => $downLevelName,
                                'ldap_error' => ldap_error($this->ldapConnection),
                            ]);
                        }
                    }
                }

                // Try just username (some AD configs allow this)
                if (!$bindSuccess) {
                    Log::info("Attempting simple username bind", ['username' => $username]);
                    if (@ldap_bind($this->ldapConnection, $username, $password)) {
                        Log::info('Simple username bind successful');
                        $bindSuccess = true;
                    } else {
                        Log::warning("All bind attempts failed for user: {$username}", [
                            'ldap_error' => ldap_error($this->ldapConnection),
                        ]);
                        return null;
                    }
                }

                // Find DN after successful bind (for group checks, etc.)
                $userDn = $this->findUserDn($username);
            }

            // Get user information from AD
            $userInfo = $this->getUserInfo($username);
            if (!$userInfo) {
                Log::error("Failed to retrieve user info for: {$username}", [
                    'ldap_error' => ldap_error($this->ldapConnection),
                    'base_dn' => $this->ldapBaseDn,
                ]);
                return null;
            }

            Log::info('User info retrieved', ['userInfo' => $userInfo]);

            // Optional: restrict access to members of certain groups
            if (!empty($this->allowedGroupDns)) {
                if (!$this->isUserInAllowedGroups($username, $userDn)) {
                    Log::warning("User is not in allowed AD groups: {$username}");
                    return null;
                }
            }

            // Create or update user in local database
            return $this->createOrUpdateUser($userInfo);

        } catch (\Exception $e) {
            Log::error('LDAP authentication error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Connect to LDAP server
     */
    private function connect(): bool
    {
        $host = $this->ldapHost;
        if (!str_contains($host, '://')) {
            $host = rtrim($this->ldapScheme, ':/') . '://' . $host;
        }

        $this->ldapConnection = @ldap_connect($host, $this->ldapPort);
        
        if (!$this->ldapConnection) {
            return false;
        }

        // Set LDAP options
        @ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, $this->ldapFollowReferrals ? 1 : 0);
        if (defined('LDAP_OPT_NETWORK_TIMEOUT')) {
            @ldap_set_option($this->ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, $this->ldapNetworkTimeout);
        }
        if (defined('LDAP_OPT_TIMELIMIT')) {
            @ldap_set_option($this->ldapConnection, LDAP_OPT_TIMELIMIT, $this->ldapTimeLimit);
        }

        // StartTLS (only when using ldap://)
        if ($this->ldapStartTls && str_starts_with($host, 'ldap://')) {
            if (!@ldap_start_tls($this->ldapConnection)) {
                Log::warning('LDAP StartTLS failed; continuing without StartTLS.');
            }
        }

        // Bind with service account if configured
        if ($this->ldapBindDn && $this->ldapBindPassword) {
            return (bool) @ldap_bind($this->ldapConnection, $this->ldapBindDn, $this->ldapBindPassword);
        }

        return true;
    }

    /**
     * Find user DN in Active Directory
     */
    private function findUserDn(string $username): ?string
    {
        $safeUsername = $this->escapeFilterValue($username);
        $filter = "(sAMAccountName={$safeUsername})";
        $search = @ldap_search($this->ldapConnection, $this->ldapBaseDn, $filter);
        
        if (!$search) {
            return null;
        }

        $entries = @ldap_get_entries($this->ldapConnection, $search);
        
        if ($entries['count'] > 0) {
            return $entries[0]['dn'];
        }

        return null;
    }

    /**
     * Get user information from Active Directory
     */
    private function getUserInfo(string $username): ?array
    {
        $safeUsername = $this->escapeFilterValue($username);
        $filter = "(sAMAccountName={$safeUsername})";
        $attributes = ['cn', 'mail', 'sAMAccountName', 'objectGUID', 'displayName'];
        
        $search = @ldap_search($this->ldapConnection, $this->ldapBaseDn, $filter, $attributes);
        
        if (!$search) {
            return null;
        }

        $entries = @ldap_get_entries($this->ldapConnection, $search);
        
        if ($entries['count'] > 0) {
            $entry = $entries[0];
            
            return [
                'username' => $entry['samaccountname'][0] ?? $username,
                'email' => $entry['mail'][0] ?? null,
                'name' => $entry['cn'][0] ?? $username,
                'display_name' => $entry['displayname'][0] ?? $entry['cn'][0] ?? $username,
                'guid' => $this->guidToString($entry['objectguid'][0] ?? ''),
            ];
        }

        return null;
    }

    private function buildUpn(string $username): ?string
    {
        $suffix = (string) $this->ldapAccountSuffix;
        if ($suffix === '') {
            return null;
        }
        if (str_starts_with($suffix, '@')) {
            return $username . $suffix;
        }
        return $username . '@' . $suffix;
    }

    /**
     * Extract NetBIOS domain name from account suffix or base DN
     */
    private function getNetBiosDomain(): ?string
    {
        // Try to get from account suffix (e.g., @nimrhqs.local -> NIMRHQS)
        $suffix = (string) $this->ldapAccountSuffix;
        if ($suffix !== '') {
            $suffix = ltrim($suffix, '@');
            $parts = explode('.', $suffix);
            if (!empty($parts[0])) {
                return strtoupper($parts[0]);
            }
        }

        // Try to get from base DN (e.g., dc=nimrhqs,dc=local -> NIMRHQS)
        if (preg_match('/dc=([^,]+)/i', $this->ldapBaseDn, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function escapeFilterValue(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        // Fallback for older environments
        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value
        );
    }

    private function isUserInAllowedGroups(string $username, ?string $userDn): bool
    {
        $safeUsername = $this->escapeFilterValue($username);
        $filter = "(sAMAccountName={$safeUsername})";
        $attributes = ['memberOf'];

        $search = @ldap_search($this->ldapConnection, $this->ldapBaseDn, $filter, $attributes);
        if (!$search) {
            return false;
        }

        $entries = @ldap_get_entries($this->ldapConnection, $search);
        if (!$entries || ($entries['count'] ?? 0) < 1) {
            return false;
        }

        $memberOf = $entries[0]['memberof'] ?? null;
        if (!$memberOf || !isset($memberOf['count'])) {
            return false;
        }

        $userGroups = [];
        for ($i = 0; $i < $memberOf['count']; $i++) {
            $userGroups[] = $memberOf[$i];
        }

        // Any match is sufficient
        foreach ($this->allowedGroupDns as $allowedDn) {
            if ($allowedDn !== '' && in_array($allowedDn, $userGroups, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert binary GUID to string
     */
    private function guidToString(string $binaryGuid): string
    {
        if (empty($binaryGuid)) {
            return '';
        }

        $hex = bin2hex($binaryGuid);
        $guid = substr($hex, 6, 2) . substr($hex, 4, 2) . substr($hex, 2, 2) . substr($hex, 0, 2);
        $guid .= '-' . substr($hex, 10, 2) . substr($hex, 8, 2);
        $guid .= '-' . substr($hex, 14, 2) . substr($hex, 12, 2);
        $guid .= '-' . substr($hex, 16, 4);
        $guid .= '-' . substr($hex, 20, 12);

        return $guid;
    }

    /**
     * Create or update user in local database
     */
    private function createOrUpdateUser(array $userInfo): User
    {
        $user = User::where('ad_username', $userInfo['username'])->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $userInfo['name'],
                'email' => $userInfo['email'] ?? $userInfo['username'] . '@nimrhqs.local',
                'ad_username' => $userInfo['username'],
                'ad_guid' => $userInfo['guid'],
                'display_name' => $userInfo['display_name'],
                'quota_bytes' => (int) config('ldap.default_quota_gb', 5) * 1024 * 1024 * 1024,
                'used_bytes' => 0,
                'last_login' => now(),
            ]);

            // Create user directory on LaCie drive
            $this->createUserDirectory($user);
        } else {
            // Update existing user
            $user->update([
                'name' => $userInfo['name'],
                'email' => $userInfo['email'] ?? $user->email,
                'display_name' => $userInfo['display_name'],
                'last_login' => now(),
            ]);
        }

        return $user;
    }

    /**
     * Create user directory on LaCie drive
     */
    private function createUserDirectory(User $user): void
    {
        try {
            $disk = Storage::disk('lacie');
            $userPath = $this->getUserFolderPath($user);
            
            if (!$disk->exists($userPath)) {
                $disk->makeDirectory($userPath);
                Log::info("Created user directory: {$userPath}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to create user directory for user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Mock authentication for development (when LDAP extension is not available)
     */
    private function mockAuthentication(string $username, string $password): ?User
    {
        // Mock AD users for testing
        $mockUsers = [
            'admin' => [
                'password' => 'password',
                'name' => 'Administrator',
                'email' => 'admin@domain.local',
                'display_name' => 'System Administrator',
                'guid' => '12345678-1234-1234-1234-123456789012',
            ],
            'john.doe' => [
                'password' => 'password123',
                'name' => 'John Doe',
                'email' => 'john.doe@domain.local',
                'display_name' => 'John Doe',
                'guid' => '87654321-4321-4321-4321-210987654321',
            ],
            'jane.smith' => [
                'password' => 'password456',
                'name' => 'Jane Smith',
                'email' => 'jane.smith@domain.local',
                'display_name' => 'Jane Smith',
                'guid' => '11111111-2222-3333-4444-555555555555',
            ],
        ];

        if (!isset($mockUsers[$username]) || $mockUsers[$username]['password'] !== $password) {
            return null;
        }

        $userInfo = $mockUsers[$username];
        $userInfo['username'] = $username;

        return $this->createOrUpdateUser($userInfo);
    }

    /**
     * Disconnect from LDAP server
     */
    private function disconnect(): void
    {
        if ($this->ldapConnection) {
            ldap_close($this->ldapConnection);
            $this->ldapConnection = null;
        }
    }

    /**
     * Get user's folder path and check permissions
     */
    public function getUserFolderPath(User $user): string
    {
        $username = $user->ad_username ?: (string) $user->id;
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        return "users/{$username}/files";
    }

    /**
     * Check if user has access to a specific path
     */
    public function hasPathAccess(User $user, ?string $path): bool
    {
        // Null paths are not allowed
        if (is_null($path)) {
            return false;
        }
        
        // Empty paths and root paths are not allowed for security
        if (empty($path) || $path === '/' || $path === '') {
            return false;
        }
        
        // Normalize path
        $path = ltrim($path, '/\\');
        
        // Check for directory traversal attempts
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return false;
        }
        
        // System paths are forbidden
        $systemPaths = ['users', 'users/', 'system', 'admin', 'root'];
        if (in_array($path, $systemPaths)) {
            return false;
        }
        
        // If path starts with users/, check if it belongs to this user (absolute path)
        if (str_starts_with($path, 'users/')) {
            $username = $user->ad_username ?: (string) $user->id;
            $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
            $userFolderPath = "users/{$username}";
            return str_starts_with($path, $userFolderPath);
        }
        
        // For relative paths (not starting with users/), allow them as they will be scoped by controllers
        return true;
    }
}