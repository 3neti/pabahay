# Deployment Guide - Pabahay Mortgage Calculator

This guide covers deploying the Pabahay Mortgage Calculator to a production environment.

## Pre-Deployment Checklist

- [ ] PHP 8.4+ installed on server
- [ ] Composer installed
- [ ] Node.js & NPM installed
- [ ] Database server (MySQL/PostgreSQL) configured
- [ ] Redis server configured (recommended)
- [ ] SSL certificate configured
- [ ] Domain/subdomain configured
- [ ] Email service configured (Resend)

## Server Requirements

### Minimum Requirements
- PHP 8.4+
- MySQL 8.0+ or PostgreSQL 13+
- 2GB RAM
- 20GB Storage
- Redis 6.0+ (recommended)

### PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick

### Web Server
- Nginx (recommended) or Apache
- With HTTPS/SSL configured

## Deployment Steps

### 1. Clone Repository

```bash
cd /var/www
git clone <repository-url> pabahay
cd pabahay
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install JavaScript dependencies
npm ci

# Build frontend assets for production
npm run build
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file with production values:

```env
APP_NAME="Pabahay Mortgage Calculator"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pabahay_production
DB_USERNAME=pabahay_user
DB_PASSWORD=your_secure_password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=resend
RESEND_KEY=re_your_production_key

SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_TTL=3600
```

### 4. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/pabahay

# Set proper permissions
sudo chmod -R 755 /var/www/pabahay
sudo chmod -R 775 /var/www/pabahay/storage
sudo chmod -R 775 /var/www/pabahay/bootstrap/cache
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Verify settings were seeded
php artisan tinker --execute="echo App\Settings\HdmfSettings::class . ' exists: ' . (app(App\Settings\HdmfSettings::class) ? 'Yes' : 'No');"
```

### 6. Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 7. Create Admin User

```bash
php artisan make:filament-user
# Follow prompts to create admin user
```

### 8. Set Up Queue Workers (Supervisor)

Create `/etc/supervisor/conf.d/pabahay-worker.conf`:

```ini
[program:pabahay-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/pabahay/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/pabahay/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pabahay-worker:*
```

### 9. Set Up Task Scheduler (Cron)

Add to crontab:
```bash
sudo crontab -e -u www-data
```

Add this line:
```cron
* * * * * cd /var/www/pabahay && php artisan schedule:run >> /dev/null 2>&1
```

## Nginx Configuration

Create `/etc/nginx/sites-available/pabahay`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/pabahay/public;

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Increase upload limits for PDF exports
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/pabahay /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Post-Deployment

### 1. Verify Installation

- [ ] Visit https://your-domain.com/calculator
- [ ] Test mortgage calculation
- [ ] Save a computation
- [ ] Retrieve saved computation
- [ ] Visit https://your-domain.com/admin
- [ ] Login with admin credentials
- [ ] View loan profiles
- [ ] Export PDF
- [ ] Check analytics dashboard
- [ ] Update a setting and verify it works

### 2. Monitor Logs

```bash
# Application logs
tail -f /var/www/pabahay/storage/logs/laravel.log

# Worker logs
tail -f /var/www/pabahay/storage/logs/worker.log

# Nginx logs
tail -f /var/log/nginx/error.log
```

### 3. Set Up Monitoring

Consider implementing:
- Application monitoring (New Relic, Sentry)
- Server monitoring (Datadog, Prometheus)
- Uptime monitoring (UptimeRobot, Pingdom)
- Log aggregation (Papertrail, Loggly)

## Updates & Maintenance

### Deploying Updates

```bash
cd /var/www/pabahay

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
sudo supervisorctl restart pabahay-worker:*

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### Backup Strategy

```bash
# Database backup (daily)
mysqldump -u pabahay_user -p pabahay_production > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf pabahay_files_$(date +%Y%m%d).tar.gz /var/www/pabahay/storage
```

Consider automated backups with:
- Database: mysqldump + cron + offsite storage
- Files: rsync to backup server
- Settings: Version control

### Security Maintenance

- [ ] Keep PHP updated
- [ ] Keep Composer dependencies updated (`composer update`)
- [ ] Keep NPM dependencies updated (`npm update`)
- [ ] Review security advisories
- [ ] Rotate secrets regularly
- [ ] Monitor failed login attempts
- [ ] Review user permissions

## Troubleshooting

### Issue: 500 Server Error
Check:
```bash
tail -100 /var/www/pabahay/storage/logs/laravel.log
```

### Issue: Assets not loading
Run:
```bash
npm run build
php artisan view:clear
```

### Issue: Queue not processing
Check supervisor:
```bash
sudo supervisorctl status pabahay-worker:*
sudo supervisorctl restart pabahay-worker:*
```

### Issue: Settings not updating
Clear cache:
```bash
php artisan cache:clear
```

## Rollback Procedure

If deployment fails:

```bash
# Revert code
git reset --hard <previous-commit-hash>

# Rollback database (if needed)
php artisan migrate:rollback

# Rebuild
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart services
sudo supervisorctl restart pabahay-worker:*
sudo systemctl restart php8.4-fpm
```

## Support

For deployment support, contact: devops@example.com
