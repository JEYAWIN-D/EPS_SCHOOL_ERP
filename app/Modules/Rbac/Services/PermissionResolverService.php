<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionResolverService
{
    const CACHE_TTL_SECONDS = 3600;

    /**
     * Get the authoritative dynamic catalog of ERP modules and submodules.
     */
    public static function getModuleCatalog(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'description' => 'Executive, Academic, Financial & Transport Analytics Overview',
                'submodules' => [
                    'overview' => 'Main Executive Dashboard',
                    'academic_widgets' => 'Academic Metrics & Attendance Overview',
                    'financial_widgets' => 'Fee Collections & Expenses Analytics',
                    'transport_widgets' => 'Fleet & Route Status',
                ],
                'default_actions' => ['view', 'view_reports', 'export'],
            ],
            'admissions' => [
                'name' => 'Admissions',
                'icon' => 'user-plus',
                'description' => 'Student Enquiries, Document Verification, and Admission Workflow',
                'submodules' => [
                    'enquiries' => 'Admission Enquiries & Follow-ups',
                    'applications' => 'Formal Applications & Registration',
                    'documents' => 'Document Verification & Uploads',
                    'approvals' => 'Principal / Management Final Approval',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'verify', 'approve', 'reject', 'print', 'export', 'view_reports'],
            ],
            'students' => [
                'name' => 'Student Management',
                'icon' => 'users',
                'description' => 'Student Directory, Class Rosters, Profiles & Historical Records',
                'submodules' => [
                    'directory' => 'Active Student Directory & Search',
                    'profiles' => 'Student Bio, PII & Emergency Contacts',
                    'enrollments' => 'Class & Section Enrollments',
                    'transfers' => 'Student Transfers & TC Issuance',
                    'inactive' => 'Archived / Withdrawn Students',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'print', 'download', 'upload', 'export', 'import', 'view_reports'],
            ],
            'employees' => [
                'name' => 'Staff Management',
                'icon' => 'briefcase',
                'description' => 'Teaching & Non-Teaching Employees, Profiles, Departments & Payroll',
                'submodules' => [
                    'directory' => 'Staff Directory & Profiles',
                    'departments' => 'Departments & Designations',
                    'class_teachers' => 'Class Teacher In-Charge Assignments',
                    'leave_requests' => 'Staff Leave Applications & Permissions',
                    'payroll' => 'Staff Salary & Compensation Records',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'approve', 'reject', 'print', 'export', 'import', 'view_reports'],
            ],
            'academics' => [
                'name' => 'Academic Management',
                'icon' => 'graduation-cap',
                'description' => 'Academic Years, Terms, Classes, Sections, Subjects & Syllabus',
                'submodules' => [
                    'classes_sections' => 'Classes & Sections Master',
                    'subjects' => 'Subjects & Subject Groups',
                    'syllabus' => 'Curriculum, Lessons & Syllabus Tracker',
                    'academic_years' => 'Academic Years & Term Configuration',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'configure', 'print', 'export', 'import'],
            ],
            'timetables' => [
                'name' => 'Timetable Management',
                'icon' => 'calendar-clock',
                'description' => 'Class Periods, Teacher Workload & Temporary Substitutions',
                'submodules' => [
                    'class_timetables' => 'Weekly Class Timetable Matrix',
                    'teacher_timetables' => 'Teacher Workload & Schedules',
                    'substitutions' => 'Teacher Substitution & Period Coverage',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'print', 'export'],
            ],
            'attendance' => [
                'name' => 'Attendance Management',
                'icon' => 'user-check',
                'description' => 'Student Daily Attendance, Period Marking & Staff Biometric/Logs',
                'submodules' => [
                    'student_daily' => 'Daily Section-Wise Student Attendance',
                    'student_period' => 'Period-Wise Subject Attendance',
                    'staff_daily' => 'Staff Daily Attendance & Biometric Logs',
                    'reports' => 'Attendance Registers & Defaulter Reports',
                ],
                'default_actions' => ['view', 'create', 'edit', 'submit', 'verify', 'print', 'download', 'export', 'view_reports'],
            ],
            'fees' => [
                'name' => 'Fees & Payments',
                'icon' => 'credit-card',
                'description' => 'Fee Structures, Collections, Receipts, Payment Splits & Concessions',
                'submodules' => [
                    'structures' => 'Fee Categories & Slab Structures',
                    'collections' => 'Student Fee Collection Counter',
                    'receipts' => 'Receipt Generation, Printing & Duplicates',
                    'splits' => 'Payment Mode Splits (Cash, Cheque, UPI)',
                    'concessions' => 'Scholarships & Fee Concession Approvals',
                    'defaulters' => 'Overdue Fee Tracking & Automated Reminders',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'print', 'download', 'export', 'view_reports'],
            ],
            'transport' => [
                'name' => 'Transport & Fleet',
                'icon' => 'bus',
                'description' => 'Vehicles, Routes, Bus Stops, Drivers, Maintenance, Fuel & Live GPS',
                'submodules' => [
                    'vehicles' => 'Vehicle Registry & Fitness Compliance',
                    'documents' => 'Vehicle Documents (RC, Insurance, Permits)',
                    'routes' => 'Route Planning & Stop Sequences',
                    'allocations' => 'Student Transport Allocations & Fees',
                    'maintenance' => 'Service Records & Fuel Logs',
                    'tracking' => 'Live GPS Telematics & Geo-fencing',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'upload', 'download', 'print', 'export', 'view_reports'],
            ],
            'books_uniforms' => [
                'name' => 'Books & Uniforms',
                'icon' => 'book-open',
                'description' => 'Booklists, Notebook Bundles, Uniform Kits & Sizing Distribution',
                'submodules' => [
                    'checklists' => 'Standard Academic Book & Notebook Bundles',
                    'uniform_kits' => 'Uniform Specifications & Sizing Sets',
                    'distribution' => 'Student Issuance & Payment Tracking',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'print', 'export', 'view_reports'],
            ],
            'library' => [
                'name' => 'Library Management',
                'icon' => 'library',
                'description' => 'Books Catalog, Barcode Scanning, Issue/Return & Overdue Fines',
                'submodules' => [
                    'catalog' => 'Books Catalog & Accession Numbers',
                    'circulation' => 'Book Issues, Returns & Renewals',
                    'members' => 'Student & Staff Library Members',
                    'fines' => 'Overdue Fine Levying & Waivers',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'print', 'export', 'view_reports'],
            ],
            'inventory' => [
                'name' => 'Warehouse & Inventory',
                'icon' => 'package',
                'description' => 'Central Store, Stock In/Out, Vendors & Department Indents',
                'submodules' => [
                    'items' => 'Item Master Catalog & SKUs',
                    'stock_logs' => 'Stock Inward (GRN) & Issue Vouchers',
                    'vendors' => 'Approved Vendors & Suppliers',
                    'warehouses' => 'Warehouse / Store Locations',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'submit', 'approve', 'reject', 'print', 'export', 'view_reports'],
            ],
            'examinations' => [
                'name' => 'Examination & Results',
                'icon' => 'award',
                'description' => 'Exam Timetable, Mark Entries, Grading Scales & Report Cards',
                'submodules' => [
                    'schedules' => 'Exam Master & Datesheets',
                    'grading' => 'Grading Scales & Assessment Weights',
                    'marks_entry' => 'Teacher Marks Entry & Moderation',
                    'report_cards' => 'Tabulation Sheets & CBSE/State Report Cards',
                ],
                'default_actions' => ['view', 'create', 'edit', 'submit', 'verify', 'approve', 'print', 'download', 'export', 'view_reports'],
            ],
            'visitors' => [
                'name' => 'Gate & Visitor Management',
                'icon' => 'shield',
                'description' => 'Security Gate Visitors, Entry Passes, Visiting Hours & Blacklist',
                'submodules' => [
                    'visitor_log' => 'Check-in / Check-out Security Register',
                    'passes' => 'Instant Digital Visitor Badge & OTP',
                    'visiting_hours' => 'School Visiting Hours & Restrictions',
                ],
                'default_actions' => ['view', 'create', 'edit', 'approve', 'reject', 'print', 'export', 'view_reports'],
            ],
            'events' => [
                'name' => 'Events & Activity Hub',
                'icon' => 'calendar-days',
                'description' => 'Annual Calendar, School Assemblies, Faculty Meetings & Tasks',
                'submodules' => [
                    'calendar' => 'Institutional Academic & Cultural Calendar',
                    'meetings' => 'Staff Meetings & Agendas',
                    'tasks' => 'Assigned Faculty Tasks & Action Items',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'print', 'export'],
            ],
            'communications' => [
                'name' => 'Circulars & Communications',
                'icon' => 'megaphone',
                'description' => 'Parent Notifications, Staff Circulars, SMS & WhatsApp Alerts',
                'submodules' => [
                    'circulars' => 'Official School Circulars & Notices',
                    'templates' => 'SMS / WhatsApp Notification Templates',
                    'broadcast' => 'Bulk Message Dispatch Logs',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'submit', 'approve', 'print', 'export'],
            ],
            'lms' => [
                'name' => 'LMS & Digital Learning',
                'icon' => 'book-text',
                'description' => 'Digital Classrooms, Homework, Online Assignments & Resources',
                'submodules' => [
                    'materials' => 'E-Books & Lesson Study Materials',
                    'assignments' => 'Homework Assignments & Submissions',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'upload', 'download', 'export'],
            ],
            'alumni' => [
                'name' => 'Alumni Network',
                'icon' => 'user-check',
                'description' => 'Past Students Network, Batch Directory & Event Announcements',
                'submodules' => [
                    'directory' => 'Graduated Students Register',
                    'events' => 'Alumni Reunions & Mentorship',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'export', 'print'],
            ],
            'expenses' => [
                'name' => 'Expenses & Petty Cash',
                'icon' => 'wallet',
                'description' => 'School Operating Expenses, Petty Cash & Multi-level Approvals',
                'submodules' => [
                    'vouchers' => 'Daily Expense Claims & Vouchers',
                    'categories' => 'Expense Heads & Ledger Codes',
                    'approvals' => 'Admin / Principal Expense Approvals',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'verify', 'approve', 'reject', 'print', 'export', 'view_reports'],
            ],
            'reports' => [
                'name' => 'Reports & Analytics',
                'icon' => 'file-spreadsheet',
                'description' => 'Consolidated Statutory, MIS, CBSE Compliance & Audit Reports',
                'submodules' => [
                    'academic_reports' => 'Student Performance & Promotion Reports',
                    'financial_reports' => 'Fee Dues, Ledger & Collection Summaries',
                    'transport_reports' => 'Route Occupancy & Fuel Efficiency Reports',
                    'staff_reports' => 'Staff Attendance & Leave Summaries',
                ],
                'default_actions' => ['view', 'view_reports', 'print', 'download', 'export'],
            ],
            'settings' => [
                'name' => 'System Settings',
                'icon' => 'sliders-horizontal',
                'description' => 'School Profile, Branding, Database Backup & Security PIN',
                'submodules' => [
                    'school_profile' => 'School Name, Affiliation & Registration',
                    'appearance' => 'Theme, Logos, Letterheads & Seals',
                    'backup' => 'Database Backup & System Maintenance',
                    'admin_pin' => 'Secondary Admin PIN & Security Rules',
                ],
                'default_actions' => ['view', 'edit', 'configure'],
            ],
            'rbac' => [
                'name' => 'Access Control & RBAC',
                'icon' => 'shield-check',
                'description' => 'User Management, Custom Roles, Permission Matrix & Audit Logs',
                'submodules' => [
                    'users' => 'ERP User Accounts & Authentication',
                    'roles' => 'System & Custom Roles',
                    'matrix' => 'Granular Permission Matrix',
                    'data_scopes' => 'Data-Level Access Boundaries',
                    'field_permissions' => 'PII & Sensitive Field Masking',
                    'account_targets' => 'Account Configuration Targets',
                    'audit_history' => 'Security & RBAC Audit Trail',
                ],
                'default_actions' => ['view', 'create', 'edit', 'delete', 'assign', 'configure', 'manage_permissions', 'export'],
            ],
        ];
    }

    /**
     * Check if a user has a specific permission string.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // 1. Account must be active
        if (!$user->isActive()) {
            return false;
        }

        // 2. Super Admin always possesses full authority
        if ($user->isSuperAdmin()) {
            return true;
        }

        $cacheKey = "rbac_user_perms_{$user->id}";
        $effectivePerms = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user) {
            return $this->getEffectivePermissionsList($user);
        });

        // Exact match
        if (in_array($permission, $effectivePerms, true)) {
            return true;
        }

        // Module wildcard match, e.g. "manage transport" grants "transport:create"
        if ($this->checkWildcardMatch($permission, $effectivePerms)) {
            return true;
        }

        return false;
    }

    /**
     * Check wildcard or legacy permission matches.
     */
    protected function checkWildcardMatch(string $permission, array $effectivePerms): bool
    {
        // Check for Spatie legacy permissions like "view transport", "edit transport", "manage students"
        $parts = explode(':', $permission);
        if (count($parts) === 2) {
            [$module, $action] = $parts;
            $legacyActionPerm = "{$action} {$module}";
            $legacyManagePerm = "manage {$module}";

            if (in_array($legacyActionPerm, $effectivePerms, true) || in_array($legacyManagePerm, $effectivePerms, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get effective permission list for a user.
     */
    public function getEffectivePermissionsList(User $user): array
    {
        $perms = [];

        // Load roles and their permissions
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $p) {
                $perms[] = $p->name;
            }
        }

        // Direct user permissions
        foreach ($user->permissions as $p) {
            $perms[] = $p->name;
        }

        return array_values(array_unique($perms));
    }

    /**
     * Clear user RBAC cache.
     */
    public static function clearUserCache(int $userId): void
    {
        Cache::forget("rbac_user_perms_{$userId}");
    }

    /**
     * Clear all RBAC cache.
     */
    public static function clearAllCache(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        // Clear cached keys
        $userIds = DB::table('users')->pluck('id');
        foreach ($userIds as $id) {
            Cache::forget("rbac_user_perms_{$id}");
        }
    }
}
