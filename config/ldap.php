<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP/Active Directory Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Active Directory server
    |
    */

    'host' => env('LDAP_HOST', 'NIMRHQS'),

    // ldap (389) or ldaps (636)
    'scheme' => env('LDAP_SCHEME', 'ldap'),
    'port' => (int) env('LDAP_PORT', 389),

    // e.g. "dc=nimr,dc=go,dc=tz"
    'base_dn' => env('LDAP_BASE_DN', 'dc=domain,dc=local'),

    // Optional service account for searching users (recommended for AD)
    'bind_dn' => env('LDAP_BIND_DN'),
    'bind_password' => env('LDAP_BIND_PASSWORD'),

    // If no bind_dn is set, we'll attempt user bind using: username + account_suffix
    // e.g. "@nimr.go.tz" or "@DOMAIN.LOCAL"
    'account_suffix' => env('LDAP_ACCOUNT_SUFFIX', ''),

    // StartTLS (only for ldap:// on 389). Ignored for ldaps://
    'start_tls' => (bool) env('LDAP_START_TLS', false),

    // Connection / search hardening
    'follow_referrals' => (bool) env('LDAP_FOLLOW_REFERRALS', false),
    'network_timeout' => (int) env('LDAP_NETWORK_TIMEOUT', 5), // seconds
    'time_limit' => (int) env('LDAP_TIME_LIMIT', 5), // seconds (server-side search time limit)

    /*
    |--------------------------------------------------------------------------
    | User Mapping
    |--------------------------------------------------------------------------
    |
    | How to map AD attributes to local user fields
    |
    */

    'user_attributes' => [
        'username' => 'sAMAccountName',
        'email' => 'mail',
        'name' => 'cn',
        'display_name' => 'displayName',
        'guid' => 'objectGUID',
    ],

    // Optional restrictions: only allow members of these AD groups (DNs).
    // Example (CSV in env): "CN=NIMR Storage Users,OU=Groups,DC=nimr,DC=go,DC=tz"
    'allowed_group_dns' => array_filter(array_map('trim', explode(',', (string) env('LDAP_ALLOWED_GROUP_DNS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Default User Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for new users created from AD
    |
    */

    'default_quota_gb' => (int) env('DEFAULT_USER_QUOTA_GB', 5),

];