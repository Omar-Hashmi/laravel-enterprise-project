# Enterprise Production Deployment & Operations Guide

> Production deployment specification, server provisioning, process supervision, and automated operations for the **Enterprise Workflow Automation & BPM Platform (LV-327)**.

---

## Table of Contents

- [1. Production System Requirements](#1-production-system-requirements)
  - [Server Hardware Sizing](#server-hardware-sizing)
  - [Software & Runtime Dependencies](#software--runtime-dependencies)
  - [PHP Extensions Matrix](#php-extensions-matrix)
- [2. Step-by-Step Production Deployment](#2-step-by-step-production-deployment)
  - [Step 1: Directory Setup & Permissions](#step-1-directory-setup--permissions)
  - [Step 2: Source Code Checkout](#step-2-source-code-checkout)
  - [Step 3: Composer Dependencies](#step-3-composer-dependencies)
  - [Step 4: Production Environment Configuration (`.env`)](#step-4-production-environment-configuration-env)
  - [Step 5: Database Migrations & Initial Seeding](#step-5-database-migrations--initial-seeding)
  - [Step 6: Frontend Compilation (Vite)](#step-6-frontend-compilation-vite)
  - [Step 7: Production Optimization & Caching](#step-7-production-optimization--caching)
- [3. Horizon & Queue Worker Supervision](#3-horizon--queue-worker-supervision)
  - [Option A: Supervisor Configuration (Recommended)](#option-a-supervisor-configuration-recommended)
  - [Option B: Systemd Unit Configuration](#option-b-systemd-unit-configuration)
  - [Horizon Signal Handling & Graceful Termination](#horizon-signal-handling--graceful-termination)
- [4. Task Scheduling & Cron Configuration](#4-task-scheduling--cron-configuration)
  - [System Crontab Entry](#system-crontab-entry)
  - [SLA Monitoring & Auto-Actions Schedule](#sla-monitoring--auto-actions-schedule)
- [5. Zero-Downtime Deployment Script (`deploy.sh`)](#5-zero-downtime-deployment-script-deploysh)
- [6. Security & Hardening Checklist](#6-security--hardening-checklist)
- [7. Operational Troubleshooting & Health Checks](#7-operational-troubleshooting--health-checks)

---

## 1. Production System Requirements

### Server Hardware Sizing

| Environment | CPU | RAM | Storage | Concurrency Target |
| :--- | :--- | :--- | :--- | :--- |
| **Minimum / Staging** | 2 vCPU | 4 GB | 40 GB NVMe | Up to 100 req/sec |
| **Production Standard** | 4 vCPU | 8 GB | 80 GB NVMe | Up to 500 req/sec |
| **High Availability** | 8+ vCPU (Web Cluster) | 16+ GB | 160 GB NVMe | 1,500+ req/sec |

### Software & Runtime Dependencies

- **Operating System**: Ubuntu 22.04 LTS or 24.04 LTS / Debian 12 / RHEL 9
- **PHP**: PHP 8.4 (CLI & FPM)
- **Web Server**: Nginx (1.24+) or Caddy
- **In-Memory Store**: Redis 7.0+ (used for session, cache, Horizon queues)
- **Relational Database**:
  - PostgreSQL 16+ *(Recommended for enterprise deployments)*
  - MySQL 8.0.30+
  - SQLite 3.39+ with JSON support *(Testing / Staging only)*
- **Node.js**: Node 20 LTS & NPM (for asset compilation)
- **Composer**: Composer 2.7+

### PHP Extensions Matrix

Verify all required extensions are enabled in your production `php.ini`:
```bash
php -m | grep -E "bcmath|ctype|curl|dom|fileinfo|intl|json|mbstring|openssl|pcntl|pdo|pdo_mysql|pdo_pgsql|posix|redis|xml|zip"
```

> [!IMPORTANT]
> **PCNTL & POSIX Extensions**: Laravel Horizon requires `pcntl` and `posix` extensions to fork worker processes and handle termination signals. These must be enabled in the CLI environment.

---

## 2. Step-by-Step Production Deployment

### Step 1: Directory Setup & Permissions

Prepare the application root directory and assign ownership to the web server user (`www-data`):

```bash
sudo mkdir -p /var/www/enterprise-workflow
sudo chown -R $USER:www-data /var/www/enterprise-workflow
sudo chmod -R 775 /var/www/enterprise-workflow
cd /var/www/enterprise-workflow
```

### Step 2: Source Code Checkout

```bash
git clone https://github.com/your-org/laravel-enterprise-project.git .
git checkout tags/v1.0.0 # or target production branch
```

### Step 3: Composer Dependencies

Install production-only PHP dependencies with an optimized class map:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

### Step 4: Production Environment Configuration (`.env`)

Copy the environment blueprint and configure production variables:

```bash
cp .env.example .env
php artisan key:generate --force
```

Key production `.env` settings:
```ini
APP_NAME="Enterprise Workflow Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://workflow.enterprise.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=enterprise_bpm
DB_USERNAME=bpm_user
DB_PASSWORD=SecurePasswordHere

CACHE_STORE=redis
CACHE_PREFIX=bpm_cache:

SESSION_DRIVER=redis
SESSION_LIFETIME=120

QUEUE_CONNECTION=redis
HORIZON_PREFIX=bpm_horizon:

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.enterprise.com
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@enterprise.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 5: Database Migrations & Initial Seeding

Run schema migrations against the production database:

```bash
php artisan migrate --force

# Seed base roles and administrative accounts (first deployment only)
php artisan db:seed --class=RoleAndPermissionSeeder --force
```

### Step 6: Frontend Compilation (Vite)

Compile minified production assets:

```bash
npm ci
npm run build
```

Ensure storage symlink is active:
```bash
php artisan storage:link
```

### Step 7: Production Optimization & Caching

Cache all configuration files, routes, events, and compiled Blade views:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Set appropriate directory permissions for cache and storage:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 3. Horizon & Queue Worker Supervision

Laravel Horizon requires an active master supervisor process running continuously in the background.

### Option A: Supervisor Configuration (Recommended)

Install Supervisor:
```bash
sudo apt-get update && sudo apt-get install supervisor -y
```

Create `/etc/supervisor/conf.d/laravel-horizon.conf`:
```ini
[program:laravel-horizon]
process_name=%(program_name)s
command=php /var/www/enterprise-workflow/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/enterprise-workflow/storage/logs/horizon.log
stopwaitsecs=3600
stopsignal=SIGTERM
```

Reload Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-horizon
```

Check status:
```bash
sudo supervisorctl status laravel-horizon
```

### Option B: Systemd Unit Configuration

If using systemd directly, create `/etc/systemd/system/laravel-horizon.service`:

```ini
[Unit]
Description=Laravel Horizon Master Supervisor
After=network.target redis-server.service
Requires=redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/enterprise-workflow/artisan horizon
ExecReload=/usr/bin/php /var/www/enterprise-workflow/artisan horizon:terminate
KillMode=process
TimeoutStopSec=3600

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-horizon.service
sudo systemctl start laravel-horizon.service
```

### Horizon Signal Handling & Graceful Termination

Whenever code changes are deployed, inform the running Horizon supervisors to cleanly finish executing active jobs and terminate so Supervisor/Systemd restarts them with the new code:

```bash
php artisan horizon:terminate
```

---

## 4. Task Scheduling & Cron Configuration

### System Crontab Entry

Configure a single cron job under the `www-data` user to execute Laravel's scheduled task runner every minute:

```bash
sudo crontab -u www-data -e
```

Add the following line:
```cron
* * * * * cd /var/www/enterprise-workflow && php /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### SLA Monitoring & Auto-Actions Schedule

Configured inside `routes/console.php`, `schedule:run` automatically triggers:

1. **`tasks:check-sla`** (Every 5 minutes):
   - Scans open, non-completed tasks for SLA deadlines (`due_at < now()`).
   - Flags breached tasks (`sla_breached = true`).
   - Fires `TaskOverdueEvent` to dispatch escalation notifications.
2. **`ProcessWorkflowEscalations`** (Every 5 minutes):
   - Escalates overdue workflow step assignments to fallback managers.
3. **`ProcessWorkflowSlaMonitoring`** (Every 5 minutes):
   - Audits workflow instance duration metrics.
4. **`ProcessWorkflowAutoActions`** (Every minute):
   - Automatically executes configured step actions (auto-approve, auto-reject, webhook triggers).

---

## 5. Zero-Downtime Deployment Script (`deploy.sh`)

Place this automated release script at `/var/www/enterprise-workflow/deploy.sh` and make it executable (`chmod +x deploy.sh`):

```bash
#!/usr/bin/env bash
set -e

echo "=== Starting Zero-Downtime Deployment ==="

APP_DIR="/var/www/enterprise-workflow"
cd $APP_DIR

# 1. Activate maintenance mode with bypass secret
php artisan down --render="errors::503" --secret="release-secret-bypass-token" || true

# 2. Fetch latest changes
git fetch origin main
git reset --hard origin/main

# 3. Install Composer dependencies
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 4. Migrate database
php artisan migrate --force

# 5. Clear old caches and build new caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Build frontend assets
npm ci
npm run build

# 7. Restart Queue Workers & Horizon
php artisan horizon:terminate

# 8. Reset PHP OPcache
if [ -x "$(command -v php-fpm8.4)" ]; then
    sudo systemctl reload php8.4-fpm
fi

# 9. Bring application back online
php artisan up

echo "=== Deployment Completed Successfully ==="
```

---

## 6. Security & Hardening Checklist

- [ ] **`APP_DEBUG=false`**: Ensure debug mode is strictly disabled in production.
- [ ] **Sanctum State Domains**: Correctly specify `SANCTUM_STATEFUL_DOMAINS` if using cookie-based SPA authentication.
- [ ] **Horizon Dashboard Authorization**: Verify `App\Providers\HorizonServiceProvider::gate()` limits dashboard access strictly to authenticated `Super Admin` users.
- [ ] **Telescope Authorization**: In production, disable Telescope or lock down access via `TelescopeServiceProvider::gate()`.
- [ ] **Redis Password**: Enforce `requirepass` in `/etc/redis/redis.conf` and update `REDIS_PASSWORD` in `.env`.
- [ ] **HTTPS / TLS**: Terminate TLS at Nginx with HTTP/2 and modern cipher suites (Let's Encrypt / Cloudflare).
- [ ] **Rate Limiting**: Confirm API rate limiting is enforced on `/api/v1/login` and sensitive endpoints.

---

## 7. Operational Troubleshooting & Health Checks

### Horizon Health Check
```bash
php artisan horizon:status
# Expected output: Horizon is running.
```

### Redis Connectivity Test
```bash
php artisan tinker --execute="echo Cache::store('redis')->put('ping', 'pong', 10) ? 'Redis OK' : 'Redis FAIL';"
```

### Failed Queue Jobs
```bash
php artisan queue:failed
php artisan queue:retry all
```

### Log Inspection
```bash
# Application error logs
tail -n 100 -f storage/logs/laravel.log

# Horizon worker logs
tail -n 100 -f storage/logs/horizon.log
```
