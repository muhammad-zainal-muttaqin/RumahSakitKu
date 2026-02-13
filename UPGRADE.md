# Upgrade Guide

Panduan lengkap untuk upgrade SIMRS RumahSakitKu dari versi lama ke versi baru.

---

## Important Disclaimer

**SEBELUM MEMULAI:**
- **BACKUP DATABASE & FILES** (see [Backup Guide](#backup-strategy))
- Test upgrade di **staging environment** terlebih dahulu
- Read entire upgrade guide for your target version
- Schedule maintenance window (estimated 30-60 minutes for minor, 2-4 hours for major)
- Notify users about downtime

---

## Table of Contents

1. [Quick Upgrade (Docker)](#quick-upgrade-docker)
2. [Manual Upgrade Process](#manual-upgrade-process)
3. [Version-Specific Instructions](#version-specific-instructions)
4. [Post-Upgrade Checklist](#post-upgrade-checklist)
5. [Rollback Procedure](#rollback-procedure)
6. [Common Upgrade Issues](#common-upgrade-issues)
7. [Upgrade Path Matrix](#upgrade-path-matrix)

---

## Quick Upgrade (Docker)

Jika Anda menggunakan Docker, upgrade sangat disederhanakan:

```bash
# 1. Pull latest images
docker-compose pull

# 2. Stop current containers
docker-compose down

# 3. Backup database
docker-compose exec mysql mysqldump -u root -p simrs_production > backup_$(date +%Y%m%d).sql

# 4. Start new containers
docker-compose up -d

# 5. Run migrations
docker-compose exec app php artisan migrate --force

# 6. Clear caches
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# 7. Verify version
docker-compose exec app php artisan --version

# 8. Check health
curl -I https://yourdomain.com/health
```

**Docker upgrade time**: ~10-15 minutes untuk minor version, ~30 minutes untuk major version.

---

## Manual Upgrade Process

### Prerequisites

- **SSH/Console access** ke production server
- **sudo/root privileges** atausudo user
- **Composer** 2.x installed
- **Node.js** 18+ dan npm (jika ada frontend changes)
- **Backup** sudah diambil (see below)

### Step-by-Step Manual Upgrade

#### 1. Preparation & Backup

```bash
# Enter maintenance mode (optional but recommended)
php artisan down --render="errors::503" --redirect="https://yourdomain.com/maintenance.html"

# Backup database
mysqldump -u simrs_user -p simrs_production > /backup/simrs_$(date +%Y%m%d_%H%M%S).sql
gzip /backup/simrs_$(date +%Y%m%d_%H%M%S).sql

# Backup code (if not using git)
tar -czf /backup/simrs_code_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/simrs

# If using git, create release tag
cd /var/www/simrs
git status  # ensure no uncommitted changes
```

#### 2. Update Code

**Jika menggunakan Git:**

```bash
# Fetch latest code
git fetch origin

# Checkout desired version (from ROADMAP.md)
git checkout 1.1.0  # or: git checkout v1.1.0

# или main branch untuk latest stable
git checkout main
git pull origin main

# Verify version
git describe --tags  # should show 1.1.0
```

**Jika manual copy:**

```bash
# Upload new release tar.gz to server
scp simrs-1.1.0.tar.gz user@server:/tmp/

# Extract
cd /var/www
tar -xzf /tmp/simrs-1.1.0.tar.gz
mv simrs-1.1.0 simrs_new
rsync -av --delete /var/www/simrs_new/ /var/www/simrs/
```

#### 3. Update Composer Dependencies

```bash
cd /var/www/simrs

# Clear composer cache
composer clear-cache

# Install new dependencies
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# If you get memory errors:
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

**Composer akan:**
- Read `composer.json`
- Download dependencies baru sesuai version constraints
- Remove packages yang tidak lagi required
- Update `vendor/` directory

#### 4. Update Node.js Dependencies (if applicable)

```bash
cd /var/www/simrs

# Clean install
rm -rf node_modules package-lock.json
npm ci --only=production

# Build assets
npm run build

# Output akan di: public/build/
```

#### 5. Run Migrations

```bash
# Check if there are new migrations
php artisan migrate:status

# Run pending migrations
php artisan migrate --force

# If migration fails, see [Troubleshooting](#common-upgrade-issues)
```

**Important:** Some migrations may modify large tables. Consider:
- Run during low traffic
- Use `--pretend` to see SQL beforehand
- For large tables, use pt-online-schema-change atau manual phased migration

#### 6. Update Configuration

Merge changes dari `env.example` ke your `.env`:

```bash
# Compare
diff .env env.example | less

# Add new variables (if any)
# Update existing ones if needed (e.g., APP_KEY, APP_URL)
```

Typical changes:
- New environment variables
- Updated cache/session drivers (redis hỗn session)
- New service credentials (BPJS, Satu Sehat, etc)

#### 7. Clear & Rebuild Caches

```bash
php artisan optimize:clear

# Cache configuration (production)
php artisan config:cache

# Cache routes (if no new routes added during upgrade)
php artisan route:cache

# Cache views
php artisan view:cache

# Clear compiled classes
rm -rf bootstrap/cache/*.php
```

**Note:** If you added new routes/controllers, don't run `route:cache` during development. Only in production.

#### 8. Restart Queue Workers

```bash
# If using Supervisor
sudo supervisorctl restart simrs-worker:*

# If using systemd
sudo systemctl restart simrs-queue

# If running manually
pkill -f "artisan queue:work"
php artisan queue:work --daemon --sleep=3 --tries=3
```

#### 9. Clear Sessions & Cache

```bash
# Clear Redis cache (if using)
redis-cli FLUSHDB  # careful: clears all Redis data!

# OR selectively:
php artisan cache:clear
php artisan session:clear

# Clear scheduled tasks cache
php artisan schedule:clear-cache
```

#### 10. Exit Maintenance Mode

```bash
php artisan up
```

#### 11. Verify Upgrade

```bash
# Check version
php artisan --version

# Run health check
curl -I https://yourdomain.com/health

# Run tests (optional but recommended)
php artisan test --parallel

# Run static analysis
php artisan analyze

# Check PHP version compatibility
php -v  # should be >= 8.2
```

---

## Version-Specific Instructions

### Upgrading from 1.0.x → 1.1.0 (Q1 2026)

**Breaking Changes:** None (minor version, backward compatible)

**New Features:**
- Multi-factor authentication (MFA) optional
- Advanced reporting with drag-and-drop
- API rate limiting configuration
- Indonesian language pack complete

**Actions Required:**

1. **Enable MFA (optional):**
   ```env
   MFA_ENABLED=true
   ```

2. **Configure Rate Limiting** (optional, default settings work):
   ```php
   // app/Http/Kernel.php
   'throttle:api' => [
       'limit' => env('API_RATE_LIMIT', 100),
       'decay' => 60,
   ],
   ```

3. **Update Filament Theme** (if customized):
   ```bash
   npm install @filament/admin@^3.0
   npm run build
   ```

4. **No database migrations needed** (feature toggle-based)

---

### Upgrading from 0.9.x → 1.0.0 (Major)

**⚠️ ATTENTION: Breaking changes!**

**Major Changes:**
- Laravel 11 → Laravel 12 upgrade
- PHP 8.1 → PHP 8.2 minimum
- Database `patient_id` → `medical_record_id` di `medical_records` table
- Policy namespacing changes (`App\Policies\*`)
- API response format: added `meta` field everywhere
- Default Filament path changed dari `/admin` ke `/dashboard`

**Required Actions:**

#### 1. Update PHP to 8.2+
```bash
# Ubuntu/Debian
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php8.2 php8.2-{common,cli,fpm,mysql,zip,gd,mbstring,curl,xml,bcmath,redis}

# Switch FPM
sudo a2dismod php8.1-fpm
sudo a2enmod php8.2-fpm
sudo systemctl restart apache2  # or nginx + php-fpm
```

#### 2. Update Laravel Framework
```bash
composer require laravel/framework "^12.0" filament/filament "^4.0" --update-with-dependencies
```

#### 3. Database Migrations
```bash
# Backup first!
php artisan migrate
```

**Migration notes:**
- `medical_records.patient_id` → `medical_record_id` (rename column)
- `policies` namespace: rename all policy classes from `App\Policies\UserPolicy` to `App\Policies\UserPolicy` (no change actually in Laravel 12, but ensure)
- Check `database/migrations/` for new migrations

#### 4. Update API Clients
Jika Anda memiliki aplikasi eksternal yang consume API:
- Update headers untuk expect `meta` field
- Adjust code yang rely pada response structure tanpa `meta`
- Test all API endpoints

#### 5. Update Filament Path (if customized)
```env
FILAMENT_PATH=dashboard  # instead of admin
```

#### 6. Rebuild Frontend Assets
```bash
npm install
npm run build
```

---

## Post-Upgrade Checklist

Setelah upgrade, verify:

### Application Health

- [ ] Homepage loads tanpa error
- [ ] Login/logout works
- [ ] User roles dan permissions function correctly
- [ ] All critical paths work:
  - [ ] Pendaftaran pasien baru
  - [ ] EMR input (SOAP, CPPT, TTV)
  - [ ] Farmasi: resep masuk, dispensing
  - [ ] Billing: create invoice, payment
  - [ ] Rawat Inap: admisi, bed assignment, checkout
  - [ ] IGD: triase, discharge
- [ ] API endpoints respond correctly
- [ ] PDF generation (resume medis, kwitansi) works
- [ ] Printer connections (label, kwitansi) verified
- [ ] Email notifications (if used) sending

### Database Integrity

- [ ] Run database consistency check:
  ```bash
  php artisan db:check
  # OR manually:
  mysqlcheck -u simrs_user -p --all-databases --auto-repair
  ```
- [ ] Check for orphaned records:
  ```sql
  SELECT * FROM medical_records WHERE patient_id NOT IN (SELECT id FROM patients);
  ```
- [ ] Verify migrations applied completely:
  ```bash
  php artisan migrate:status
  # All "Yes" for new migrations
  ```

### Performance

- [ ] Cache warmed:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] Redis connection working
- [ ] Queue workers running
- [ ] Slow query log checked (no new slow queries introduced)

### Security

- [ ] All users have valid sessions (check audit trail)
- [ ] No debug mode (`APP_DEBUG=false`)
- [ ] SSL certificate valid
- [ ] No exposed `.env` file via web
- [ ] Default admin password changed

### Monitoring

- [ ] Log aggregation working (Laravel logs shipping)
- [ ] Uptime monitoring alerts configured
- [ ] Error reporting (Sentry, etc) receiving events
- [ ] Performance monitoring (New Relic, Datadog) collecting

---

## Rollback Procedure

Jika upgrade gagal atau ada masalah critical:

### Quick Rollback (Git-based)

```bash
# 1. Enter maintenance mode
php artisan down

# 2. Revert code ke previous version
git revert HEAD  # jika melakukan commit upgrade
# OR
git checkout 1.0.0  # ke previous known good version

# 3. Restore database backup
mysql -u simrs_user -p simrs_production < /backup/simrs_20260208_120000.sql

# 4. Clear caches
php artisan optimize:clear

# 5. Restart services
sudo supervisorctl restart simrs-worker:*
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# 6. Exit maintenance mode
php artisan up
```

### Full Rollback (Manual backup)

```bash
# 1. Maintenance mode
php artisan down

# 2. Restore code dari backup
rsync -av /backup/simrs_code_20260208_120000.tar.gz /var/www/simrs/

# 3. Restore database
gunzip -c /backup/simrs_20260208_120000.sql.gz | mysql -u simrs_user -p simrs_production

# 4. Composer install untuk previous dependencies
composer install --no-dev --optimize-autoloader

# 5. Clear caches
php artisan optimize:clear

# 6. Exit maintenance mode
php artisan up
```

**Rollback time should be < 15 minutes jika backup valid.**

---

## Common Upgrade Issues

### Issue: "Class not found" errors

**Cause:** Autoloader tidak riconfiguration setelah upgrade  
**Solution:**
```bash
composer dump-autoload --optimize
php artisan optimize:clear
```

### Issue: Migration failed dengan "column already exists"

**Cause:** Migration already run sebelumnya (coba ulang)  
**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Jika migration ter-tracking tapi belum applied, force:
php artisan migrate --path=database/migrations/2026_02_01_000000_add_meta_to_api_responses.php --force

# Jika migration gagal di tengah, rollback specific batch:
php artisan migrate:rollback --step=1
```

### Issue: "Target class [App\Http\Controllers\...] does not exist"

**Cause:** Namespace changes (major version upgrade)  
**Solution:**
- Update all `use` statements di controllers
- Run `php artisan optimize:clear` untuk clear opcache
- Jika using route caching, regenerate:
  ```bash
  php artisan route:clear
  php artisan route:cache
  ```

### Issue: Frontend assets not loading (404)

**Cause:** Vite mix manifest missing atau wrong path  
**Solution:**
```bash
npm run build  # rebuild assets
php artisan view:clear
# Check Vite manifest exists: public/build/manifest.json
```

### Issue: "SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint"

**Cause:** Migration foreign key references to table/column that doesn't exist yet  
**Solution:**
1. Check order of migrations (filenames timestamp)
2. Ensure referenced table exists before adding FK
3. Check data type compatibility (both must be same type/length)
4. Manual fix: temporarily disable foreign key checks:
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- run migration
   SET FOREIGN_KEY_CHECKS=1;
   ```

### Issue: Sessions not persisting after upgrade

**Cause:** Session driver change (database → redis) atau encryption key mismatch  
**Solution:**
```env
# Ensure APP_KEY sama dengan before upgrade (jika sessions encrypted)
APP_KEY=base64:same-key-as-before

# If changing session driver, clear old sessions
php artisan session:clear
```

### Issue: Queue jobs failing

**Cause:** Job class moved atau serialization change  
**Solution:**
```bash
# Clear failed jobs
php artisan queue:clear

# Restart workers with fresh code
sudo supervisorctl restart simrs-worker:*

# Check specific job failure
php artisan queue:failed
php artisan queue:retry all
```

### Issue: "The stream or file ... could not be opened" (cache/logs)

**Cause:** Permissions tidak benar setelah upgrade  
**Solution:**
```bash
# Set proper ownership (www-data typical)
sudo chown -R www-data:www-data /var/www/simrs/storage /var/www/simrs/bootstrap/cache

# Permissions
chmod -R 775 /var/www/simrs/storage /var/www/simrs/bootstrap/cache

# SELinux (if enabled)
sudo chcon -R -t httpd_sys_rw_content_t /var/www/simrs/storage
```

---

## Upgrade Path Matrix

| Current Version | Target Version | Recommended Path | Notes |
|-----------------|----------------|------------------|-------|
| 1.0.x           | 1.1.x          | Direct upgrade   | Minor version, no DB changes |
| 1.0.x           | 1.2.x          | Upgrade to 1.1.0 first, then 1.2.0 | Sequential minor upgrades |
| 0.9.x           | 1.0.x          | Direct upgrade (but test extensively) | Major, breaking changes |
| 0.8.x           | 1.0.x          | Upgrade to 0.9.0 first → 1.0.0 | Multiple major upgrades |
| < 0.8           | 1.0.x          | **Not supported** - must rebuild | Too old, archaic database schema |

**General Rule:**
- Minor version upgrades (1.0.0 → 1.1.0): Can skip intermediate
- Major version upgrades (0.9.x → 1.0.0): Visit each major version, apply all patches
- Patch upgrades (1.0.1 → 1.0.2): Direct

---

## Downtime Minimization Strategies

For production with high availability requirements:

### Blue-Green Deployment
1. Prepare green environment (new version)
2. Switch load balancer dari blue ke green
3. Keep blue as fallback for 24 hours
4. Decommission blue after verification

### Rolling Update (Kubernetes)
- Deploy new pods gradually (maxUnavailable 25%)
- Health checks before routing traffic
- Rollback if metrics degrade

### Database Migrations Without Downtime
- **Add columns** dengan `NULL` atau default (safe)
- **Backfill data** in batches
- **Add foreign keys** after data verified (`NOT VALID` di PostgreSQL, atau two-step di MySQL)
- **Remove columns** (only after 2 releases deprecated)

---

## Support

Jika Anda mengalami masalah saat upgrade:

1. **Check [Common Upgrade Issues](#common-upgrade-issues)** above
2. **Search [GitHub Issues](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/issues?q=label%3Aupgrade)**
3. **Ask in [GitHub Discussions](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/discussions)**
4. **Contact support**: upgrade@rumahsakitku.com (include: current version, target version, error logs)

---

## Changelog Reference

For detailed changes between versions, see:
- **[CHANGELOG.md](./CHANGELOG.md)** - Version-by-version breakdown
- **[ROADMAP.md](./ROADMAP.md)** - Upcoming features (may affect upgrade planning)

---

*Last Updated: 2026-02-14*  
*Applies to versions: 1.0.x and 1.1.x*  
*For older versions, please see archived upgrade guides di [GitHub Releases](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/releases).*
