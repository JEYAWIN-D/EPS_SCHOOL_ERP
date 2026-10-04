<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Notice;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityHubSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Register Activity Hub Permissions
        $permissions = [
            'view activities',
            'view events',
            'manage notices',
            'publish notices',
            'approve notices',
            'manage events',
            'approve events',
            'manage meetings',
            'manage tasks',
            'assign tasks',
            'approve tasks',
            'view audit activities',
        ];

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Assign permissions to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $principal = Role::where('name', 'principal')->first();
        if ($principal) {
            $principal->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'view activities',
                'manage notices',
                'publish notices',
                'manage events',
                'manage meetings',
                'manage tasks',
                'assign tasks',
                'approve tasks',
            ]);
        }

        $teacher = Role::where('name', 'teacher')->first();
        if ($teacher) {
            $teacher->givePermissionTo([
                'view activities',
                'view events',
                'manage tasks',
            ]);
        }

        $classTeacher = Role::where('name', 'class_teacher')->first();
        if ($classTeacher) {
            $classTeacher->givePermissionTo([
                'view activities',
                'view events',
                'manage tasks',
            ]);
        }

        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->givePermissionTo(['view activities', 'view events']);
        }

        $student = Role::where('name', 'student')->first();
        if ($student) {
            $student->givePermissionTo(['view activities', 'view events']);
        }

        $parent = Role::where('name', 'parent')->first();
        if ($parent) {
            $parent->givePermissionTo(['view activities', 'view events']);
        }

        // 3. Find key users for seeding sample activity records
        $adminUser     = User::where('email', 'admin@schoolerp.in')->first() ?? User::first();
        $principalUser = User::where('email', 'principal@schoolerp.in')->first() ?? $adminUser;
        $teacherUser   = User::where('email', 'teacher@schoolerp.in')->first() ?? $adminUser;
        $employee      = Employee::first();
        $department    = Department::first();

        if (!$adminUser) return;

        // 4. Seed Notices
        $n1 = Notice::firstOrCreate(
            ['title' => 'Annual Sports Day 2026 Participation & Practice Schedule'],
            [
                'content' => 'All students from Classes 6 to 12 are invited to register for track & field events. Daily practice commences from 6:30 AM on the main athletics ground. Parents are welcome to attend the final day ceremony.',
                'notice_type' => 'event',
                'priority' => 'high',
                'target_audience' => 'all',
                'publish_date' => now()->toDateString(),
                'expiry_date' => now()->addDays(20)->toDateString(),
                'is_published' => true,
                'status' => 'published',
                'requires_approval' => false,
                'requires_acknowledgement' => true,
                'approval_status' => 'approved',
                'created_by' => $adminUser->id,
            ]
        );

        $n2 = Notice::firstOrCreate(
            ['title' => 'Mandatory CBSE Compliance Review & Internal Assessment Guidelines'],
            [
                'content' => 'All Subject HODs and Class Teachers must finalize term-1 internal marks portfolios before October 15, 2026. Review meetings will be conducted with the Principal.',
                'notice_type' => 'academic',
                'priority' => 'urgent',
                'target_audience' => 'staff',
                'publish_date' => now()->toDateString(),
                'expiry_date' => now()->addDays(14)->toDateString(),
                'is_published' => true,
                'status' => 'published',
                'requires_approval' => false,
                'requires_acknowledgement' => true,
                'approval_status' => 'approved',
                'created_by' => $principalUser->id,
            ]
        );

        $n3 = Notice::firstOrCreate(
            ['title' => 'Proposed Autumn Science & Robotics Exhibition 2026'],
            [
                'content' => 'Proposal for hosting an inter-school STEM exhibition on October 28, 2026. Featuring robotics, green energy models, and coding prototypes.',
                'notice_type' => 'circular',
                'priority' => 'normal',
                'target_audience' => 'all',
                'publish_date' => now()->toDateString(),
                'is_published' => false,
                'status' => 'pending_approval',
                'requires_approval' => true,
                'requires_acknowledgement' => false,
                'approval_status' => 'pending',
                'created_by' => $teacherUser->id,
            ]
        );

        // Seed Acknowledgement
        if ($n1 && $teacherUser) {
            DB::table('notice_acknowledgements')->updateOrInsert(
                ['notice_id' => $n1->id, 'user_id' => $teacherUser->id],
                [
                    'acknowledged_at' => now()->subHours(2),
                    'ip_address' => '127.0.0.1',
                    'feedback_note' => 'Noted. Athletics team schedule updated.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Seed Events
        $e1 = Event::firstOrCreate(
            ['name' => 'Inter-House Annual Sports Meet 2026'],
            [
                'event_type' => 'sports',
                'category' => 'School Championship',
                'event_date' => now()->addDays(12)->toDateString(),
                'start_time' => '08:30:00',
                'end_time' => '16:00:00',
                'venue' => 'Main Athletics Ground & Stadium',
                'description' => 'Comprehensive athletic events including 100m sprint, 4x100m relay, high jump, shot put, and inter-house tug of war.',
                'is_published' => true,
                'status' => 'published',
                'allow_rsvp' => true,
                'max_rsvp' => 500,
                'audience' => 'all',
                'created_by' => $adminUser->id,
                'organizer_id' => $principalUser->id,
                'budget_estimated' => 75000.00,
                'budget_actual' => 15000.00,
            ]
        );

        $e2 = Event::firstOrCreate(
            ['name' => 'CBSE Regional Science & Innovation Fair 2026'],
            [
                'event_type' => 'academic',
                'category' => 'Exhibition',
                'event_date' => now()->addDays(28)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '15:30:00',
                'venue' => 'Science Block Auditoriums 1 & 2',
                'description' => 'Showcasing over 60 working models on clean technology, AI prototypes, and agricultural robotics.',
                'is_published' => true,
                'status' => 'published',
                'allow_rsvp' => true,
                'audience' => 'all',
                'created_by' => $adminUser->id,
                'organizer_id' => $adminUser->id,
                'budget_estimated' => 50000.00,
                'budget_actual' => 0.00,
            ]
        );

        if ($e1 && $employee) {
            DB::table('event_staff')->updateOrInsert(
                ['event_id' => $e1->id, 'employee_id' => $employee->id, 'role' => 'in_charge'],
                [
                    'duties' => 'Overall event coordination, ground marking, medical kit readiness, certificate distribution.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 6. Seed Meetings
        $m1 = DB::table('meetings')->where('title', 'Weekly Academic Council & HOD Review')->first();
        if (!$m1) {
            $m1Id = DB::table('meetings')->insertGetId([
                'title' => 'Weekly Academic Council & HOD Review',
                'agenda' => "1. Review of syllabus completion percentage across Grades 9-12\n2. Lab equipment procurement status\n3. Upcoming Parent-Teacher Conference preparations",
                'meeting_type' => 'hod',
                'organizer_id' => $principalUser->id,
                'chairperson_id' => $principalUser->id,
                'department_id' => $department?->id,
                'start_time' => now()->addDays(2)->setTime(10, 30),
                'end_time' => now()->addDays(2)->setTime(11, 45),
                'venue' => 'Conference Hall A (Main Admin Block)',
                'meeting_link' => 'https://meet.google.com/xyz-school-hod',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attendee
            DB::table('meeting_attendees')->insert([
                'meeting_id' => $m1Id,
                'user_id' => $teacherUser->id,
                'is_optional' => false,
                'rsvp_status' => 'accepted',
                'attendance_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('meeting_attendees')->insert([
                'meeting_id' => $m1Id,
                'user_id' => $adminUser->id,
                'is_optional' => false,
                'rsvp_status' => 'accepted',
                'attendance_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $m2 = DB::table('meetings')->where('title', 'Grade 10 Parent-Teacher Meeting (PTM)')->first();
        if (!$m2) {
            $m2Id = DB::table('meetings')->insertGetId([
                'title' => 'Grade 10 Parent-Teacher Meeting (PTM)',
                'agenda' => 'Individual student progress review, pre-board schedule announcement, and remedial class planning.',
                'meeting_type' => 'ptm',
                'organizer_id' => $adminUser->id,
                'chairperson_id' => $principalUser->id,
                'start_time' => now()->addDays(21)->setTime(9, 0),
                'end_time' => now()->addDays(21)->setTime(14, 0),
                'venue' => 'School Auditorium & Respective Homerooms',
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('meeting_attendees')->insert([
                'meeting_id' => $m2Id,
                'user_id' => $teacherUser->id,
                'is_optional' => false,
                'rsvp_status' => 'accepted',
                'attendance_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. Seed Tasks
        $t1 = DB::table('tasks')->where('title', 'Finalize Mid-Term Assessment Papers')->first();
        if (!$t1) {
            $t1Id = DB::table('tasks')->insertGetId([
                'title' => 'Finalize Mid-Term Assessment Papers',
                'description' => 'Prepare and proofread Question Papers for Mathematics and Physics with blueprint mapping to Bloom taxonomy.',
                'creator_id' => $principalUser->id,
                'priority' => 'high',
                'start_date' => now()->subDays(2)->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => 'in_progress',
                'source_type' => 'independent',
                'department_id' => $department?->id,
                'estimated_hours' => 12.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('task_assignees')->insert([
                'task_id' => $t1Id,
                'user_id' => $teacherUser->id,
                'role' => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('task_updates')->insert([
                'task_id' => $t1Id,
                'user_id' => $teacherUser->id,
                'status_from' => 'accepted',
                'status_to' => 'in_progress',
                'comment' => 'Drafted Sections A and B; finalizing numerical problems.',
                'progress_percentage' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $t2 = DB::table('tasks')->where('title', 'Sports Equipment Inspection & Safety Audit')->first();
        if (!$t2) {
            $t2Id = DB::table('tasks')->insertGetId([
                'title' => 'Sports Equipment Inspection & Safety Audit',
                'description' => 'Inspect hurdles, high jump mattresses, goal nets, and first-aid kits prior to the annual sports championship.',
                'creator_id' => $adminUser->id,
                'priority' => 'normal',
                'start_date' => now()->toDateString(),
                'due_date' => now()->addDays(8)->toDateString(),
                'status' => 'pending',
                'source_type' => 'event',
                'source_id' => $e1->id,
                'estimated_hours' => 6.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('task_assignees')->insert([
                'task_id' => $t2Id,
                'user_id' => $teacherUser->id,
                'role' => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Seed Sample Audit Log
        DB::table('activity_audit_logs')->insert([
            'user_id' => $adminUser->id,
            'action' => 'published',
            'entity_type' => 'event',
            'entity_id' => $e1->id,
            'payload' => json_encode(['title' => $e1->name, 'date' => $e1->event_date]),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}
