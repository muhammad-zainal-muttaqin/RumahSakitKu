# Docker Setup untuk SIMRS RumahSakitKu

Cara menjalankan SIMRS dengan Docker tanpa perlu install PHP/MySQL manual.

## Prerequisites

1. **Docker Desktop for Windows**
   - Download: https://www.docker.com/products/docker-desktop
   - Install dan jalankan Docker Desktop
   - Pastikan WSL2 diaktifkan (Windows akan otomatis minta saat install)

## Quick Start (Otomatis)

Buka PowerShell sebagai **Administrator**, lalu jalankan:

```powershell
cd C:\Users\Zainal\Desktop\RumahSakitKu
.\docker-setup.ps1
```

Script ini akan:
- ✅ Check Docker installation
- ✅ Setup environment file
- ✅ Build Docker images
- ✅ Start containers (app, nginx, mysql, redis, queue, scheduler)
- ✅ Install Composer dependencies
- ✅ Run migrations dan seeders
- ✅ Set file permissions

Setelah selesai, buka: **http://localhost:8000/admin**

Login default:
- Email: `admin@rumahsakitu.test`
- Password: `password`

## Manual Setup

Jika script otomatis bermasalah, jalankan manual:

```powershell
# 1. Copy environment file
copy .env.docker .env

# 2. Build dan start containers
docker-compose up -d --build

# 3. Install dependencies
docker-compose exec app composer install

# 4. Generate key
docker-compose exec app php artisan key:generate

# 5. Run migrations
docker-compose exec app php artisan migrate

# 6. Seed database
docker-compose exec app php artisan db:seed
```

## Penggunaan dengan sail.bat

Saya sudah buatkan wrapper script (seperti Laravel Sail) untuk memudahkan:

```batch
# Start containers
.\sail.bat up

# Stop containers
.\sail.bat down

# Run artisan commands
.\sail.bat artisan migrate
.\sail.bat artisan make:model Test

# Run tests
.\sail.bat test
.\sail.bat test --filter=PatientTest

# Composer commands
.\sail.bat composer install
.\sail.bat composer update

# Database
.\sail.bat mysql          # Masuk ke MySQL shell
.\sail.bat redis          # Masuk ke Redis CLI

# Development
.\sail.bat tinker         # Tinker shell
.\sail.bat shell          # Bash shell di container
.\sail.bat logs           # Lihat logs
.\sail.bat logs app       # Lihat logs app container

# Database operations
.\sail.bat fresh          # migrate:fresh
.\sail.bat seed           # db:seed
```

## Struktur Container

| Container | Port | Deskripsi |
|-----------|------|-----------|
| simrs-app | - | PHP 8.2 FPM + Laravel |
| simrs-nginx | 8000 | Web server (http://localhost:8000) |
| simrs-mysql | 3306 | MySQL 8.0 database |
| simrs-redis | 6379 | Redis cache & queue |
| simrs-queue | - | Queue worker (auto-run) |
| simrs-scheduler | - | Cron scheduler (auto-run) |

## Running Tests

```batch
# Semua tests
.\sail.bat test

# Dengan coverage
.\sail.bat artisan test --coverage

# Parallel
.\sail.bat artisan test --parallel

# Filter test
.\sail.bat test --filter=PatientTest
```

## Troubleshooting

### Port 8000 sudah digunakan
```powershell
docker-compose down
# Edit docker-compose.yml, ganti port di nginx menjadi "8001:80"
docker-compose up -d
```

### Permission denied
```powershell
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### MySQL tidak connect
```powershell
docker-compose down -v  # Hapus volume
docker-compose up -d    # Start ulang
.\sail.bat artisan migrate:fresh --seed
```

### Reset semua (clean slate)
```powershell
docker-compose down -v
docker volume prune -f
.\docker-setup.ps1
```

## Development Workflow

1. **Edit kode di Windows** menggunakan VS Code/PHPStorm
2. **Perubahan langsung terlihat** karena menggunakan volume mounting
3. **Run commands via sail.bat**
4. **Testing otomatis** dengan PHPUnit

## Environment Variables

Edit `.env` file untuk mengubah konfigurasi:

```env
# Database (jangan ubah jika pakai Docker)
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=rumahsakitu_simrs
DB_USERNAME=simrs
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis

# BPJS (isi dengan credentials Anda)
BPJS_CONS_ID=xxx
BPJS_SECRET_KEY=xxx
```

## Backup Database

```powershell
# Export database
docker-compose exec mysql mysqldump -u root -p rumahsakitu_simrs > backup.sql

# Import database
docker-compose exec -T mysql mysql -u root -p rumahsakitu_simrs < backup.sql
```

## Update Project

```powershell
# Pull latest code
git pull

# Update dependencies
.\sail.bat composer install

# Run migrations
.\sail.bat artisan migrate

# Clear cache
.\sail.bat artisan cache:clear
.\sail.bat artisan config:clear
```

## Production Deployment

Untuk production, ubah di `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

Dan jalankan:
```powershell
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## Support

Jika ada masalah:
1. Check logs: `docker-compose logs -f`
2. Restart containers: `docker-compose restart`
3. Rebuild: `docker-compose up -d --build`
