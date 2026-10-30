# Emergency Rollback & Disaster Recovery Guide

**Document Reference:** EPS-ROL-2026-001  
**System:** Erode Public School ERP (DASA TECH)  
**Target Environments:** Cloud VPS (`187.127.150.113`) / PostgreSQL 16  
**Service Level Targets:** RTO < 15 Minutes | RPO < 1 Hour  

---

## 1. Rollback Trigger Criteria

An immediate rollback is mandated under any of the following conditions:
1. **Critical Health Failure:** The `/health` endpoint responds with HTTP 503 or remains unreachable for $> 3$ consecutive minutes.
2. **Persistent 500 Outage:** Unhandled exceptions spike above 1% of total user traffic.
3. **Database Corruption or Lockup:** PostgreSQL connection pool exhausted or unrecoverable transactional state.
4. **Data Integrity Violation:** Accidental data corruption or broken check constraint cascading across active student/fee records.

---

## 2. Emergency Response Roles & Communication

- **Incident Commander:** Senior Architecture Lead
- **Operations Lead:** DevOps / SysAdmin
- **Verification Lead:** QA Lead
- **Notification Channel:** School IT Operations Team

---

## 3. Step-by-Step Code Rollback Protocol

### Step 3.1: Enable Laravel Maintenance Mode
Inform users gracefully while operations are performed:
```bash
cd /var/www/dasa-eps-school
php artisan down --secret="eps-admin-recovery-2026" --render="errors.503"
```
*(Administrative engineers can still access the site by appending `?eps-admin-recovery-2026` to any URL).*

### Step 3.2: Revert Code to Last Known Stable Commit
```bash
# Check git commit log
git log -n 5 --oneline

# Revert to specific stable tag or previous commit SHA
git checkout <LAST_STABLE_COMMIT_HASH>

# Alternatively, if performing a branch reset:
git reset --hard HEAD~1
```

### Step 3.3: Rebuild Optimized Application Caches
Ensure compiled routes, configs, and views from the failed release are completely cleared:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3.4: Re-enforce Storage Permissions & ACL
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
setfacl -R -d -m u:www-data:rwx,g:www-data:rwx,u:root:rwx storage bootstrap/cache
setfacl -R -m u:www-data:rwx,g:www-data:rwx,u:root:rwx storage bootstrap/cache
```

### Step 3.5: Reload Web Server & PHP-FPM
```bash
systemctl reload php8.5-fpm
systemctl reload nginx
```

---

## 4. Database Snapshot Restoration Protocol

If the rollback requires restoring the database to a pre-deployment state:

### Step 4.1: Terminate Active Connections
Disconnect active database sessions on PostgreSQL:
```sql
SELECT pg_terminate_backend(pid) 
FROM pg_stat_activity 
WHERE datname = 'school_erp' AND pid <> pg_backend_pid();
```

### Step 4.2: Restore from Pre-Deployment Dump
```bash
# Locate pre-deployment snapshot created before deployment
RESTORE_FILE=$(ls -t /var/backups/school_erp_pre_deploy_*.dump | head -n 1)
echo "Restoring from snapshot: $RESTORE_FILE"

# Drop and recreate schema or restore directly
pg_restore -h 187.127.150.113 -U erp_user -d school_erp --clean --if-exists -v "$RESTORE_FILE"
```

### Step 4.3: Validate Core Table Counts
Verify record sanity post-restoration:
```bash
php artisan tinker --execute="echo 'Students: ' . \App\Models\Student::count() . ' | Employees: ' . \App\Models\Employee::count();"
```

---

## 5. Post-Rollback Validation & Resumption

### Step 5.1: Run Health Endpoint Check
```bash
curl -s https://eps.dasatech.in/health
```
Verify JSON output reports `"status": "healthy"`.

### Step 5.2: Disable Maintenance Mode
```bash
php artisan up
```

### Step 5.3: Post-Mortem Incident Logging
Record all metrics, error messages, and actions in `docs/audit/ROOT_CAUSE_ANALYSIS.md` within 24 hours of incident recovery.

