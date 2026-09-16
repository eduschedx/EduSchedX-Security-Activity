# Deployment Preparation

Target only `/var/www/eduschedx-security-activity` and `activity.eduschedx.me`.
Do not modify `/var/www/eduschedx04` or the `eduschedx.me` server block.

## Requirements

- PHP 8.1+ with `pdo_sqlite`, `mbstring`, and FPM
- Nginx
- A DNS A/AAAA record for `activity.eduschedx.me`
- A TLS certificate for `activity.eduschedx.me`
- An `EDUSCHEDX_ADMIN_PASSWORD_HASH` environment variable available to the PHP-FPM service

## Installation outline (run only during deployment)

1. Copy this project to `/var/www/eduschedx-security-activity`.
2. Set the web root files read-only for the PHP-FPM user, while granting `www-data` write access only to `storage/`.
3. Install `deployment/php-fpm/eduschedx-activity.conf` as its own PHP-FPM pool.
4. Install `deployment/nginx/activity.eduschedx.me.conf` as a new Nginx site.
5. Obtain the certificate, then test with `nginx -t` before reloading Nginx.
6. Set `EDUSCHEDX_ADMIN_PASSWORD_HASH` to a bcrypt password hash in the PHP-FPM service environment. The application deliberately has no default admin password.

Suggested permissions:

```bash
sudo chown -R root:www-data /var/www/eduschedx-security-activity
sudo find /var/www/eduschedx-security-activity -type d -exec chmod 750 {} \;
sudo find /var/www/eduschedx-security-activity -type f -exec chmod 640 {} \;
sudo chown -R www-data:www-data /var/www/eduschedx-security-activity/storage
sudo chmod -R 770 /var/www/eduschedx-security-activity/storage
```

Only `storage/` is writable by `www-data`. Application source remains owned by `root` and is not writable by PHP-FPM.

The student entry point is `/`; the protected instructor entry point is `/admin/`.
