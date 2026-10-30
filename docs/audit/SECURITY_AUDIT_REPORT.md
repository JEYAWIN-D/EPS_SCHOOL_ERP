# Security Audit & Hardening Report

**Document Reference:** EPS-SEC-2026-001  
**Audit Date:** October 30, 2026  
**Auditor:** Application Security Lead & Infrastructure Architect  
**Classification:** Enterprise Security Assurance (OWASP Top 10 & ISO 27001 Guidelines)  
**System Under Audit:** Erode Public School ERP (DASA TECH)  

---

## 1. Security Posture Summary

A comprehensive application security review was conducted across authentication, role-based access control (RBAC), session management, sensitive data leakage prevention, cross-site scripting (XSS), SQL injection, and server-level hardening.

| Security Domain | Pre-Audit Risk | Post-Audit Status | Standard Complied |
|---|:---:|:---:|---|
| **Information Disclosure on 500** | HIGH (Stack Traces & DB Leaks) | **ELIMINATED** | OWASP A01:2021 |
| **Authentication & Session State** | MEDIUM (Null checks missing) | **HARDENED** | OWASP A07:2021 |
| **Role-Based Access Control (RBAC)**| GOOD (Spatie v6 Active) | **VERIFIED** | NIST SP 800-162 |
| **SQL Injection (SQLi)** | LOW (Eloquent / PDO) | **ZERO VULNERABILITY**| OWASP A03:2021 |
| **Cross-Site Scripting (XSS)** | LOW (Blade Auto-Escaping) | **HARDENED** | OWASP A03:2021 |
| **Cross-Site Request Forgery (CSRF)**| LOW (VerifyCsrfToken) | **VERIFIED** | OWASP A01:2021 |
| **Environment Configuration** | HIGH (`APP_DEBUG` risk) | **ENFORCED FALSE** | CIS Benchmark |

---

## 2. Information Leakage Elimination & Correlation IDs

### Vulnerability Identified
Previously, when an unhandled exception occurred, depending on local or temporary `.env` flags, detailed stack traces containing:
- Database host (`187.127.150.113`)
- Database username (`erp_user`)
- Table names and column structures
- Absolute server paths (`/var/www/dasa-eps-school/...`)
could potentially be exposed to end users.

### Security Remediations Implemented
1. **Production Configuration Lock:**  
   `APP_DEBUG=false` strictly enforced in `/var/www/dasa-eps-school/.env`.
2. **Centralized Exception Obfuscation:**  
   In `bootstrap/app.php`, all uncaught exceptions are intercepted. A random enterprise correlation ID (`EPS-` + 6 random alphanumeric characters) is generated:
   ```php
   $correlationId = 'EPS-' . strtoupper(Str::random(6));
   Log::error("Production 500 [{$correlationId}]: " . $e->getMessage(), [
       'url' => $request->fullUrl(),
       'user_id' => auth()->id(),
       'ip' => $request->ip(),
   ]);
   return response()->view('errors.500', compact('correlationId'), 500);
   ```
3. **Client-Facing Error Views:**  
   Custom templates for HTTP 403, 404, 419, and 500 present polite, institutional school-branded notices with no server internals.

---

## 3. RBAC & Spatie Permission Matrix

The application employs `spatie/laravel-permission` v6 with hierarchical roles. The permission matrix across key operations was verified:

| ERP Module | Super Admin | Admin / Principal | Teacher | Accountant | Receptionist / Gate | Student / Parent |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Dashboard** | Full Access | Full Access | Staff View | Finance View | Gate View | Portal View |
| **Admissions** | Create/Edit/Delete | Create/Edit | Read Only | View Fees | Inquiries Only | Apply Online |
| **Student Roster** | Full Access | Full Access | Class Roster | Fee Status | View Only | Own Profile |
| **Attendance Mark**| All Classes | All Classes | Assigned Classes | View Shortages | Gate Logs Only | Own Attendance |
| **Fee Collection** | Full Access | View/Reports | No Access | Collect/Print/Refund | No Access | Online Pay / View |
| **Accounts / Cash**| Full Access | Full Access | No Access | Manage Cash Box | No Access | No Access |
| **HR & Payroll** | Full Access | View Staff | Own Profile | Payroll Run | No Access | No Access |
| **Gate & Visitors**| Full Access | Full Access | View Visitors | No Access | Check-in/Passes | No Access |
| **Academics / Exams**| Full Access | Full Access | Grade/Timetable | View Only | No Access | View Hall Ticket |

### Route Protection Audit
- All administrative routes are wrapped with `Route::middleware(['auth', EnsureStaffUser::class])`.
- Specific sub-modules are guarded by role/permission middlewares (`permission:fees.collect`, `permission:gate.manage`, etc.).
- Public routes (`/enquiry`, `/apply/{token}`, `/students/upload-docs/{token}`, `/health`) are throttled using `throttle:10,1` or `throttle:30,1`.

---

## 4. Input Sanitization & Parameter Tampering Defenses

1. **SQL Injection:**
   All queries utilize Eloquent ORM or parameterized PDO bindings via `DB::select('SELECT ... WHERE id = ?', [$id])`. No raw string concatenation is present in SQL statements.
2. **Strict Route Parameter Regex:**
   Numeric wildcard routes now strictly reject non-integer input:
   ```php
   Route::get('/gate/{id}', [GateController::class, 'show'])->where('id', '[0-9]+');
   Route::get('/gate/pass/{id}', [GateController::class, 'pass'])->where('id', '[0-9]+');
   ```
3. **XSS Protection:**
   Blade template syntax `{{ $variable }}` auto-escapes HTML entities using `htmlspecialchars()`. Dynamic JavaScript data is injected using `@json($data)` instead of fragile string interpolation.

---

## 5. Session Security & Cookie Protection

In `config/session.php`:
- `encrypt => false` (standard Laravel session encryption available if required)
- `http_only => true` (prevents JavaScript access to session cookie)
- `same_site => 'lax'` (guards against CSRF in cross-site requests)
- `secure => true` in production (enforces HTTPS transmission of session tokens)
- Session driver: `file` with proper POSIX ACL permissions, preventing unauthorized access by other local processes.

---

## 6. Security Hardening Checklist Verification

- [x] `APP_ENV=production` verified on VPS
- [x] `APP_DEBUG=false` verified on VPS
- [x] Directory browsing disabled in Nginx configuration
- [x] SSL/TLS 1.2 and 1.3 enforced by Let's Encrypt / Certbot
- [x] Git repository (`.git/`) inaccessible via web server
- [x] Sensitive files (`.env`, `composer.json`, `package.json`) blocked by Nginx
- [x] Database credentials restricted to `127.0.0.1` and specific private subnet
- [x] Rate limiting active on all public application forms
