# Deployment Guide - SIMRS

> Sistem Informasi Manajemen Rumah Sakit - Production Deployment

## Table of Contents

- [Server Requirements](#server-requirements)
- [Environment Setup](#environment-setup)
- [Application Deployment](#application-deployment)
- [Database Migration](#database-migration)
- [Storage Permissions](#storage-permissions)
- [Queue Worker Setup](#queue-worker-setup)
- [Schedule Task Setup](#schedule-task-setup)
- [Web Server Configuration](#web-server-configuration)
- [SSL Configuration](#ssl-configuration)
- [Backup Strategy](#backup-strategy)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)

---

## Server Requirements

### Minimum Hardware Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 4 GB | 8+ GB |
| Storage | 50 GB SSD | 100+ GB SSD |
| Network | 10 Mbps | 100+ Mbps |

### Software Requirements

- **Operating System**: Ubuntu 22.04 LTS or 24.04 LTS
- **Web Server**: Nginx 1.24+ or Apache 2.4+
- **PHP**: 8.2+ with FPM
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Cache**: Redis 7.0+ (recommended)
- **Queue**: Redis or Database
- **Process Manager**: Supervisor or systemd

### Required PHP Extensions

```bash
sudo apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-json \
    php8.2-intl \
    php8.2-opcache \
    php8.2-readline \
    php8.2-redis \
    php8.2-sqlite3
```

---

## Environment Setup

### 1. Server Provisioning

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install essential packages
sudo apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    supervisor \
    nginx \
    redis-server \
    mysql-server-8.0 \
    certbot \
    python3-certbot-nginx
```

### 2. User Setup

```bash
# Create deployment user
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG sudo deploy

# Set password
sudo passwd deploy

# Create application directory
sudo mkdir -p /var/www/simrs
sudo chown deploy:deploy /var/www/simrs
```

### 3. Database Setup

```bash
# Login to MySQL
sudo mysql -u root

# Create database and user
CREATE DATABASE simrs_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs_user'@'localhost' IDENTIFIED BY 'strong_random_password';
GRANT ALL PRIVILEGES ON simrs_production.* TO 'simrs_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Secure MySQL
sudo mysql_secure_installation
```

### 4. PHP-FPM Configuration

```bash
# Edit PHP-FPM pool configuration
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Recommended settings:

```ini
[simrs]
user = deploy
group = deploy
listen = /run/php/php8.2-fpm-simrs.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Environment variables
env[APP_ENV] = production
env[APP_DEBUG] = false

; Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_flag[allow_url_fopen] = off
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

---

## Application Deployment

### 1. Initial Deployment

```bash
# SSH as deploy user
ssh deploy@your-server

# Clone repository
cd /var/www/simrs
git clone https://github.com/your-org/simrs.git .

# Copy environment file
cp .env.example .env

# Install dependencies (no dev)
composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### 2. Environment Configuration

Edit `.env`:

```env
APP_NAME=SIMRS
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://simrs.your-hospital.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simrs_production
DB_USERNAME=simrs_user
DB_PASSWORD=strong_random_password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-mail.com
MAIL_PORT=587
MAIL_USERNAME=notifications@your-hospital.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notifications@your-hospital.com
MAIL_FROM_NAME="${APP_NAME}"

# Filament Configuration
FILAMENT_PATH=admin
FILAMENT_DOMAIN=null
```

Generate key:
```bash
php artisan key:generate
```

### 3. Build Assets

```bash
# Install Node dependencies
npm ci

# Build production assets
npm run build
```

---

## Database Migration

### Safe Migration Strategy

```bash
# Enter maintenance mode
php artisan down --render="errors::503"

# Backup database first
mysqldump -u simrs_user -p simrs_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
php artisan migrate --force

# Clear cache
php artisan optimize:clear

# Cache config and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Exit maintenance mode
php artisan up
```

### Rollback Plan

```bash
# If migration fails, restore from backup
mysql -u simrs_user -p simrs_production < backup_YYYYMMDD_HHMMSS.sql
```

---

## Storage Permissions

```bash
# Set proper ownership
sudo chown -R deploy:www-data /var/www/simrs

# Storage directory
sudo chmod -R 775 /var/www/simrs/storage
sudo chmod -R 775 /var/www/simrs/bootstrap/cache

# Upload directories
sudo chmod -R 775 /var/www/simrs/storage/app/public
sudo chmod -R 775 /var/www/simrs/storage/app/uploads

# Create storage link
php artisan storage:link

# SELinux (if enabled)
sudo chcon -R -t httpd_sys_rw_content_t /var/www/simrs/storage
sudo chcon -R -t httpd_sys_rw_content_t /var/www/simrs/bootstrap/cache
```

---

## Queue Worker Setup

### Using Supervisor

```bash
sudo nano /etc/supervisor/conf.d/simrs-worker.conf
```

Add configuration:

```ini
[program:simrs-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/simrs/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/simrs
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/simrs/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start simrs-worker:*
```

Monitor workers:
```bash
sudo supervisorctl status simrs-worker:*
sudo tail -f /var/www/simrs/storage/logs/worker.log
```

### Queue Restart After Deployment

Add to deployment script:
```bash
php artisan queue:restart
```

---

## Schedule Task Setup

### Cron Job

```bash
# Edit crontab for deploy user
crontab -e
```

Add:

```cron
# SIMRS Schedule
* * * * * cd /var/www/simrs && php artisan schedule:run >> /dev/null 2>&1
```

Verify:
```bash
crontab -l
```

### Monitoring Scheduled Tasks

```bash
# View scheduled tasks
php artisan schedule:list

# Run specific command
php artisan schedule:run
```

---

## Web Server Configuration

### Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/simrs
```

Configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name simrs.your-hospital.com;
    root /var/www/simrs/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

    # Gzip compression
gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Client body size
    client_max_body_size 50M;

    # Location blocks
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.(env|gitignore|gitattributes|lock)$ {
        deny all;
    }

    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests)/ {
        deny all;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm-simrs.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Timeouts
        fastcgi_connect_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;
        
        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Logging
    access_log /var/log/nginx/simrs-access.log;
    error_log /var/log/nginx/simrs-error.log;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/simrs /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## SSL Configuration

### Using Let's Encrypt (Certbot)

```bash
# Obtain certificate
sudo certbot --nginx -d simrs.your-hospital.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### Manual SSL Configuration

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name simrs.your-hospital.com;
    root /var/www/simrs/public;

    # SSL certificates
    ssl_certificate /etc/nginx/ssl/simrs.crt;
    ssl_certificate_key /etc/nginx/ssl/simrs.key;

    # SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;

    # Include rest of configuration...
    include /etc/nginx/snippets/simrs-common.conf;
}

# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name simrs.your-hospital.com;
    return 301 https://$server_name$request_uri;
}
```

---

## Backup Strategy

### Database Backup

#### Daily Automated Backup

```bash
sudo nano /usr/local/bin/backup-simrs-db.sh
```

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/simrs/database"
DB_NAME="simrs_production"
DB_USER="simrs_user"
DB_PASS="your_password"
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS --single-transaction --routines --triggers $DB_NAME | gzip > $BACKUP_DIR/simrs_db_$DATE.sql.gz

# Remove old backups
find $BACKUP_DIR -name "simrs_db_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Sync to remote storage (optional)
# aws s3 sync $BACKUP_DIR s3://your-backup-bucket/simrs/database/
```

Make executable and schedule:
```bash
sudo chmod +x /usr/local/bin/backup-simrs-db.sh
sudo crontab -e
```

```cron
# Database backup daily at 2 AM
0 2 * * * /usr/local/bin/backup-simrs-db.sh >> /var/log/backup-simrs.log 2>&1
```

### File Backup

```bash
sudo nano /usr/local/bin/backup-simrs-files.sh
```

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/simrs/files"
APP_DIR="/var/www/simrs"
RETENTION_DAYS=14

mkdir -p $BACKUP_DIR

# Backup uploaded files and storage
tar -czf $BACKUP_DIR/simrs_files_$DATE.tar.gz -C $APP_DIR storage/app/public

# Remove old backups
find $BACKUP_DIR -name "simrs_files_*.tar.gz" -mtime +$RETENTION_DAYS -delete
```

### Using Laravel Backup (Spatie)

Install package:
```bash
composer require spatie/laravel-backup

php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configure in `config/backup.php`:

```php
'destination' => [
    'disks' => [
        'local',
        's3', // optional
    ],
],

'source' => [
    'files' => [
        'include' => [
            base_path('storage/app/public'),
        ],
        'exclude' => [
            base_path('vendor'),
            base_path('node_modules'),
        ],
    ],
],

'notifications' => [
    'mail' => [
        'to' => 'admin@your-hospital.com',
    ],
],
```

Schedule backup:
```cron
# Daily backup at 1 AM
0 1 * * * cd /var/www/simrs && php artisan backup:run >> /dev/null 2>&1

# Cleanup old backups weekly
0 3 * * 0 cd /var/www/simrs && php artisan backup:clean >> /dev/null 2>&1
```

---

## Monitoring

### Log Monitoring

```bash
# Application logs
sudo tail -f /var/www/simrs/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/simrs-error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# System logs
sudo journalctl -u php8.2-fpm -f
sudo journalctl -u nginx -f
```

### Health Checks

Create health check endpoint:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version'),
    ]);
});
```

### Monitoring Tools

Recommended monitoring stack:
- **Server**: Netdata, Prometheus + Grafana
- **Application**: Laravel Telescope (dev), Sentry (production)
- **Uptime**: UptimeRobot, Pingdom
- **Logs**: ELK Stack or Papertrail

---

## Troubleshooting

### Common Issues

#### 500 Internal Server Error

```bash
# Check logs
tail -f /var/www/simrs/storage/logs/laravel.log

# Clear caches
php artisan cache:clear
php artisan config:clear

# Check permissions
ls -la /var/www/simrs/storage
```

#### Queue Not Processing

```bash
# Check supervisor status
sudo supervisorctl status simrs-worker:*

# Restart workers
sudo supervisorctl restart simrs-worker:*

# Check for failed jobs
php artisan queue:failed
php artisan queue:retry all
```

#### Database Connection Issues

```bash
# Test MySQL connection
mysql -u simrs_user -p -e "SELECT 1;"

# Check MySQL status
sudo systemctl status mysql

# Restart MySQL
sudo systemctl restart mysql
```

#### High Memory Usage

```bash
# Check PHP-FPM memory
ps aux | grep php-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check Redis memory
redis-cli INFO memory
```

### Emergency Rollback

```bash
# Put in maintenance mode
php artisan down

# Restore database
mysql -u simrs_user -p simrs_production < backup_YYYYMMDD_HHMMSS.sql

# Restore code
git checkout previous-commit-hash

# Clear caches
php artisan optimize:clear

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Exit maintenance mode
php artisan up
```

---

## Deployment Automation

### Deploy Script

```bash
#!/bin/bash
# /usr/local/bin/deploy-simrs.sh

set -e

DEPLOY_DIR="/var/www/simrs"
USER="deploy"

echo "Starting deployment..."

# Maintenance mode
cd $DEPLOY_DIR
php artisan down --render="errors::503"

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
php artisan migrate --force

# Build assets
npm ci
npm run build

# Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:upgrade

# Set permissions
chmod -R 775 storage bootstrap/cache

# Restart workers
php artisan queue:restart

# Exit maintenance mode
php artisan up

echo "Deployment completed successfully!"
```

### GitHub Actions Deployment

See `.github/workflows/ci.yml` for automated deployment configuration.

---

**Last Updated**: 2026-02-08
