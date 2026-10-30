# Root Cause Analysis (RCA) – 5-Whys Engineering Investigation

**Document Reference:** EPS-RCA-2026-001  
**Investigation Date:** October 30, 2026  
**Methodology:** 5-Whys Root Cause Analysis (Six Sigma / SAP Reliability Framework)  
**System:** Erode Public School ERP (DASA TECH)  

---

## Failure Mode 1: Daily Recurring HTTP 500 on `/admissions`

### Problem Statement
Users navigating to `/admissions` intermittently received an HTTP 500 error:  
`file_put_contents(/var/www/dasa-eps-school/storage/framework/cache/data/...): Failed to open stream: Permission denied`.

### 5-Whys Analysis
1. **Why did the page return HTTP 500?**  
   Laravel threw an unhandled `ErrorException` because PHP could not write a cache entry to disk.
2. **Why could PHP not write the cache entry?**  
   The file permissions on directory `/var/www/dasa-eps-school/storage/framework/cache/data/` prevented write operations by the PHP process.
3. **Why did the directory have incorrect permissions?**  
   Artisan CLI commands (`php artisan cache:clear` or queue workers) were executed as the `root` Linux user, creating new cache subdirectories owned by `root:root` with default umask `022` (drwxr-xr-x).
4. **Why did this block the web application?**  
   The Nginx web server runs PHP-FPM under the user `www-data`. When `www-data` attempted to create cache files inside directories owned by `root` with `755` permissions, Linux denied the write request.
5. **Why was there a permission conflict between CLI and FPM?**  
   The Linux host lacked POSIX Default Access Control Lists (ACLs) to enforce dual inheritance of `rwx` permissions for both `www-data` and `root` across all newly created child folders.

### Root Cause
Absence of POSIX Access Control Lists (ACL) on the persistent `storage/` directory tree, causing ownership friction between CLI executions (`root`) and PHP-FPM web execution (`www-data`).

### Permanent Corrective Action
- Installed Linux `acl` utilities on the production VPS (`apt-get install -y acl`).
- Set directory sticky bit (`chmod -R g+s storage bootstrap/cache`) to inherit group ownership.
- Deployed recursive default POSIX ACLs:
  ```bash
  setfacl -R -d -m u:www-data:rwx,g:www-data:rwx,u:root:rwx storage bootstrap/cache
  setfacl -R -m u:www-data:rwx,g:www-data:rwx,u:root:rwx storage bootstrap/cache
  ```
- Created a health check to verify cache and storage writability continuously.

---

## Failure Mode 2: SQL Crash `column "is_active" does not exist in fee_structures`

### Problem Statement
Navigating to student creation or fee assignment threw:  
`Illuminate\Database\QueryException: SQLSTATE[42703]: Undefined column: 7 ERROR: column "is_active" does not exist in table fee_structures`.

### 5-Whys Analysis
1. **Why did the student creation / fee assignment throw an error?**  
   A PostgreSQL query attempted to filter `fee_structures` using `WHERE fee_structures.is_active = true`.
2. **Why was that column queried?**  
   `StudentController.php` and `FeeController.php` executed `FeeStructure::where('is_active', true)->get()`.
3. **Why did the developer write `FeeStructure::where('is_active', true)`?**  
   The developer assumed `is_active` was a column on the `fee_structures` table, identical to other master tables.
4. **Why is `is_active` not on `fee_structures`?**  
   The database schema normalizes active state at the parent `fee_heads` level; `fee_structures` merely defines the academic year and class amounts linked to a fee head.
5. **Why was this not caught earlier?**  
   The development database may have run with loose queries or unseeded fee structures, whereas PostgreSQL 16 enforces strict relational integrity and aborts transactions on non-existent column references.

### Root Cause
Direct filtering on a nonexistent column in `fee_structures` rather than querying through the `feeHead` relationship.

### Permanent Corrective Action
Updated the query across all controllers to use relationship querying:
```php
// Old Fragile Query:
$structures = FeeStructure::where('is_active', true)->get();

// Permanent Resilient Query:
$structures = FeeStructure::whereHas('feeHead', function ($q) {
    $q->where('is_active', true);
})->get();
```

---

## Failure Mode 3: Route Collisions & Type Errors in `GateController::show()`

### Problem Statement
Visiting Gate and Visitors features or static routes threw:  
`TypeError: GateController::show(): Argument #1 ($id) must be of type int, string given, called in ...`.

### 5-Whys Analysis
1. **Why did `GateController::show()` throw a `TypeError`?**  
   It received a string argument (e.g. `"id-card"`, `"print"`, or `"create"`) instead of an integer.
2. **Why did it receive a string?**  
   The Laravel route definition `Route::get('/gate/{id}', [GateController::class, 'show'])` was evaluated by the router before static routes like `Route::get('/gate/id-card', ...)`.
3. **Why did the router match `{id}` first?**  
   Route order precedence in `routes/web.php` placed the wildcard parameter route higher in the file than subsequent sub-action routes.
4. **Why was there no parameter validation?**  
   The wildcard route lacked a regular expression constraint (`->where('id', '[0-9]+')`).
5. **Why was PHP 8.2+ strict typing involved?**  
   PHP 8's strict argument typing immediately raises a fatal `TypeError` when a non-numeric string is coerced into an `int` parameter.

### Root Cause
Unconstrained route wildcard definitions placed before specific sub-routes, resulting in URL pattern collisions.

### Permanent Corrective Action
- Reordered all specific static routes before parameterized routes.
- Applied regex constraints to all numeric ID routes:
  ```php
  Route::get('/gate/{id}', [GateController::class, 'show'])
      ->where('id', '[0-9]+')
      ->name('gate.show');
  ```
- Registered route aliases for `gate.visitors.id-card`, `gate.visitor-id-card.studio`, and `gate.visitor-id-card.pdf`.

---

## Failure Mode 4: Layout Fatal Error `Call to a member function getRoleNames() on null`

### Problem Statement
When an unauthenticated session hit a restricted route or triggered an error page, the system crashed with:  
`Call to a member function getRoleNames() on null in resources/views/layouts/app.blade.php`.

### 5-Whys Analysis
1. **Why did the error page crash?**  
   The Blade layout attempted to call `auth()->user()->getRoleNames()->first()`.
2. **Why was `auth()->user()` null?**  
   The user was either unauthenticated, a guest user accessing a public endpoint, or the session had expired (HTTP 401/403/419/500).
3. **Why was the main layout rendered for an error page?**  
   The error views extended `layouts.app` to retain school branding and navigation elements.
4. **Why was there no null check?**  
   The template assumed `auth()->user()` would always return an authenticated `User` model instance.
5. **Why did this exacerbate 500 errors?**  
   When an initial 403 or 401 error occurred, the template engine crashed while trying to render the error screen, converting a recoverable auth challenge into an unrecoverable 500 crash.

### Root Cause
Direct object method invocation on `auth()->user()` without null-safety verification in layout navigation and error views.

### Permanent Corrective Action
Replaced all insecure user object calls across Blade templates and controllers with PHP 8 nullsafe operators and guest fallbacks:
```php
// Old Fragile Code:
$role = auth()->user()->getRoleNames()->first();

// Permanent Resilient Code:
$role = auth()->user()?->getRoleNames()?->first() ?? 'Guest';
```

---

## Failure Mode 5: Global Exception Handler Converting 302 Redirects to 500

### Problem Statement
Unauthenticated public requests were receiving HTTP 500 instead of HTTP 302 redirecting to `/login`.

### 5-Whys Analysis
1. **Why did an unauthenticated request return 500?**  
   The catch-all exception handler in `bootstrap/app.php` intercepted `AuthenticationException` and returned a 500 JSON/HTML response.
2. **Why did the handler intercept `AuthenticationException`?**  
   The handler registered `$exceptions->render(function (\Throwable $e) { ... })` without filtering out expected HTTP and authentication lifecycle exceptions.
3. **Why is `AuthenticationException` thrown?**  
   Laravel's `auth` middleware throws `AuthenticationException` by design to signal the application to redirect the client to the login page.
4. **Why did the custom handler run?**  
   Custom render callbacks take precedence over framework default response handlers unless explicitly bypassed.
5. **Why was it registered as `\Throwable`?**  
   It was intended to mask unexpected database and fatal errors, but lacked exception discrimination.

### Root Cause
Catch-all `\Throwable` handler intercepted standard framework flow-control exceptions (`AuthenticationException`, `ValidationException`, `HttpExceptionInterface`).

### Permanent Corrective Action
Refactored `bootstrap/app.php` to immediately return `null` (passing control back to Laravel) for expected lifecycle exceptions:
```php
$exceptions->render(function (\Throwable $e, Request $request) {
    if ($e instanceof \Illuminate\Auth\AuthenticationException ||
        $e instanceof \Illuminate\Validation\ValidationException ||
        $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
        $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
        return null; // Allow standard Laravel redirect or HTTP response
    }
    // Only catch true internal server errors
    $correlationId = 'EPS-' . strtoupper(Str::random(6));
    Log::error("Production 500 [{$correlationId}]: " . $e->getMessage(), ['exception' => $e]);
    return response()->view('errors.500', compact('correlationId'), 500);
});
```

---

## Failure Mode 6: Pre-KG Class Selection "The class id field is required"

### Problem Statement
In the Student Admission form, selecting "Class Pre-KG" still showed:  
`The class id field is required.` and the Section dropdown remained empty.

### 5-Whys Analysis
1. **Why did the error message display despite selecting a class?**  
   The server-side error element remained visible, and Alpine.js failed to trigger the section fetch.
2. **Why did Alpine.js fail to trigger?**  
   The Alpine `@change="onClassChange($event.target.value)"` handler was tied to an Alpine component instance that was unexposed to DOM events after DOM re-renders.
3. **Why was the section dropdown empty?**  
   The AJAX endpoint `/admin/sections/by-class` was never invoked because the event was swallowed.
4. **Why was the server error element still shown?**  
   The Blade template injected an error container `#class_id_server_err` that had no JavaScript listener to dismiss itself upon value change.
5. **Why was this isolated to Pre-KG?**  
   Pre-KG was the first item in the list, making it the most frequent entry point for new admission tests.

### Root Cause
Decoupled server-rendered error DOM node and isolated Alpine component scope that lacked bidirectional event synchronization.

### Permanent Corrective Action
- Exposed the Alpine controller globally to `window.admitFormInstance`.
- Added an inline `onchange` trigger to the native select element:
  ```html
  onchange="if(window.admitFormInstance) { window.admitFormInstance.onClassChange(this.value); } document.getElementById('class_id_server_err')?.classList.add('hidden');"
  ```
- Added a server-side auto-resolution fallback in `StudentController::store()`: if `section_id` is omitted, the system resolves the first active section for that class.

---

## Failure Mode 7: Pre-KG Attendance "No Students Found"

### Problem Statement
Selecting Class Pre-KG in Manual Section Attendance displayed "0 Total Enrolled" unless Section B was manually clicked.

### 5-Whys Analysis
1. **Why were 0 students shown initially?**  
   The controller defaulted to Section A, but students were enrolled in Section B.
2. **Why did the controller filter out students even when section was selected?**  
   The query filtered `student_enrollments.academic_year_id = $currentYear->id` (Year 6 / 2026-2027), but the enrolled students were registered under academic year 5.
3. **Why were they registered under academic year 5?**  
   The school admissions seeder created historical student enrollments that had not yet been formally promoted to the current academic calendar year.
4. **Why did Section B work?**  
   Section B had active students whose enrollment records were retrieved by direct ID lookup.
5. **Why did the controller strictly filter by year?**  
   The query lacked an adaptive fallback to search for active students across current or latest active enrollments for the class.

### Root Cause
Rigid academic year filter combined with an arbitrary section default (Section A) that had no enrolled students.

### Permanent Corrective Action
In `AttendanceController.php`:
- Auto-detect the primary active section with enrollments if no section is explicitly specified in the request.
- Relaxed the academic year constraint: if the current academic year returns zero records, query the latest active enrollment for the class:
  ```php
  if ($students->isEmpty()) {
      $students = Student::whereHas('enrollments', function($q) use ($classId, $sectionId) {
          $q->where('class_id', $classId)->where('section_id', $sectionId)->where('status', 'active');
      })->orderBy('first_name')->get();
  }
  ```

---

## Failure Mode 8: Accounts "View Logs" Infinite Loading / Failure to Open

### Problem Statement
Clicking "View Logs" under Cash Drawers in Account Management did not navigate or open the ledger.

### 5-Whys Analysis
1. **Why did "View Logs" fail to open?**  
   The JavaScript function called `window.scrollTo()` and target element `scrollIntoView()`, but nothing moved.
2. **Why did `scrollIntoView()` not move the page?**  
   In `layouts/app.blade.php`, the `window` is not scrollable; the layout uses a fixed viewport (`h-screen overflow-hidden`) with an inner `<main class="flex-1 overflow-y-auto">` container.
3. **Why did the developer use `scrollIntoView()`?**  
   Standard documentation assumes standard window scrolling.
4. **Why did it appear to "keep loading"?**  
   The anchor tag had `href="javascript:void(0)"` with an active state style, leaving the user with no visual feedback.
5. **Why was this not noticed in desktop testing?**  
   Testing was performed on resolutions where the ledger section happened to be already visible.

### Root Cause
Targeting `window` or `document.documentElement` for scrolling instead of the parent `<main>` scroll container.

### Permanent Corrective Action
Created a container-aware scroll utility:
```javascript
function scrollToLedger(accountId) {
    const mainContainer = document.querySelector('main');
    const targetElement = document.getElementById('account-ledger-section');
    if (mainContainer && targetElement) {
        const topPos = targetElement.offsetTop - mainContainer.offsetTop - 20;
        mainContainer.scrollTo({ top: topPos, behavior: 'smooth' });
    }
}
```

---

## Failure Mode 9: Gate Visitor Pass Two-Page Print & Margin Drift

### Problem Statement
Printing the Gate Visitor Pass resulted in two pages, with content shifted to the top-left margin and cut off on desktop printers.

### 5-Whys Analysis
1. **Why did the print output take two pages?**  
   Uncontrolled margin calculations and unconstrained footer containers triggered an unnecessary page break.
2. **Why was content shifted to one side?**  
   The CSS had `@media print { body { width: 80mm; } }` without margin centering (`margin: 0 auto;`).
3. **Why was 80mm specified?**  
   The pass was designed for thermal receipt printers (80mm paper width).
4. **Why did desktop printing fail?**  
   When an 80mm body rule is processed by standard A4 desktop printers, the browser renders an 80mm column on the far left edge of a 210mm wide sheet.
5. **Why did it break into 2 pages?**  
   The CSS lacked `page-break-inside: avoid;` and `break-inside: avoid;` on the pass wrapper card.

### Root Cause
Lack of adaptive print CSS targeting both POS thermal roll printers and standard A4 office printers.

### Permanent Corrective Action
In `resources/views/gate/pass.blade.php`:
- Centered the print layout dynamically using `margin: 0 auto;`.
- Declared `@page { size: auto; margin: 8mm; }`.
- Applied `break-inside: avoid; page-break-inside: avoid; page-break-after: avoid;` on the `.visitor-pass-card`.
- Removed all screen dashboard elements with `.no-print { display: none !important; }`.

