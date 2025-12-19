## NIMR Storage (National Institute for Medical Research)

NIMR Storage is a Laravel + Inertia (Vue 3) file storage system that authenticates users against **Active Directory (LDAP/LDAPS)** and stores user files on a configured filesystem disk (typically a network storage share).

### Active Directory (LDAPS) configuration

This project uses `app/Services/LdapAuthService.php` and `config/ldap.php`.

Add these keys to your `.env`:

```bash
# AD / LDAP
LDAP_HOST=10.0.10.5
LDAP_SCHEME=ldaps
LDAP_PORT=636

# For domain "nimrhqs.local", the Base DN is typically:
LDAP_BASE_DN=DC=nimrhqs,DC=local

# Service account (recommended for searching users)
LDAP_BIND_DN=kmchaina@nimrhqs.local
LDAP_BIND_PASSWORD=***set-me***

# Optional: StartTLS is only for LDAP on 389, not LDAPS
LDAP_START_TLS=false

# Default quota for newly created users (GB)
DEFAULT_USER_QUOTA_GB=2
```

Notes:
- **LDAPS certificates**: the web server must trust the Domain Controller certificate chain. If login fails with TLS-related errors, install the issuing CA certificate on the web server.
- No login restrictions are enabled by default. If later you want restrictions by AD group, set `LDAP_ALLOWED_GROUP_DNS` (CSV of group DNs) in `.env`.

### Storage configuration (network share / remote server drive)

This project stores user files under:

`users/{ad_username}/files`

and uses the `lacie` filesystem disk (`config/filesystems.php`):

```bash
# Point this to a local path OR a UNC path:
LACIE_DRIVE_PATH=\\\\FILESERVER\\NIMRStorage
```

Important Windows note:
- If your storage is on another server, **use a UNC path** (`\\server\share`). Mapped drive letters (like `F:\`) often **won’t work** for Windows services (e.g., Apache/XAMPP) because services don’t see per-user mapped drives.
- Ensure the Windows account running the web server service has **read/write permissions** to the share and NTFS folder.

### How quotas work (App quota vs Windows quota)

This system has an **application-level quota**:
- Stored per user in the database as `users.quota_bytes`
- Updated by the app after uploads/deletes (and can be recalculated)
- Enforced by the app when uploading (so the UI can show “2GB max” etc.)

Windows quotas are a **separate layer**:
- **NTFS Disk Quotas** apply per Windows user account on the volume. Because the web app writes as the *web server service account*, NTFS per-user quotas usually **do not map to individual NIMR users** unless you implement Windows impersonation (not done here).
- **FSRM (File Server Resource Manager)** quotas can apply per-folder (recommended). You can apply an auto-quota template to `\\FILESERVER\NIMRStorage\users\*` so every user folder is capped at 2GB on the file server side.

Recommended setup:
- Use **FSRM per-folder quotas** on the storage server for hard enforcement.
- Keep the **app quota** enabled for UI/UX and early enforcement.

If Windows hard quota triggers first, uploads will fail at the filesystem level (the app will receive a write error). In that case we should surface a clean “quota exceeded” message to the user.
