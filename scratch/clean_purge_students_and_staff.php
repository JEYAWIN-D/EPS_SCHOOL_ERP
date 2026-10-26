<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

echo "=== STARTING COMPREHENSIVE PURGE OF STUDENTS & STAFF ===" . PHP_EOL;

DB::beginTransaction();

try {
    // 1. Delete Student-dependent child records
    $studentChildTables = [
        'attendance_records',
        'attendance_condonations',
        'exam_marks',
        'marks_recheck_requests',
        'online_exam_attempts',
        'homework_submissions',
        'fee_payments',
        'fee_advance_payments',
        'fee_carry_forwards',
        'fee_change_logs',
        'student_fee_charges',
        'student_concessions',
        'student_custom_fees',
        'student_enrollments',
        'admission_book_note_items',
        'admission_inventory_issues',
        'student_documents',
        'student_outpasses',
        'student_leave_requests',
        'student_medical_records',
        'student_vaccinations',
        'student_promotions',
        'student_disciplinaries',
        'student_disciplinary',
        'tc_requests',
        'activity_learning_records',
        'coscholastic_assessments',
        'competency_assessments',
        'transport_roster_passengers',
        'transport_allotments',
        'transport_assignment_histories',
        'bus_attendance',
        'hostel_allotments',
        'hostel_complaints',
        'hostel_visitors',
        'hostel_disciplinary',
        'hostel_outpasses',
        'lms_assignment_submissions',
        'lms_lesson_progress',
        'lms_quiz_attempts',
        'parent_students',
    ];

    foreach ($studentChildTables as $tbl) {
        if (Schema::hasTable($tbl)) {
            $deleted = DB::table($tbl)->delete();
            if ($deleted > 0) {
                echo "Deleted {$deleted} records from {$tbl}" . PHP_EOL;
            }
        }
    }

    // Nullify or clean references in other tables for students
    if (Schema::hasTable('visitors') && Schema::hasColumn('visitors', 'student_id')) {
        DB::table('visitors')->update(['student_id' => null]);
    }
    if (Schema::hasTable('generated_documents') && Schema::hasColumn('generated_documents', 'student_id')) {
        DB::table('generated_documents')->whereNotNull('student_id')->delete();
    }
    if (Schema::hasTable('book_issues') && Schema::hasColumn('book_issues', 'student_id')) {
        DB::table('book_issues')->whereNotNull('student_id')->delete();
    }
    if (Schema::hasTable('book_reservations') && Schema::hasColumn('book_reservations', 'student_id')) {
        DB::table('book_reservations')->whereNotNull('student_id')->delete();
    }

    // 2. Delete Staff / Employee child records
    $employeeChildTables = [
        'staff_attendance',
        'teacher_subject_allocations',
        'timetables',
        'staff_class_teacher_assignments',
        'teacher_level_eligibilities',
        'teaching_level_activities',
        'employee_certifications',
        'employee_family_members',
        'employee_appointment_requests',
        'employee_documents',
        'employee_qualifications',
        'employee_experiences',
        'salary_structures',
        'salary_advances',
        'staff_duties',
        'staff_free_period_tasks',
        'staff_test_reminders',
        'employee_appraisals',
        'employee_assets',
        'employee_career_histories',
        'employee_loans',
        'employee_requests',
        'leave_encashments',
        'event_staff',
        'leave_requests',
        'night_duty_staff',
        'overtime_entries',
        'payroll_records',
        'staff_events',
        'staff_permission_sessions',
        'staff_work_assignments',
        'training_participants',
        'period_reports',
        'extra_classes',
        'arrears_bonus_entries',
    ];

    foreach ($employeeChildTables as $tbl) {
        if (Schema::hasTable($tbl)) {
            $deleted = DB::table($tbl)->delete();
            if ($deleted > 0) {
                echo "Deleted {$deleted} records from {$tbl}" . PHP_EOL;
            }
        }
    }

    if (Schema::hasTable('generated_documents') && Schema::hasColumn('generated_documents', 'employee_id')) {
        DB::table('generated_documents')->whereNotNull('employee_id')->delete();
    }
    if (Schema::hasTable('book_issues') && Schema::hasColumn('book_issues', 'employee_id')) {
        DB::table('book_issues')->whereNotNull('employee_id')->delete();
    }
    if (Schema::hasTable('book_reservations') && Schema::hasColumn('book_reservations', 'employee_id')) {
        DB::table('book_reservations')->whereNotNull('employee_id')->delete();
    }

    // 3. Clear user references to employees and students
    // First, unlink employee_id and student_id from users table to prevent FK errors
    if (Schema::hasColumn('users', 'employee_id')) {
        DB::table('users')->where('id', 1)->update(['employee_id' => null]);
    }
    if (Schema::hasColumn('users', 'student_id')) {
        DB::table('users')->where('id', 1)->update(['student_id' => null]);
    }

    // Delete users associated with students or non-admin roles
    $adminUserIds = DB::table('users')
        ->where('id', 1)
        ->orWhere('email', 'admin@schoolerp.in')
        ->pluck('id')
        ->toArray();

    echo "Preserving Super Admin User IDs: " . implode(', ', $adminUserIds) . PHP_EOL;

    // Delete student / staff users
    // First remove related records for those users (e.g. audit_logs, model_has_roles, sessions)
    $usersToDelete = DB::table('users')
        ->whereNotIn('id', $adminUserIds)
        ->where(function($q) {
            $q->whereNotNull('employee_id')
              ->orWhereNotNull('student_id')
              ->orWhereExists(function($sub) {
                  $sub->select(DB::raw(1))
                      ->from('model_has_roles')
                      ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                      ->whereColumn('model_has_roles.model_id', 'users.id')
                      ->whereIn('roles.name', ['teacher', 'student', 'parent', 'staff', 'nanny', 'driver', 'accountant', 'class_teacher']);
              });
        })
        ->pluck('id')
        ->toArray();

    echo "Found " . count($usersToDelete) . " staff/student user accounts to remove." . PHP_EOL;

    if (!empty($usersToDelete)) {
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->whereIn('model_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('model_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('user_data_scopes')) {
            DB::table('user_data_scopes')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('event_rsvps')) {
            DB::table('event_rsvps')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('notice_acknowledgements')) {
            DB::table('notice_acknowledgements')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('task_assignees')) {
            DB::table('task_assignees')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('task_updates')) {
            DB::table('task_updates')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('activity_audit_logs')) {
            DB::table('activity_audit_logs')->whereIn('user_id', $usersToDelete)->delete();
        }
        if (Schema::hasTable('meeting_attendees')) {
            DB::table('meeting_attendees')->whereIn('user_id', $usersToDelete)->delete();
        }

        // Now delete the users
        $deletedUsers = DB::table('users')->whereIn('id', $usersToDelete)->delete();
        echo "Deleted {$deletedUsers} staff/student users." . PHP_EOL;
    }

    // Set remaining non-deleted users' employee_id and student_id to null just in case
    if (Schema::hasColumn('users', 'employee_id')) {
        DB::table('users')->update(['employee_id' => null]);
    }
    if (Schema::hasColumn('users', 'student_id')) {
        DB::table('users')->update(['student_id' => null]);
    }

    // 4. Delete all Students
    $deletedStudents = DB::table('students')->delete();
    echo "Deleted {$deletedStudents} records from students table." . PHP_EOL;

    // 5. Delete all Employees
    $deletedEmployees = DB::table('employees')->delete();
    echo "Deleted {$deletedEmployees} records from employees table." . PHP_EOL;

    DB::commit();
    echo "=== DATABASE COMMIT SUCCESSFUL ===" . PHP_EOL;

    Cache::flush();
    echo "Flushed application cache." . PHP_EOL;

} catch (\Throwable $e) {
    DB::rollBack();
    echo "ERROR during purge: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== VERIFICATION AFTER PURGE ===" . PHP_EOL;
echo "Students count: " . Student::count() . PHP_EOL;
echo "Employees count: " . Employee::count() . PHP_EOL;
echo "Users count: " . User::count() . PHP_EOL;
echo "Super Admin exists: " . (User::where('email', 'admin@schoolerp.in')->exists() ? 'YES' : 'NO') . PHP_EOL;
echo "Transport Routes count: " . DB::table('transport_routes')->count() . PHP_EOL;
echo "Transport Stops count: " . DB::table('transport_stops')->count() . PHP_EOL;
echo "Transport Vehicles count: " . DB::table('vehicles')->count() . PHP_EOL;
echo "Classes count: " . DB::table('classes')->count() . PHP_EOL;
echo "Sections count: " . DB::table('sections')->count() . PHP_EOL;
