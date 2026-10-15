<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Employee;
use App\Models\ExtraClass;
use App\Models\Section;
use App\Models\StaffDuty;
use App\Models\StaffFreePeriodTask;
use App\Models\StaffTestReminder;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use App\Services\StaffTimetableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StaffTimetableTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $teacherUser;
    protected Employee $teacherEmployee;
    protected StaffTimetableService $timetableService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'school_erp',
        ]);

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->timetableService = app(StaffTimetableService::class);

        // Super Admin user
        $this->adminUser = User::role('super_admin')->where('is_active', true)->firstOrFail();
        $this->adminUser->update(['must_change_password' => false]);

        // Find or create a teacher user with linked employee
        $this->teacherUser = User::role('teacher')->where('is_active', true)->first();
        if (!$this->teacherUser) {
            $this->teacherUser = User::create([
                'name'                 => 'Staff Timetable Faculty Test',
                'email'                => 'faculty.timetable.test@schoolerp.in',
                'password'             => bcrypt('Secret123!'),
                'is_active'            => true,
                'must_change_password' => false,
            ]);
            $this->teacherUser->assignRole('teacher');
        } else {
            $this->teacherUser->update(['must_change_password' => false]);
        }

        // Ensure teacher has an Employee record
        if ($this->teacherUser->employee_id) {
            $this->teacherEmployee = Employee::find($this->teacherUser->employee_id);
        } else {
            $this->teacherEmployee = Employee::where('user_id', $this->teacherUser->id)->first();
        }

        if (!$this->teacherEmployee) {
            $this->teacherEmployee = Employee::create([
                'user_id'       => $this->teacherUser->id,
                'employee_code' => 'TESTFAC01',
                'first_name'    => 'Faculty',
                'last_name'     => 'Tester',
                'official_email'=> $this->teacherUser->email,
                'is_active'     => true,
                'employee_type' => 'teaching',
            ]);
            $this->teacherUser->update(['employee_id' => $this->teacherEmployee->id]);
        }
    }

    /**
     * Test 1: Authenticated teacher can view staff timetable hub.
     */
    public function test_teacher_can_view_staff_timetable_hub()
    {
        $response = $this->actingAs($this->teacherUser)->get(route('staff-timetable.index'));
        $response->assertStatus(200);
        $response->assertSee('Staff Timetable & Teaching Work Management');
        $response->assertSee($this->teacherEmployee->first_name);
    }

    /**
     * Test 2: Teacher can access /my-timetable shortcut.
     */
    public function test_teacher_can_access_my_timetable_shortcut()
    {
        $response = $this->actingAs($this->teacherUser)->get(route('staff-timetable.my'));
        $response->assertStatus(302);
        $response->assertRedirect(route('staff-timetable.index', [
            'staff_id' => $this->teacherEmployee->id,
            'view'     => 'weekly',
            'date'     => today()->toDateString(),
        ]));
    }

    /**
     * Test 3: Admin can view workload analytics.
     */
    public function test_admin_can_view_workload_analytics()
    {
        $response = $this->actingAs($this->adminUser)->get(route('staff-timetable.analytics'));
        $response->assertStatus(200);
        $response->assertSee('Staff Workload & Academic Analytics');
        $response->assertSee('Average Utilization');
    }

    /**
     * Test 4: Free period calculation correctly identifies unallocated working periods.
     */
    public function test_free_period_calculation()
    {
        // Monday next week
        $nextMonday = today()->next(Carbon::MONDAY);
        $daySchedule = $this->timetableService->getDaySchedule($this->teacherEmployee, $nextMonday);

        $this->assertIsArray($daySchedule);
        $this->assertArrayHasKey('periods', $daySchedule);
        $this->assertArrayHasKey('metrics', $daySchedule);
        $this->assertEquals(8, count($daySchedule['periods']));

        // Total periods = teaching + free + duties + extra
        $metrics = $daySchedule['metrics'];
        $total = $metrics['teaching_periods'] + $metrics['free_periods'] + $metrics['duties'];
        $this->assertEquals(8, $total);
    }

    /**
     * Test 5: Extra class scheduling with conflict detection.
     */
    public function test_extra_class_scheduling_and_conflict_prevention()
    {
        $class = Classes::first();
        $section = Section::first();
        $subject = Subject::first();

        if (!$class || !$section || !$subject) {
            $this->markTestSkipped('Classes, sections, or subjects missing in database.');
        }

        $targetDate = today()->next(Carbon::TUESDAY)->toDateString();

        // 1. Schedule Extra Class
        $postData = [
            'teacher_id'    => $this->teacherEmployee->id,
            'class_id'      => $class->id,
            'section_id'    => $section->id,
            'subject_id'    => $subject->id,
            'date'          => $targetDate,
            'period_number' => 7,
            'room'          => 'Lab A',
            'reason'        => 'Special Revision Class',
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('staff-timetable.extra-class.store'), $postData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('extra_classes', [
            'teacher_id'    => $this->teacherEmployee->id,
            'period_number' => 7,
            'date'          => $targetDate,
        ]);

        // 2. Attempt to schedule another extra class for the SAME teacher at the SAME period (Double-booking)
        $conflictResponse = $this->actingAs($this->adminUser)->postJson(route('staff-timetable.extra-class.store'), $postData);
        $conflictResponse->assertStatus(422);
        $conflictResponse->assertJson(['success' => false]);
        $this->assertTrue(str_contains($conflictResponse->getContent(), 'Teacher double-booking'));
    }

    /**
     * Test 6: Free period task booking and cancellation lifecycle.
     */
    public function test_free_period_task_lifecycle()
    {
        $targetDate = today()->next(Carbon::WEDNESDAY)->toDateString();

        // Book a free period task
        $taskData = [
            'teacher_id'    => $this->teacherEmployee->id,
            'date'          => $targetDate,
            'period_number' => 8,
            'activity_type' => 'lesson_preparation',
            'title'         => 'Prepare Class 10 Physics Notes',
            'notes'         => 'Complete Chapter 4 diagrams',
        ];

        $response = $this->actingAs($this->teacherUser)->postJson(route('staff-timetable.free-task.store'), $taskData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $task = StaffFreePeriodTask::where('teacher_id', $this->teacherEmployee->id)
            ->whereDate('date', $targetDate)
            ->where('period_number', 8)
            ->first();

        $this->assertNotNull($task);
        $this->assertEquals('Prepare Class 10 Physics Notes', $task->title);

        // Delete / Clear task
        $deleteResponse = $this->actingAs($this->teacherUser)->deleteJson(route('staff-timetable.free-task.delete', $task->id));
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('staff_free_period_tasks', ['id' => $task->id]);
    }

    /**
     * Test 7: Test reminder creation and deletion.
     */
    public function test_test_reminder_creation_and_deletion()
    {
        $class = Classes::first();
        $section = Section::first();
        $subject = Subject::first();

        $testData = [
            'teacher_id'        => $this->teacherEmployee->id,
            'class_id'          => $class->id,
            'section_id'        => $section->id,
            'subject_id'        => $subject->id,
            'test_title'        => 'Weekly Assessment 1',
            'test_type'         => 'class_test',
            'test_date'         => today()->addDays(5)->toDateString(),
            'max_marks'         => 30,
            'syllabus_details'  => 'Units 1 and 2',
        ];

        $response = $this->actingAs($this->teacherUser)->postJson(route('staff-timetable.test-reminder.store'), $testData);
        $response->assertStatus(200);

        $reminder = StaffTestReminder::where('teacher_id', $this->teacherEmployee->id)
            ->where('test_title', 'Weekly Assessment 1')
            ->first();

        $this->assertNotNull($reminder);

        // Delete reminder
        $deleteResponse = $this->actingAs($this->teacherUser)->deleteJson(route('staff-timetable.test-reminder.delete', $reminder->id));
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('staff_test_reminders', ['id' => $reminder->id]);
    }

    /**
     * Test 8: Printable PDF Export view renders with status 200.
     */
    public function test_pdf_export_view_renders()
    {
        $response = $this->actingAs($this->teacherUser)->get(route('staff-timetable.pdf', $this->teacherEmployee->id));
        $response->assertStatus(200);
        $response->assertSee('Faculty Timetable');
        $response->assertSee($this->teacherEmployee->employee_code);
    }

    /**
     * Test 9: API free staff lookup returns valid JSON list of available teachers.
     */
    public function test_api_free_staff_lookup()
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('staff-timetable.api.free-staff', [
            'date'          => today()->next(Carbon::THURSDAY)->toDateString(),
            'period_number' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'date',
            'period',
            'count',
            'staff',
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test 10: API conflict check accurately flags teacher collision.
     */
    public function test_api_conflict_check_accurately_flags()
    {
        $targetDate = today()->next(Carbon::FRIDAY)->toDateString();

        // Create a duty for period 3
        $duty = StaffDuty::create([
            'teacher_id'       => $this->teacherEmployee->id,
            'duty_type'        => 'exam_duty',
            'title'            => 'Exam Invigilation',
            'date'             => $targetDate,
            'period_number'    => 3,
            'room'             => 'Hall 1',
            'status'           => 'scheduled',
            'assigned_by'      => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('staff-timetable.api.conflict-check', [
            'teacher_id'    => $this->teacherEmployee->id,
            'date'          => $targetDate,
            'period_number' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'has_conflict' => true,
        ]);
    }
}
