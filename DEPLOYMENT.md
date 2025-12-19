# NIMR Drive - Deployment Guide

## Architecture Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Web Server    │────▶│  Windows Server │────▶│   LaCie Drive   │
│  (Laravel App)  │ SMB │   10.0.10.6     │     │   I:\nimr-storage
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

## Option A: Deploy on Windows

### Step 1: Install XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. Install to `C:\xampp`
3. Enable PHP extensions and configure limits in `C:\xampp\php\php.ini`:
   ```ini
   extension=ldap
   extension=fileinfo
   
   ; Upload settings (chunks are 2MB, allow headroom)
   upload_max_filesize = 100M
   post_max_size = 100M
   max_execution_time = 600
   max_input_time = 600
   memory_limit = 512M
   ```
4. Restart Apache

### Step 2: Deploy Application

```cmd
cd C:\xampp\htdocs
git clone <repository-url> nimr-storage
cd nimr-storage

:: Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

:: Configure environment
copy .env.example .env
:: Edit .env with production settings (see below)

:: Generate key and run migrations
php artisan key:generate
php artisan migrate --force

:: Set permissions
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
```

### Step 3: Connect to Remote Storage

```cmd
:: Connect to LaCie share (run as the user Apache runs as, or use persistent connection)
net use \\10.0.10.6\nimr-storage /user:nimrhqs\serviceaccount Password123 /persistent:yes
```

### Step 4: Configure Apache Virtual Host

Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName storage.nimrhqs.local
    DocumentRoot "C:/xampp/htdocs/nimr-storage/public"
    
    <Directory "C:/xampp/htdocs/nimr-storage/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/nimr-storage-error.log"
    CustomLog "logs/nimr-storage-access.log" common
</VirtualHost>
```

---

## Option B: Deploy on Linux (Recommended)

### Step 1: Install Dependencies (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-ldap php8.2-sqlite3 php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip
sudo apt install -y nginx composer nodejs npm cifs-utils
```

### Step 2: Mount SMB Share

Create mount point and credentials file:

```bash
sudo mkdir -p /mnt/nimr-storage
sudo nano /etc/smbcredentials
```

Add credentials (use a service account):
```
username=serviceaccount
password=YourPassword
domain=nimrhqs
```

Secure the file:
```bash
sudo chmod 600 /etc/smbcredentials
```

Add to `/etc/fstab` for persistent mount:
```
//10.0.10.6/nimr-storage /mnt/nimr-storage cifs credentials=/etc/smbcredentials,uid=www-data,gid=www-data,file_mode=0775,dir_mode=0775 0 0
```

Mount it:
```bash
sudo mount -a
```

### Step 3: Deploy Application

```bash
cd /var/www
sudo git clone <repository-url> nimr-storage
cd nimr-storage

# Install dependencies
sudo composer install --no-dev --optimize-autoloader
sudo npm install && sudo npm run build

# Configure environment
sudo cp .env.example .env
sudo nano .env  # Edit with production settings

# Generate key and migrate
sudo php artisan key:generate
sudo php artisan migrate --force

# Set permissions
sudo chown -R www-data:www-data /var/www/nimr-storage
sudo chmod -R 775 storage bootstrap/cache
```

### Step 4: Configure Nginx

Create `/etc/nginx/sites-available/nimr-storage`:

```nginx
server {
    listen 80;
    server_name storage.nimrhqs.local;
    root /var/www/nimr-storage/public;

    index index.php;

    # Increase upload limits (chunks are 2MB, but allow headroom)
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
sudo ln -s /etc/nginx/sites-available/nimr-storage /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 5: Configure PHP-FPM

Edit `/etc/php/8.2/fpm/php.ini`:
```ini
# Support for 2GB file uploads (chunked, but PHP still needs headroom)
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

# Storage - Remote LaCie via SMB
# Windows: use UNC path
# LACIE_DRIVE_PATH=//10.0.10.6/nimr-storage
# Linux: use mount point
LACIE_DRIVE_PATH=/mnt/nimr-storage

# Active Directory
LDAP_HOST=10.0.10.5
LDAP_PORT=389
LDAP_BASE_DN=dc=nimrhqs,dc=local
LDAP_ACCOUNT_SUFFIX=@nimrhqs.local
LDAP_BIND_DN=
LDAP_BIND_PASSWORD=

# User Settings
DEFAULT_USER_QUOTA_GB=5
```

---

## Post-Deployment Checklist

- [ ] Verify AD connection: Users can log in with domain credentials
- [ ] Verify storage: Files upload to `\\10.0.10.6\nimr-storage\users\`
- [ ] Test large file upload (100MB+)
- [ ] Test folder upload
- [ ] Check error logs: `storage/logs/laravel.log`
- [ ] Set up SSL certificate (recommended)
- [ ] Configure firewall rules
- [ ] Set up backup for SQLite database

---

## Troubleshooting

### "LDAP connection failed"
```bash
# Test LDAP connectivity
telnet 10.0.10.5 389
# Or on Windows:
Test-NetConnection -ComputerName 10.0.10.5 -Port 389
```

### "Cannot access storage"
```bash
# Linux: Check mount
df -h | grep nimr-storage
ls -la /mnt/nimr-storage

# Windows: Check connection
net use
dir \\10.0.10.6\nimr-storage
```

### "Files uploaded but not showing"
```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
```

### "429 Too Many Requests"
Rate limiting is set to 2000 requests/minute for uploads (supports 2GB files with 2MB chunks). For extremely large batch uploads, you may need to increase this in `routes/web.php`.

### "Session expired (419)"
```bash
# Clear sessions and cache
php artisan cache:clear
php artisan session:flush
```

---

## Security Recommendations

1. **Use HTTPS** - Install SSL certificate (Let's Encrypt or internal CA)
2. **Service Account** - Create a dedicated AD service account for SMB access
3. **Firewall** - Only allow necessary ports (80/443, 389, 445)
4. **File Permissions** - Don't use `Everyone:F` in production
5. **Regular Backups** - Backup SQLite database and LaCie storage
6. **Monitoring** - Set up log monitoring for errors

---

## Support

For issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Web server logs: Apache/Nginx error logs
3. PHP-FPM logs (Linux): `/var/log/php8.2-fpm.log`
