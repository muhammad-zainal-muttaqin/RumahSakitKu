# Security Policy

## Supported Versions

| Version | Supported          | Security Updates |
|---------|-------------------|------------------|
| 1.1.x   | ✅ Yes (current)  | Until 2026-08-08 |
| 1.0.x   | ⚠️ Limited        | Until 2026-02-08 (EOL) |
| < 1.0   | ❌ No             | No longer supported |

**Minimum supported version**: 1.0.0  
**Recommended version**: Always use latest stable release.

---

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability, please report it responsibly.

### How to Report

**DO NOT** create a public GitHub issue for security vulnerabilities.

Instead, please:

1. **Email**: Send details to **security@rumahsakitku.com**
2. **PGP Key**: Use our PGP key for encrypted communication:
   ```
   Key ID: 0x4A3B2C1D0E5F6A7B
   Fingerprint: ABCD EFGH IJKL MNOP QRST UVWX YZ12 3456 7890 ABCD
   Public Key: https://rumahsakitku.com/pgp-key.asc
   ```
3. **Include**:
   - Description of vulnerability
   - Steps to reproduce (PoC if possible)
   - Affected versions
   - Potential impact
   - Your contact information

### What to Expect

- **Acknowledgment**: Within 24 hours
- **Status update**: Within 72 hours (triaged, accepted, declined)
- **Fix timeline**: Critical (7 days), High (30 days), Medium (90 days), Low (next release)
- **Credit**: We'll credit you in [SECURITY.md](./SECURITY.md) changelog (unless you wish to remain anonymous)

### Reward Program

We offer bug bounty for responsible disclosure:

| Severity | Reward (IDR) | Criteria |
|----------|--------------|----------|
| Critical | Rp 50.000.000 | Remote code execution, data breach, authentication bypass |
| High      | Rp 20.000.000 | SQL injection, XSS, CSRF, privilege escalation |
| Medium    | Rp 5.000.000  | Information disclosure, insecure direct object reference |
| Low       | Rp 1.000.000  | Missing security headers, minor misconfigurations |

Rewards paid via bank transfer or cryptocurrency (BTC/ETH). Reward amounts may vary based on actual impact.

---

## Security Best Practices for Deployment

### 1. Server Hardening

#### Operating System
- Keep system updated:
  ```bash
  sudo apt-get update && sudo apt-get upgrade -y  # Ubuntu/Debian
  sudo yum update -y                              # CentOS/RHEL
  ```
- Disable unnecessary services
- Use firewall (UFW/iptables):
  ```bash
  sudo ufw allow 22/tcp    # SSH
  sudo ufw allow 80/tcp    # HTTP
  sudo ufw allow 443/tcp   # HTTPS
  sudo ufw enable
  ```
- Fail2ban untuk brute-force protection:
  ```bash
  sudo apt-get install fail2ban
  sudo systemctl enable fail2ban
  ```

#### SSH Security
- Disable root login: `PermitRootLogin no`
- Use key-based authentication only
- Change default port dari 22 ke port lain
- Use SSH protocol 2 only

### 2. PHP Configuration

#### php.ini Security Settings
```ini
; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,eval

; Prevent information disclosure
expose_php = Off

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1      ; if using HTTPS
session.use_strict_mode = 1

; File uploads (limit if not needed)
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20

; Error handling (production)
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### 3. Web Server (Nginx/Apache)

#### Nginx Security Headers
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
```

#### Nginx hardening
```nginx
# Hide Nginx version
server_tokens off;

# Limit request size
client_max_body_size 10M;

# Deny access to sensitive files
location ~ /\.(?!well-known).* {
    deny all;
}

location ~ ~$ {
    deny all;
}

location ~ ^/(app|bootstrap|config|database|resources|routes|tests)/ {
    deny all;
}
```

### 4. Laravel Security

#### Environment Configuration (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-32-character-key-here-change-this!

# Force HTTPS
APP_URL=https://yourdomain.com
FORCE_HTTPS=true

# Session security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Database - use strong password
DB_PASSWORD=very-strong-random-password-minimum-32-chars

# Cache & Queue (Redis) - set password
REDIS_PASSWORD=your-redis-password

# Mail - use TLS
MAIL_ENCRYPTION=tls
MAIL_PORT=587
```

#### Application-Level Security
- **Enable rate limiting**: Already included, adjust in `app/Http/Kernel.php`
- **Use strong password hashing**: Laravel uses bcround by default, good
- **CSRF protection**: Enabled by default (verify middleware)
- **SQL injection protection**: Eloquent/Query Builder prevents
- **XSS protection**: Blade `{{ }}` auto-escapes, use `{!! !!}` sparingly
- **File upload validation**:
  ```php
  $request->validate([
      'file' => 'required|file|mimes:jpg,png,pdf|max:10240', // 10MB max
  ]);
  ```
- **Validate all input** - use Form Request classes
- **Use policies/gates** untuk authorization

### 5. Database Security

#### MySQL/MariaDB Security
```sql
-- Use strong passwords for all users
ALTER USER 'simrs_user'@'localhost' IDENTIFIED BY 'very-strong-password';

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

-- Only allow local connections for application user
CREATE USER 'simrs_user'@'127.0.0.1' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON simrs_production.* TO 'simrs_user'@'127.0.0.1';

-- Enable SSL for remote connections (if needed)
ALTER USER 'simrs_user'@'%' REQUIRE SSL;
```

#### Database Backups
- Encrypt backups:
  ```bash
  mysqldump -u root -p simrs | gpg -c -o backup_$(date +%Y%m%d).sql.gz.gpg
  ```
- Store backups offsite (AWS S3, Google Cloud Storage)
- Test restore monthly

### 6. Network Security

#### Firewall Rules
- Only open necessary ports:
  - 80 (HTTP) → redirect to 443
  - 443 (HTTPS)
  - 22 (SSH) → from trusted IPs only if possible
- Block all other ports
- Use VPN untuk backend access (phpMyAdmin, Redis, etc)

#### DDoS Protection
- Cloudflare or similar CDN with DDoS protection
- Rate limiting at CDN level
- Enable "I'm Under Attack" mode for severe attacks

#### SSL/TLS Configuration
- Use certificates from trusted CA (Let's Encrypt免费)
- TLS 1.2+ only, disable TLS 1.0/1.1
- Strong cipher suites:
  ```
  ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:
  ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
  ```
- Enable HSTS:
  ```nginx
  add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
  ```

### 7. Monitoring & Logging

#### Laravel Logging
Configure `config/logging.php` untuk production:
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack', 'papertrail'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'warning',  // production: warning, staging: info
        'days' => 30,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'SIMRS Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

#### Set Up Alerts
Monitor for:
- Multiple failed login attempts (possible brute force)
- Unusual access patterns (outside business hours)
- Large data exports (possible data exfiltration)
- Admin panel access from unusual IPs
- Database connection errors
- High server load

Tools:
- **Laravel Telescope** (development only)
- **Sentry** (exception tracking)
- **Datadog/New Relic** (application performance)
- **fail2ban** untuk log scanning

### 8. Data Protection

#### Encryption at Rest
- Database columns dengan sensitive data (nik, phone, email) should be encrypted using Laravel's encryption:
  ```php
  use Illuminate\Support\Facades\Crypt;
  
  $encryptedNik = Crypt::encryptString($nik);
  $decryptedNik = Crypt::decryptString($encryptedNik);
  ```
- File uploads: Store outside web root atau dengan .htaccess protection
- Backups: Encrypt dengan GPG or AWS S3 SSE

#### Data in Transit
- All API calls MUST use HTTPS
- Use TLS 1.2+ for database connections jika remote
- Redis: Use AUTH dan if remote, use stunnel atau REDIS TLS

#### Data Retention & Deletion
- **Audit logs**: Retain 7 years ( Indonesian regulations )
- **Medical records**: Retain indefinitely atau sesuai regulation
- **Backups**: Encrypted, offsite, test restore quarterly
- **Patient deletion**: Soft delete only; hard delete requires legal approval (GDPR "right to be forgotten" exceptions untuk medical records)

### 9. Third-Party Dependencies

#### Composer Security
- Regularly update dependencies:
  ```bash
  composer update --with-dependencies
  ```
- Check for known vulnerabilities:
  ```bash
  composer audit
  ```
- Use `--no-dev` untuk production
- Remove dev dependencies after deployment

#### JavaScript Dependencies
```bash
npm audit
npm audit fix
```

#### Keep Updated
- Laravel framework (security patches)
- PHP version (latest patch release)
- Database (MySQL/MariaDB latest security patch)
- OS packages

### 10. Access Control

#### Principle of Least Privilege
- Each user hanya punya access yang dibutuhkan untuk job
- Review roles quarterly
- Remove inactive users
- Use separate accounts for admin vs regular usage (no sharing!)

#### Password Policy
- Minimum 12 characters
- Require uppercase, lowercase, numbers, symbols
- Password expiration: 90 days
- Prevent reuse of last 10 passwords
- Implement password strength meter

#### Session Management
- Session timeout after 30 minutes inactivity
- Concurrent session limit (1 session per user)
- Invalidate sessions on password change
- Use secure cookies (HttpOnly, Secure, SameSite)

---

## Security Checklist for Deployment

Before going to production, verify:

- [ ] All default passwords changed (admin, database, redis, etc.)
- [ ] APP_DEBUG=false
- [ ] APP_KEY is strong random 32-char base64
- ] HTTPS enforced (FORCE_HTTPS=true atau web server redirect)
- [ ] Firewall configured (only 80/443 open to public)
- [ ] SSH key authentication only (password disabled)
- [ ] Fail2ban installed and configured
- [ ] SSL certificate valid (not expired, from trusted CA)
- [ ] Database credentials strong password, limited user privileges
- [ ] Backup configured (daily, encrypted, offsite)
- [ ] Logging configured (daily rotation, 30 days retention)
- [ ] Monitoring alerts set up (failed logins, errors, high load)
- [ ] Rate limiting enabled
- [ ] File upload validation strict
- [ ] Dependency updates applied (composer, npm)
- [ ] Security headers configured
- [ ] Session security settings correct
- [ ] Regular backups tested (restore演练 quarterly)
- [ ] Incident response plan documented
- [ ] Access logs reviewed weekly
- [ ] Penetration test performed (at least annually)

---

## Incident Response

If security breach suspected:

1. **Contain**:
   - Isolate affected systems (block firewall, disable service)
   - Preserve logs (do not reboot/delete)

2. **Investigate**:
   - Identify scope (what data accessed/modified)
   - Determine attack vector
   - Preserve evidence (logs, database dumps)

3. **Notify**:
   - Internal: management, legal, affected stakeholders
   - External: If personal data breached, notify authorities per regulation (PDP Law: within 72 hours)
   - Affected users: If sensitive data compromised

4. **Remediate**:
   - Fix vulnerability
   - Change all passwords/keys
   - Harden systems

5. **Recover**:
   - Restore from clean backups if needed
   - Monitor for continued intrusion
   - Resume operations

6. **Post-Mortem**:
   - Document lessons learned
   - Update security controls
   - Publish incident report (if appropriate)

---

## Security Advisories

We'll publish security advisories di:
- [GitHub Security Advisories](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/security)
- [CHANGELOG.md](./CHANGELOG.md#security)
- Mailing list (subscribe di website)

Subscribe to receive notifications:
```bash
curl -X POST https://rumahsakitku.com/api/security-advisory-subscribe -d "email=your@email.com"
```

---

## Responsible Disclosure

We believe in responsible disclosure. If you:
- Report vulnerability responsibly (via email, not public)
- Give us reasonable time to fix (typically 90 days)
- Do not exploit or share data

We will:
- Not pursue legal action
- Credit you in our hall of fame (unless anonymous)
- Award bug bounty if eligible
- Fix promptly

---

## Credits

Security researchers who helped improve SIMRS:

| Researcher | Vulnerability | Date | Reward |
|------------|---------------|------|--------|
| (None yet) | - | - | - |

Want to be here? Report responsibly!

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/12.x/security)
- [PHP: The Right Way - Security](https://phptherightway.com/#security)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [Indonesian Personal Data Protection Law (PDP)](https://www.kemenkumham.go.id/pdp)

---

*Last reviewed: 2026-02-14*  
*Next review: 2026-08-14*  
*Maintained by: Security Team (security@rumahsakitku.com)*
