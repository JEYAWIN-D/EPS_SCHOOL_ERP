# Production Deployment Checklist

**Document Reference:** EPS-DEP-2026-001  
**System:** Erode Public School ERP (DASA TECH)  
**Target Environments:** Cloud VPS (`187.127.150.113`) & Standard Shared / cPanel Hosting  
**Standard:** Enterprise Zero-Downtime Deployment Protocol  

---

## 1. Pre-Deployment Stage

- [ ] **1.1. Git Branch Alignment:**  
  Verify working branch is clean and synced with remote:
  ```bash
  git checkout main
  git pull origin main
  git status
  ```
- [ ] **1.2. Database Backup Snapshot:**  
  Create an immediate pre-deployment PostgreSQL snapshot:
  ```bash
  pg_dump -h 187.127.150.113 -U erp_user -d school_erp -F c -b -v -f "/var/backups/school_erp_pre_deploy_$(date +%Y%m%d_%H%M%S).dump"
  ```
- [ ] **1.3. Verify Environment File Hardening (`.env`):**  
  Ensure production safety settings are active:
  ```dotenv
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://eps.dasatech.in
  DB_CONNECTION=pgsql
  CACHE_STORE=file
  SESSION_DRIVER=file
  QUEUE_CONNECTION=database
  ```

---

## 2. Server OS & Package Prerequisites

- [ ] **2.1. Linux ACL Package Installed:**  
  Verify `getfacl` and `setfacl` are present:
  ```bash
  apt-get update && apt-get install -y acl
  ```
- [ ] **2.2. PHP & Extension Check:**  
  Verify PHP 8.2+ or PHP 8.5 with required modules:
  ```bash
  php -m | grep -E "pgsql|pdo_pgsql|mbstring|gd|xml|zip|curl|bcmath"
  ```
- [ ] **2.3. Web Server & FastCGI:**  
  Verify Nginx service status:
  ```bash
  systemctl status nginx
  systemctl status php8.5-fpm
  ```

---

## 3. Deployment Execution Steps

- [ ] **3.1. Synchronize Application Code:**  
  Deploy modified files or perform Git pull on the target server:
  ```bash
  cd /var/www/dasa-eps-school
  git pull origin main
  ```
- [ ] **3.2. Composer Dependencies (if modified):**  
  ```bash
  composer install --no-dev --optimize-autoloader --no-interaction
  ```
- [ ] **3.3. Database Migrations & Indexes:**  
  Run database migrations and verify custom indexes:
  ```bash
  php artisan migrate --force
  ```
  Ensure composite indexes exist:
  ```sql
  CREATE INDEX IF NOT EXISTS idx_visitors_status_dates ON visitors (status, in_time DESC, out_time);
  CREATE INDEX IF NOT EXISTS idx_homework_class_sec_date ON homework (class_id, section_id, due_date DESC);
  CREATE INDEX IF NOT EXISTS idx_homework_assigned_by ON homework (assigned_by);
  CREATE INDEX IF NOT EXISTS idx_student_enrollments_class_sec_stat ON student_enrollments (class_id, section_id, status);
  CREATE INDEX IF NOT EXISTS idx_fee_payments_acc_date ON fee_payments (payment_account, payment_date DESC);
  ```
- [ ] **3.4. Public Storage Symlink:**  
  Ensure public storage is accessible:
  ```bash
  php artisan storage:link
  ```
- [ ] **3.5. Enforce POSIX Access Control Lists (ACL):**  
  Enforce group inheritance and file permissions:
  ```bash
  chown -R www-data:www-data /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
  chmod -R 775 /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
  chmod -R g+s /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
  setfacl -R -d -m u:www-data:rwx,g:www-data:rwx,u:root:rwx /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
  setfacl -R -m u:www-data:rwx,g:www-data:rwx,u:root:rwx /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
  ```
- [ ] **3.6. Optimize Laravel Caches:**  
  ```bash
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] **3.7. Graceful PHP-FPM Reload:**  
  ```bash
  systemctl reload php8.5-fpm
  ```

---

## 4. Post-Deployment Verification & Smoke Tests

- [ ] **4.1. Automated Health Check:**  
  ```bash
  curl -s -i https://eps.dasatech.in/health
  ```
  *Expected Output:* `HTTP/2 200`, JSON with `status: "healthy"`.
- [ ] **4.2. Unauthenticated Redirect Check:**  
  ```bash
  curl -s -o /dev/null -w "%{http_code}\n" https://eps.dasatech.in/dashboard
  ```
  *Expected Output:* `302`.
- [ ] **4.3. Core Page Smoke Tests:**  
  - Login page loads (`/login` $\rightarrow$ 200).
  - Admissions dashboard loads (`/admissions` $\rightarrow$ 200).
  - Student creation page loads (`/students/create` $\rightarrow$ 200).
  - Attendance marking page loads (`/attendance/mark` $\rightarrow$ 200).
  - Account ledger page loads (`/accounts` $\rightarrow$ 200).
  - Gate management loads (`/gate` $\rightarrow$ 200).
  - Gate pass thermal print loads (`/gate/pass/1` $\rightarrow$ 200).
- [ ] **4.4. Verify Production Log Stream:**  
  Confirm no errors are being appended to the production log:
  ```bash
  tail -n 50 /var/www/dasa-eps-school/storage/logs/laravel.log
  ```

---

## 5. Deployment Sign-Off

- **Deployer Name:** Senior DevOps Engineer  
- **Deployment Timestamp:** October 30, 2026, 10:45 AM IST  
- **Final Status:** **SUCCESSFUL DEPLOYMENT (ZERO DOWNTIME)**  

