# EventFlow Management System — Deployment Guide

**Document Path:** `docs/DEPLOYMENT.md`
**Applies to:** EventFlow Management System (Laravel 13 / PHP 8.4 / MySQL 8.4)
**Version:** 1.0

This guide covers production deployment of EventFlow on a conventional VPS: web
server, PHP-FPM, MySQL, queue worker supervision, the scheduler, mail, storage,
security hardening, backups, and the upgrade procedure.

> For first-time installation steps, see `docs/INSTALLATION.md`. This guide
> assumes an installed application.

---

## 1. Recommended production stack

| Layer | Recommendation |
| --- | --- |
| Web server | Nginx (or Apache) with PHP-FPM |
| PHP | 8.3+ (8.4 recommended) with the extensions listed in `docs/INSTALLATION.md` |
| Database | MySQL 8.x |
| Queue worker | `php artisan queue:work` under Supervisor or systemd |
| Scheduler | one cron entry running `php artisan schedule:run` every minute |
| Mail | SMTP relay; see §7 |

---

## 2. Directory structure and permissions

Give the PHP-FPM user (commonly `www-data`) write access only where needed:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Never grant the web server write access to the application source beyond those
directories.

---

## 3. Production environment

`.env` values for production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://events.example.com
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

- `APP_DEBUG=false` is mandatory in production (SRS §31.10). `eventflow:check-health`
  fails the check when `APP_ENV=production` and `APP_DEBUG=true`.
- The database-backed session and queue drivers are the recommended defaults for
  a single-server VPS deployment.

After changing `.env`, refresh the caches:

```bash
php artisan optimize
```

---

## 4. Web server configuration

### 4.1 Nginx

Serve `public/` as the document root. Block access to hidden files and
environment files:

```nginx
server {
    listen 80;
    server_name events.example.com;
    root /var/www/eventflow/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Never serve .env or dotfiles
    location ~ /\. {
        deny all;
    }

    location ~ ^/\.env(?:\.example)?$ {
        deny all;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~* \.(?:js|css|png|jpg|jpeg|gif|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public";
    }
}
```

### 4.2 TLS

Terminate TLS at the web server (or a load balancer) and set `APP_URL` to the
`https://` URL. A free certificate is available via Let's Encrypt / Certbot.

---

## 5. Queue worker

EventFlow uses a database-backed queue for notifications and background work.
Run a persistent worker:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=90
```

Production Laravel convention is to run `queue:work` under a process supervisor
so it restarts on failure and on every deploy.

### Supervisor example (`/etc/supervisor/conf.d/eventflow-worker.conf`)

```ini
[program:eventflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/eventflow/artisan queue:work --sleep=3 --tries=3 --timeout=90
directory=/var/www/eventflow
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/eventflow/storage/logs/worker.log
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### Verify the worker

Dispatch a test job to the queue and watch the log, or confirm processed rows in
the `jobs`/`failed_jobs` tables. The installer also flags the queue-connection
setup in `eventflow:install`.

---

## 6. Scheduler (cron)

EventFlow registers scheduled tasks (e.g. event reminders) in
`routes/console.php`. Add a single cron entry that runs the Laravel scheduler
every minute:

```cron
* * * * * cd /var/www/eventflow && php artisan schedule:run >> /dev/null 2>&1
```

Add this via `crontab -e` for the same Linux user that owns the application
(typically `www-data`). Verify with:

```bash
php artisan schedule:list
```

Without this entry, scheduled events such as reminder notifications will not run.

---

## 7. Mail

Set real SMTP values in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=eventflow@example.com
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="EventFlow"
```

Because `MAIL_PASSWORD` is sensitive, keep it out of version control and never
print it. Send a test email after configuring (trigger any notification, then
inspect `storage/logs` or the SMTP queue).

---

## 8. Storage and uploads

- `php artisan storage:link` creates the `public/storage` symlink so uploaded
  media is served.
- Uploaded files persist under `storage/app/public` and **must be included in
  backups** (§9).
- On a multi-server setup, move uploads to shared object storage instead. This
  is a future deployment option and not required for the MVP VPS profile.

---

## 9. Backup and restore

Backup policy follows SRS §32.

### 9.1 What to back up

1. **MySQL database** — all application data.
2. **Uploaded files** — `storage/app/public` (and `storage/app` private uploads
   if used).
3. **Configuration documentation** needed to restore (e.g. the non-secret parts
   of `.env`, deployment notes). Do **not** treat backups as the place to store
   secrets unless they are encrypted and access-restricted (§32.3).

### 9.2 Database backup

```bash
mysqldump --single-transaction --quick --routines \
  -u eventflow -p eventflow | gzip > backups/eventflow-$(date +%F).sql.gz
```

### 9.3 Files backup

```bash
rsync -a /var/www/eventflow/storage/app/ backups/eventflow-storage/ \
  --exclude='/framework/cache/*'
```

### 9.4 Minimum production policy (§32.2)

- daily database backup,
- daily uploaded-file backup or equivalent snapshot,
- at least 7 daily restore points,
- at least 4 weekly restore points,
- at least one copy stored **outside** the primary VPS.

### 9.5 Restore testing

- **BKP-001**: a backup is not operationally valid until a restore procedure is
  documented.
- **BKP-002**: test restores before commercial launch and periodically after.

### 9.6 Before risky deployments (§32.5)

- create a verified database backup,
- preserve uploaded files,
- record the currently deployed version.

---

## 10. Security hardening checklist

- `APP_ENV=production`, `APP_DEBUG=false` — verified by `eventflow:check-health`.
- `.env` is not web-served (Nginx dotfile block above).
- Strong, dedicated MySQL user and password; not `root`.
- Strong initial Owner password (created via `eventflow:create-owner`; passwords
  are never printed).
- TLS enabled; `APP_URL` uses `https://`.
- Files under `storage` and `bootstrap/cache` owned by the PHP-FPM user scoped to
  the expected paths.
- Backups containing attendee data are access-restricted (§32.3).

---

## 11. Deployment / upgrade procedure

1. **Back up first** (see §9.6).
2. Pull the new code:

   ```bash
   cd /var/www/eventflow
   sudo -u www-data git pull
   ```

3. Install/update dependencies (no dev packages):

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. Run migrations:

   ```bash
   php artisan migrate --force
   ```

5. Refresh the production caches:

   ```bash
   php artisan optimize
   ```

6. Restart the queue worker to pick up new code:

   ```bash
   sudo supervisorctl restart eventflow-worker:*
   ```

7. Verify:

   ```bash
   php artisan eventflow:check-health
   curl -fsS https://events.example.com/up
   ```

You may also run `php artisan eventflow:install` after each deploy — it performs
the same preflight, migration, and health checks and is safe to re-run.

---

## 12. Monitoring

- **Liveness**: `GET /up` returns `200` when the app can boot.
- **Health gate**: `php artisan eventflow:check-health` exits `0` on success and
  `1` on failure (`--strict` also fails on warnings such as unlinked storage,
  missing worker, or missing cron). Use the exit code in a cron/CI check:

  ```bash
  php artisan eventflow:check-health --strict || alert
  ```

- Monitor `storage/logs/laravel.log` and the worker's stdout log for errors.