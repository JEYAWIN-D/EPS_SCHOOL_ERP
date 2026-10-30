# Hosting, Infrastructure & Continuous Monitoring Guide

**Document Reference:** EPS-MON-2026-001  
**System:** Erode Public School ERP (DASA TECH)  
**Host Architecture:** Ubuntu 22.04 LTS / Cloud VPS (`187.127.150.113`)  
**Components:** Nginx 1.28 + PHP 8.5 FPM + PostgreSQL 16 + Laravel 12  

---

## 1. System Architecture & Resource Allocation

```
             Internet (Cloudflare / Public DNS)
                             │
                             ▼ [Port 443 / SSL]
                     ┌───────────────┐
                     │  Nginx 1.28   │
                     └───────┬───────┘
                             │ FastCGI Unix Socket
                             ▼
                     ┌───────────────┐
                     │  PHP 8.5 FPM  │
                     └───────┬───────┘
                             │ TCP Loopback / Socket
                             ▼ [Port 5432]
                     ┌───────────────┐
                     │ PostgreSQL 16 │
                     └───────────────┘
                             │
                      Local SSD Mount
           (/var/www/dasa-eps-school/storage)
```

### Server Hardware Baseline
- **vCPU:** 4 Cores
- **RAM:** 8 GB DDR4
- **Storage:** 120 GB NVMe SSD
- **Swap:** 4 GB Configured

---

## 2. Web Server & PHP-FPM Optimization

### 2.1. Nginx Virtual Host Best Practices (`/etc/nginx/sites-available/eps.dasatech.in`)
```nginx
server {
    listen 443 ssl http2;
    server_name eps.dasatech.in;
    root /var/www/dasa-eps-school/public;
    index index.php index.html;

    # SSL Hardening
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # FastCGI Buffering for PDF and Large Reports
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_read_timeout 120s;
    client_max_body_size 25M;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }

    # Deny access to sensitive hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static Asset Long-Term Caching
    location ~* \.(jpg|jpeg|png|gif|ico|webp|svg|css|js|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

### 2.2. PHP 8.5 FPM Pool Tuning (`/etc/php/8.5/fpm/pool.d/www.conf`)
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000
request_terminate_timeout = 120s
catch_workers_output = yes
```

### 2.3. PHP Settings (`/etc/php/8.5/fpm/php.ini`)
```ini
memory_limit = 512M
upload_max_filesize = 25M
post_max_size = 30M
max_execution_time = 120
date.timezone = Asia/Kolkata
```

---

## 3. Log Rotation Protocol

To prevent disk space exhaustion from continuous production logging, log rotation is enforced.

### 3.1. Laravel Application Log Rotation (`/etc/logrotate.d/laravel-eps`)
Create `/etc/logrotate.d/laravel-eps`:
```text
/var/www/dasa-eps-school/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
}
```

### 3.2. Nginx Web Server Logs
Verify Nginx log rotation in `/etc/logrotate.d/nginx`:
- Daily rotation with 14 days retention.
- Re-open log handles via `kill -USR1 $(cat /run/nginx.pid)`.

---

## 4. Automated Health Monitoring & Alerting

### 4.1. The `/health` Endpoint Monitor
The deployed `/health` endpoint executes real-time diagnostics:
1. Active PostgreSQL query response (`SELECT 1`).
2. Filesystem write verification on `storage/framework/cache`.
3. Application cache engine read/write/delete cycle.

### 4.2. Automated Cron Ping Script (`/usr/local/bin/eps-health-check.sh`)
```bash
#!/bin/bash
# Erode Public School Health Monitor Script
ENDPOINT="https://eps.dasatech.in/health"
RESPONSE=$(curl -s -m 5 "$ENDPOINT")
STATUS=$(echo "$RESPONSE" | grep -o '"status":"healthy"')

if [ -z "$STATUS" ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') ALERT: EPS System Unhealthy! Response: $RESPONSE" >> /var/log/eps-alerts.log
    # Trigger webhook or email notification
    # curl -X POST -H 'Content-type: application/json' --data "{\"text\":\"🚨 EPS ERP ALERT: Server unhealthy\"}" https://hooks.slack.com/services/...
    
    # Attempt automatic service reload if failed
    systemctl reload php8.5-fpm
fi
```
Add to `crontab` to run every 5 minutes:
```cron
*/5 * * * * /usr/local/bin/eps-health-check.sh > /dev/null 2>&1
```

---

## 5. Daily Scheduled Tasks & Maintenance

Ensure the Laravel scheduler runs continuously in crontab under `www-data`:
```cron
* * * * * cd /var/www/dasa-eps-school && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Maintenance Jobs:
1. **Attendance Cutoff Checks:** Daily at 12:00 PM.
2. **Visitor Pass Expiration:** Nightly at 11:59 PM.
3. **Log & Prune Database Notification Records:** Weekly on Sunday.
4. **PostgreSQL Automated Daily Backup:** Daily at 02:00 AM IST:
   ```cron
   0 2 * * * pg_dump -U erp_user -d school_erp -F c -b -v -f "/var/backups/school_erp_$(date +\%Y\%m\%d).dump"
   ```

