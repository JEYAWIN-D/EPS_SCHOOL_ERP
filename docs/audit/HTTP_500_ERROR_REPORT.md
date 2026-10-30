# HTTP 500 Error Elimination Report

**Document Reference:** EPS-500-2026-001  
**Investigation Date:** October 30, 2026  
**System:** Erode Public School ERP (DASA TECH)  
**Target:** 100% Elimination of HTTP 500 Errors Across All Modules  

---

## 1. Summary of Identified & Eliminated 500 Errors

| Incident ID | Route / URI | Exception Class | Primary Cause | Resolution Status |
|---|---|---|---|:---:|
| **ERR-500-01** | `GET /admissions` | `ErrorException: file_put_contents(): Permission denied` | Storage cache ACL conflict | **RESOLVED** |
| **ERR-500-02** | `GET /students/create` | `QueryException: column "is_active" does not exist` | Invalid column on fee_structures | **RESOLVED** |
| **ERR-500-03** | `GET /gate/{id}` | `TypeError: GateController::show(): int expected` | Unconstrained route pattern | **RESOLVED** |
| **ERR-500-04** | `GET /gate` | `RouteNotFoundException: [gate.visitors.id-card]` | Missing named route alias | **RESOLVED** |
| **ERR-500-05** | Any unauth error | `Error: Call to a member function getRoleNames() on null` | Missing nullsafe check in layout | **RESOLVED** |
| **ERR-500-06** | Public unauth requests | `Unhandled Server Exception: Unauthenticated` | Catch-all handler catching 302s | **RESOLVED** |
| **ERR-500-07** | `GET /students/{id}` | `ErrorException: Undefined property: stdClass::$total_days` | Missing fields in monthly stats | **RESOLVED** |
| **ERR-500-08** | `GET /fees/monthly-collection` | `ErrorException: Undefined variable $year` | Unassigned variable in controller | **RESOLVED** |
| **ERR-500-09** | `POST /admissions` | `QueryException: students_student_type_check violation` | Unset default enum attributes | **RESOLVED** |
| **ERR-500-10** | `POST /academics/timetable` | `QueryException: timetables_period_type_check violation` | Omitted required period_type | **RESOLVED** |

---

## 2. In-Depth Defect Breakdown & Code Diffs

---

### Incident ERR-500-01: File Permission Denied on Cache

#### Error Signature
```text
[2026-10-30 04:12:15] production.ERROR: file_put_contents(/var/www/dasa-eps-school/storage/framework/cache/data/9a/b1/9ab12...): 
Failed to open stream: Permission denied in /var/www/dasa-eps-school/vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php:124
```

#### Affected Route
- `GET /admissions`
- `GET /dashboard`

#### Code Diff & Fix
Applied system-level POSIX ACLs on the VPS filesystem:
```bash
# Production VPS remediation:
apt-get install -y acl
chown -R www-data:www-data /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
chmod -R 775 /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
chmod -R g+s /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
setfacl -R -d -m u:www-data:rwx,g:www-data:rwx,u:root:rwx /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
setfacl -R -m u:www-data:rwx,g:www-data:rwx,u:root:rwx /var/www/dasa-eps-school/storage /var/www/dasa-eps-school/bootstrap/cache
```

#### Verification
Executed `php artisan cache:clear` and reloaded `php8.5-fpm`. Verified with `curl -s https://eps.dasatech.in/admissions` returning `HTTP 302 Redirect` (unauth) and `HTTP 200 OK` (auth).

---

### Incident ERR-500-02: Missing Column `is_active` in `fee_structures`

#### Error Signature
```text
Illuminate\Database\QueryException: SQLSTATE[42703]: Undefined column: 7 ERROR: column "is_active" does not exist in table fee_structures
LINE 1: select * from "fee_structures" where "is_active" = $1
```

#### Affected Files
- `app/Http/Controllers/Admin/StudentController.php` (Line 412)
- `app/Http/Controllers/Admin/FeeController.php` (Line 285)

#### Code Diff
```diff
--- a/app/Http/Controllers/Admin/StudentController.php
+++ b/app/Http/Controllers/Admin/StudentController.php
@@ -409,7 +409,9 @@ class StudentController extends Controller
-        $feeStructures = FeeStructure::where('is_active', true)
-            ->where('academic_year_id', $currentYear->id)
-            ->get();
+        $feeStructures = FeeStructure::whereHas('feeHead', function($q) {
+                $q->where('is_active', true);
+            })
+            ->where('academic_year_id', $currentYear->id)
+            ->get();
```

#### Verification
Tested `GET /students/create` via authenticated session: returned HTTP 200 OK with fee structure dropdown populated properly.

---

### Incident ERR-500-03 & ERR-500-04: Route Collisions & Missing Names in Gate Module

#### Error Signature
```text
Symfony\Component\Routing\Exception\RouteNotFoundException: Route [gate.visitors.id-card] not defined.
in resources/views/gate/index.blade.php:482

TypeError: GateController::show(): Argument #1 ($id) must be of type int, string given
in app/Http/Controllers/Admin/GateController.php:128
```

#### Affected File
- `routes/web.php`
- `resources/views/gate/index.blade.php`

#### Code Diff
```diff
--- a/routes/web.php
+++ b/routes/web.php
@@ -480,8 +480,13 @@ Route::middleware(['auth'])->prefix('admin')->group(function () {
     Route::prefix('gate')->name('gate.')->group(function () {
+        // Static actions declared before parameterized routes
+        Route::get('/visitor-id-card/studio', [GateController::class, 'visitorIdCardStudio'])->name('visitor-id-card.studio');
+        Route::get('/visitor-id-card/pdf',    [GateController::class, 'visitorIdCardPdf'])->name('visitor-id-card.pdf');
+        Route::get('/visitors/id-card',       [GateController::class, 'visitorIdCardStudio'])->name('visitors.id-card');
+
-        Route::get('/{id}',                  [GateController::class, 'show'])->name('show');
+        Route::get('/{id}',                  [GateController::class, 'show'])->where('id', '[0-9]+')->name('show');
         Route::get('/pass/{id}',             [GateController::class, 'pass'])->where('id', '[0-9]+')->name('pass');
     });
```

#### Verification
Tested `GET /admin/gate`, `GET /admin/gate/pass/1`, and `GET /admin/gate/visitors/id-card`: all returned HTTP 200 OK.

---

### Incident ERR-500-05: Null Pointer Calling `getRoleNames()` on Null User

#### Error Signature
```text
Error: Call to a member function getRoleNames() on null
in resources/views/layouts/app.blade.php:112
```

#### Affected Files
- `resources/views/layouts/app.blade.php`
- `resources/views/errors/403.blade.php`

#### Code Diff
```diff
--- a/resources/views/layouts/app.blade.php
+++ b/resources/views/layouts/app.blade.php
@@ -110,3 +110,3 @@
-    <span class="text-xs font-semibold">{{ auth()->user()->getRoleNames()->first() }}</span>
+    <span class="text-xs font-semibold">{{ auth()->user()?->getRoleNames()?->first() ?? 'Staff' }}</span>
```

```diff
--- a/resources/views/errors/403.blade.php
+++ b/resources/views/errors/403.blade.php
@@ -25,3 +25,3 @@
-    <p class="text-sm">Current Role: {{ auth()->user()->getRoleNames()->first() }}</p>
+    <p class="text-sm">Current Role: {{ auth()->user()?->getRoleNames()?->first() ?? 'Guest' }}</p>
```

#### Verification
Simulated unauthenticated access to restricted endpoint. Page cleanly displayed 403 / redirected to login without any fatal null pointer errors.

---

### Incident ERR-500-06: Global Exception Handler Bypassing Authentication Redirection

#### Error Signature
```text
Unhandled Server Exception: Unauthenticated
in vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php:63
HTTP 500 returned instead of HTTP 302
```

#### Affected File
- `bootstrap/app.php`

#### Code Diff
```diff
--- a/bootstrap/app.php
+++ b/bootstrap/app.php
@@ -45,6 +45,15 @@ return Application::configure(basePath: dirname(__DIR__))
     ->withExceptions(function (Exceptions $exceptions): void {
         $exceptions->render(function (\Throwable $e, Request $request) {
+            // Bypass standard flow-control exceptions
+            if ($e instanceof \Illuminate\Auth\AuthenticationException ||
+                $e instanceof \Illuminate\Validation\ValidationException ||
+                $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
+                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
+                return null;
+            }
+            
             $correlationId = 'EPS-' . strtoupper(Str::random(6));
             Log::error("Unhandled Production Exception [{$correlationId}]: " . $e->getMessage(), [
                 'url' => $request->fullUrl(),
                 'trace' => $e->getTraceAsString(),
             ]);
```

#### Verification
Ran `curl -I https://eps.dasatech.in/dashboard`. Response returned:
`HTTP/2 302 Found`, `location: https://eps.dasatech.in/login`.

---

### Incident ERR-500-07: Undefined Property `$total_days` in Student Show

#### Error Signature
```text
ErrorException: Undefined property: stdClass::$total_days
in resources/views/students/show.blade.php:1896
```

#### Affected Files
- `app/Http/Controllers/Admin/StudentController.php` (Line 634)
- `resources/views/students/show.blade.php` (Line 1896)

#### Code Diff
```diff
--- a/app/Http/Controllers/Admin/StudentController.php
+++ b/app/Http/Controllers/Admin/StudentController.php
@@ -632,6 +632,15 @@ class StudentController extends Controller
             $monthlyStats = DB::table('student_attendances')
                 ...
+                ->selectRaw('count(*) as total_days,
+                             count(case when status = \'present\' then 1 end) as present_days,
+                             count(case when status = \'absent\' then 1 end) as absent_days,
+                             count(case when status = \'late\' then 1 end) as late_days,
+                             count(case when status = \'half_day\' then 1 end) as half_day_days,
+                             count(case when status = \'leave\' then 1 end) as leave_days')
```

```diff
--- a/resources/views/students/show.blade.php
+++ b/resources/views/students/show.blade.php
@@ -1895,3 +1895,3 @@
-    <td class="px-4 py-2">{{ $m->total_days }}</td>
+    <td class="px-4 py-2">{{ $m->total_days ?? $m->total ?? 0 }}</td>
```

#### Verification
Tested `GET /students/1` via authenticated Super Admin. Profile rendered completely with all monthly attendance cards and attendance percentage charts.

---

### Incident ERR-500-08: Undefined Variable `$year` in Monthly Fee Collection

#### Error Signature
```text
ErrorException: Undefined variable $year
in resources/views/fees/monthly-collection.blade.php:42
```

#### Affected Files
- `app/Http/Controllers/Admin/FeeController.php` (Line 1255)
- `resources/views/fees/monthly-collection.blade.php` (Line 12)

#### Code Diff
```diff
--- a/app/Http/Controllers/Admin/FeeController.php
+++ b/app/Http/Controllers/Admin/FeeController.php
@@ -1253,5 +1253,6 @@ class FeeController extends Controller
+        $year = $startYear;
         return view('fees.monthly-collection', compact(
             'monthlyData', 'months', 'academicYears', 'currentYearId', 'summary', 'year'
         ));
```

```diff
--- a/resources/views/fees/monthly-collection.blade.php
+++ b/resources/views/fees/monthly-collection.blade.php
@@ -10,3 +10,4 @@
+@php
+    $year = $year ?? $startYear ?? now()->year;
+@endphp
```

#### Verification
Tested `GET /fees/monthly-collection`: page loaded with month-by-month financial summary and interactive chart in 0.12s.

---

## 3. Centralized Production Error Page & Masking

When an unhandled exception occurs in production, raw SQL queries, file paths, and environment secrets are **never** exposed to the user.

Instead:
1. A unique correlation ID is generated (e.g. `EPS-8F3K9A`).
2. Full stack trace, request URL, user ID, and IP address are recorded in `/var/www/dasa-eps-school/storage/logs/laravel.log`.
3. The user is served a branded, professional error view (`resources/views/errors/500.blade.php`) displaying the correlation ID for IT support dispatch.
