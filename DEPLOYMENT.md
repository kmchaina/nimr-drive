# NIMR Drive - Deployment Guide

## Architecture Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Web Server    │────▶│  Storage Server │────▶│   LaCie Drive   │
│  (Laravel App)  │ SMB │   10.0.10.6     │     │   nimr-storage  │
│                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │
        │ LDAP (389)
        ▼
┌─────────────────┐
│   AD Server     │
│   10.0.10.5     │
│  nimrhqs.local  │
└─────────────────┘
```

## Server Requirements

| Component | Requirement |
|-----------|-------------|
| PHP | 8.1+ with extensions: ldap, fileinfo, pdo_sqlite |
| Web Server | Apache or Nginx |
| Composer | Latest version |
| Node.js | 18+ (for building assets) |
| Network | Access to AD (10.0.10.5:389) and Storage (10.0.10.6:445) |

---

## Option A: Deploy on Windows (Development)

### Step 1: Install XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. Install to `C:\xampp`
3. Enable PHP LDAP extension in `C:\xampp\php\php.ini`:
   ```ini
   extension=ldap
   extension=fileinfo
   
   ; Upload settings
   upload_max_filesize = 100M
   post_max_size = 100M
   max_execution_time = 600
   max_input_time = 600
   memory_limit = 512M
   ```
4. **Restart Apache** (required for php.ini changes to take effect)

### Step 2: Deploy Application

```cmd
cd C:\xampp\htdocs
git clone <repository-url> nimr-drive
cd nimr-drive

:: Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

:: Configure environment
copy .env.example .env
:: Edit .env with production settings (see Environment Configuration below)

:: Generate key and run migrations
php artisan key:generate
php artisan migrate --force

:: Set permissions
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

### Step 3: Connect to Network Storage (Critical)

The application needs persistent access to the SMB share. This is the most common issue after system restarts.

**Create a service account in Active Directory:**
- Username: `svc-nimrdrive`
- Grant read/write access to the `nimr-storage` share

**Create mount script** (`scripts/mount-storage.bat`):
```batch
@echo off
net use \\10.0.10.6\nimr-storage /delete /y >nul 2>&1
net use \\10.0.10.6\nimr-storage /user:NIMRHQS\svc-nimrdrive YourPassword /persistent:yes >nul 2>&1
exit /b 0
```

**Auto-mount on login:**
Copy the script to your Startup folder:
```
C:\Users\<YourUsername>\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup
```

> **Important:** The network share connection is lost after every restart. The mount script must run before Apache starts serving requests, otherwise users will see "Unable to create directory" errors.

### Step 4: Configure Apache Virtual Host

Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName storage.nimrhqs.local
    DocumentRoot "C:/xampp/htdocs/nimr-drive/public"
    
    <Directory "C:/xampp/htdocs/nimr-drive/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/nimr-drive-error.log"
    CustomLog "logs/nimr-drive-access.log" common
</VirtualHost>
```

---

## Option B: Deploy on Linux (Production - Recommended)

### Step 1: Install Dependencies (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-ldap php8.2-sqlite3 php8.2-xml \
    php8.2-curl php8.2-mbstring php8.2-zip nginx composer nodejs npm cifs-utils
```

### Step 2: Mount SMB Share (Critical for Persistence)

This is the most important step. The SMB mount must survive reboots and be available before the web server starts.

**Create mount point:**
```bash
sudo mkdir -p /mnt/nimr-storage
```

**Create credentials file** (use the AD service account):
```bash
sudo nano /etc/nimr-smbcredentials
```

Add:
```
username=svc-nimrdrive
password=YourServiceAccountPassword
domain=NIMRHQS
```

**Secure the credentials file:**
```bash
sudo chmod 600 /etc/nimr-smbcredentials
sudo chown root:root /etc/nimr-smbcredentials
```

**Add to `/etc/fstab` for persistent mount:**
```bash
sudo nano /etc/fstab
```

Add this line:
```
//10.0.10.6/nimr-storage /mnt/nimr-storage cifs credentials=/etc/nimr-smbcredentials,uid=www-data,gid=www-data,file_mode=0775,dir_mode=0775,_netdev,x-systemd.automount 0 0
```

Key options explained:
- `_netdev` - Wait for network before mounting
- `x-systemd.automount` - Systemd will handle mounting automatically

**Mount and verify:**
```bash
sudo mount -a
ls -la /mnt/nimr-storage
```

**Create systemd service for guaranteed mount** (optional but recommended):

```bash
sudo nano /etc/systemd/system/nimr-storage-mount.service
```

Add:
```ini
[Unit]
Description=Mount NIMR Storage Share
After=network-online.target
Wants=network-online.target
Before=nginx.service php8.2-fpm.service

[Service]
Type=oneshot
ExecStart=/bin/mount /mnt/nimr-storage
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
```

Enable the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable nimr-storage-mount.service
```

### Step 3: Deploy Application

```bash
cd /var/www
sudo git clone <repository-url> nimr-drive
cd nimr-drive

# Install dependencies
sudo composer install --no-dev --optimize-autoloader
sudo npm install && sudo npm run build

# Configure environment
sudo cp .env.example .env
sudo nano .env  # Edit with production settings (see below)

# Generate key and migrate
sudo php artisan key:generate
sudo php artisan migrate --force

# Set permissions
sudo chown -R www-data:www-data /var/www/nimr-drive
sudo chmod -R 775 storage bootstrap/cache
```

### Step 4: Configure Nginx

Create `/etc/nginx/sites-available/nimr-drive`:

```nginx
server {
    listen 80;
    server_name storage.nimrhqs.local;
    root /var/www/nimr-drive/public;

    index index.php;

    client_max_body_size 100M;
    client_body_timeout 300s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/nimr-drive /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 5: Configure PHP-FPM

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Step 6: Set Service Dependencies

Ensure web services start AFTER the storage is mounted:

```bash
sudo systemctl edit nginx.service
```

Add:
```ini
[Unit]
After=nimr-storage-mount.service
Requires=nimr-storage-mount.service
```

Do the same for PHP-FPM:
```bash
sudo systemctl edit php8.2-fpm.service
```

Reload systemd:
```bash
sudo systemctl daemon-reload
```

---

## Environment Configuration (.env)

```ini
APP_NAME="NIMR Drive"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://storage.nimrhqs.local

# Database
DB_CONNECTION=sqlite

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Storage Path
# Windows (UNC path):
# LACIE_DRIVE_PATH=//10.0.10.6/nimr-storage
# Linux (mount point):
LACIE_DRIVE_PATH=/mnt/nimr-storage

# Active Directory / LDAP
LDAP_HOST=10.0.10.5
LDAP_PORT=389
LDAP_BASE_DN=dc=nimrhqs,dc=local
LDAP_ACCOUNT_SUFFIX=@nimrhqs.local

# Optional: Service account for user lookups
LDAP_BIND_DN=
LDAP_BIND_PASSWORD=

# User Settings
DEFAULT_USER_QUOTA_GB=5
```

---

## Post-Deployment Checklist

- [ ] PHP LDAP extension is enabled (`php -m | grep ldap`)
- [ ] Storage mount is accessible (`ls /mnt/nimr-storage` or `dir \\10.0.10.6\nimr-storage`)
- [ ] Storage mount survives reboot (test with `sudo reboot`)
- [ ] Users can log in with AD credentials
- [ ] Files upload to correct user folders
- [ ] Test large file upload (100MB+)
- [ ] Check error logs: `storage/logs/laravel.log`
- [ ] Set up SSL certificate (recommended for production)
- [ ] Configure firewall rules
- [ ] Set up backup for SQLite database

---

## Troubleshooting

### "Unable to create directory" / Storage errors after restart

This is the most common issue. The SMB share is not mounted.

**Windows:**
```cmd
:: Check if share is connected
net use

:: Reconnect manually
net use \\10.0.10.6\nimr-storage /user:NIMRHQS\svc-nimrdrive Password

:: Verify PHP can access it
php -r "var_dump(is_dir('//10.0.10.6/nimr-storage'));"
```

**Linux:**
```bash
# Check mount status
df -h | grep nimr-storage
mount | grep nimr-storage

# Remount if needed
sudo mount /mnt/nimr-storage

# Check permissions
ls -la /mnt/nimr-storage
```

### "LDAP extension not loaded"

**Windows:** 
1. Edit `C:\xampp\php\php.ini`
2. Uncomment `extension=ldap`
3. **Restart Apache** (critical!)

**Linux:**
```bash
sudo apt install php8.2-ldap
sudo systemctl restart php8.2-fpm
```

### "LDAP connection failed"

```bash
# Test LDAP connectivity
telnet 10.0.10.5 389

# Or use ldapsearch
ldapsearch -x -H ldap://10.0.10.5 -b "dc=nimrhqs,dc=local"
```

### "Invalid credentials" on login

Check the logs for details:
```bash
tail -f storage/logs/laravel.log
```

The app tries multiple authentication formats:
1. UPN: `username@nimrhqs.local`
2. Down-level: `NIMRHQS\username`
3. Simple: `username`

### "Files uploaded but not showing"

```bash
php artisan cache:clear
php artisan config:clear
```

### Duplicate user folders (username + ID)

This was a bug that has been fixed. Old numbered folders can be safely deleted if empty.

---

## Security Recommendations

1. **Use HTTPS** - Install SSL certificate (Let's Encrypt or internal CA)
2. **Service Account** - Use dedicated AD service account `svc-nimrdrive` for SMB access
3. **Firewall** - Only allow necessary ports (80/443, 389, 445)
4. **Credentials Security** - On Linux, `/etc/nimr-smbcredentials` should be mode 600, owned by root
5. **Regular Backups** - Backup SQLite database (`database/database.sqlite`) and storage
6. **Log Monitoring** - Monitor `storage/logs/laravel.log` for errors

---

## Service Account Setup (Active Directory)

Create a dedicated service account for the application:

1. Open Active Directory Users and Computers
2. Create new user: `svc-nimrdrive`
3. Set password to never expire (or manage rotation)
4. Grant read/write permissions on the `nimr-storage` share
5. Use this account in mount scripts/credentials

This is more secure than using a personal user account and won't break when someone changes their password.

---

## Support

For issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Web server logs: Apache/Nginx error logs
3. PHP-FPM logs (Linux): `/var/log/php8.2-fpm.log`
4. System logs (Linux): `journalctl -u nimr-storage-mount.service`
