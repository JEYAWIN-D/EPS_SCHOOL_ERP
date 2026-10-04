<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStaff;
use App\Models\Notice;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventsModuleUsabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        // Find or create admin user with super_admin role or view events permission
        $this->admin = User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->first()
            ?? User::first()
            ?? User::factory()->create(['name' => 'Admin Test', 'email' => 'admin_events@schoolerp.in']);

        \Spatie\Permission\Models\Permission::findOrCreate('view events', 'web');
        $this->admin->givePermissionTo('view events');

        // Find or create an employee
        $this->employee = Employee::where('is_active', true)->first();
        if (!$this->employee) {
            $this->employee = Employee::create([
                'employee_code'  => 'EMP-EV-01',
                'first_name'     => 'Anand',
                'last_name'      => 'Raj',
                'designation'    => 'Sports Director',
                'department'     => 'Sports',
                'joining_date'   => today()->subYear(),
                'is_active'      => true,
                'official_email' => 'anand.raj@schoolerp.in',
            ]);
        }
    }

    /** @test */
    public function test_events_index_page_loads_and_has_clean_navigation()
    {
        $response = $this->actingAs($this->admin)->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('School Events');
        $response->assertSee('Circulars &amp; Official Orders', false);
        $response->assertSee('Calendar View');
        $response->assertSee('New Event');
    }

    /** @test */
    public function test_duplicate_circulars_button_near_calendar_view_is_removed()
    {
        $response = $this->actingAs($this->admin)->get(route('events.index'));

        $response->assertStatus(200);

        $html = $response->getContent();

        // The top switcher tab for Circulars & Official Orders should be present
        $this->assertStringContainsString('Circulars &amp; Official Orders', $html);

        // But there should NOT be an additional "Circulars & Orders" button right next to Calendar View in header actions
        $this->assertStringNotContainsString('Circulars &amp; Orders</a>', $html);
    }

    /** @test */
    public function test_calendar_page_displays_events_and_circulars_and_orders()
    {
        // Create an event for current month
        $event = Event::create([
            'name'         => 'Annual Athletic Sports Meet',
            'event_type'   => 'sports',
            'event_date'   => now()->startOfMonth()->addDays(10),
            'start_time'   => '09:00',
            'end_time'     => '16:00',
            'venue'        => 'Main Sports Complex',
            'description'  => 'Annual inter-house sports competitions',
            'audience'     => 'all',
            'is_published' => true,
            'created_by'   => $this->admin->id,
        ]);

        // Create a circular for current month
        $circular = Notice::create([
            'title'           => 'Exam Schedule & Code of Conduct',
            'reference_no'    => 'EPS/CIR/2026/045',
            'notice_type'     => 'circular',
            'priority'        => 'high',
            'target_audience' => 'all',
            'publish_date'    => now()->startOfMonth()->addDays(10),
            'content'         => 'Detailed guidelines for upcoming semester examinations.',
            'is_published'    => true,
            'created_by'      => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('events.calendar', [
            'month' => now()->month,
            'year'  => now()->year,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Institutional Calendar');
        $response->assertSee('Annual Athletic Sports Meet');
        $response->assertSee('Exam Schedule &amp; Code of Conduct', false);
        $response->assertSee('Academic');
        $response->assertSee('Circular');
        $response->assertSee('Official Order');
    }

    /** @test */
    public function test_calendar_json_endpoint_returns_events_and_circulars()
    {
        $event = Event::create([
            'name'         => 'Science Exhibition',
            'event_type'   => 'academic',
            'event_date'   => now()->startOfMonth()->addDays(5),
            'audience'     => 'all',
            'is_published' => true,
            'created_by'   => $this->admin->id,
        ]);

        $circular = Notice::create([
            'title'           => 'Holiday Notice on Local Festival',
            'reference_no'    => 'EPS/CIR/2026/077',
            'notice_type'     => 'circular',
            'priority'        => 'normal',
            'target_audience' => 'all',
            'publish_date'    => now()->startOfMonth()->addDays(5),
            'content'         => 'School will remain closed.',
            'is_published'    => true,
            'created_by'      => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('events.calendar', [
            'month' => now()->month,
            'year'  => now()->year,
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);

        $eventFound = collect($data)->firstWhere('title', 'Science Exhibition');
        $this->assertNotNull($eventFound);
        $this->assertEquals('event', $eventFound['item_type']);

        $circularFound = collect($data)->first(fn($i) => str_contains($i['title'], 'Holiday Notice'));
        $this->assertNotNull($circularFound);
        $this->assertEquals('circular', $circularFound['item_type']);
    }

    /** @test */
    public function test_event_assignment_assign_later_workflow()
    {
        // 1. Create event with assignment_mode = 'later' (Assign Later)
        $response = $this->actingAs($this->admin)->post(route('events.store'), [
            'name'            => 'Mathematics Olympiad 2026',
            'event_type'      => 'academic',
            'event_date'      => today()->addDays(7)->toDateString(),
            'start_time'      => '10:00',
            'end_time'        => '12:00',
            'venue'           => 'Hall B',
            'audience'        => 'students',
            'is_published'    => 1,
            'assignment_mode' => 'later',
            'description'     => 'Competitive math exam.',
        ]);

        $response->assertRedirect();
        
        $event = Event::where('name', 'Mathematics Olympiad 2026')->first();
        $this->assertNotNull($event);
        $this->assertFalse($event->is_assigned);
        $this->assertEquals('Pending Assignment', $event->assignment_status_label);
        $this->assertCount(0, $event->staff);

        // 2. View event details - confirms Pending Assignment displayed
        $showResp = $this->actingAs($this->admin)->get(route('events.show', $event->id));
        $showResp->assertStatus(200);
        $showResp->assertSee('Pending Assignment');

        // 3. Authorized user assigns staff later through edit action
        $updateResp = $this->actingAs($this->admin)->put(route('events.update', $event->id), [
            'name'            => 'Mathematics Olympiad 2026',
            'event_type'      => 'academic',
            'event_date'      => today()->addDays(7)->toDateString(),
            'start_time'      => '10:00',
            'end_time'        => '12:00',
            'venue'           => 'Hall B',
            'audience'        => 'students',
            'is_published'    => 1,
            'assignment_mode' => 'now',
            'staff'           => [
                [
                    'employee_id' => $this->employee->id,
                    'role'        => 'in_charge',
                    'duties'      => 'Exam invigilation and evaluation',
                ]
            ],
            'description'     => 'Competitive math exam.',
        ]);

        $updateResp->assertRedirect(route('events.show', $event->id));

        $event->refresh();
        $this->assertTrue($event->is_assigned);
        $this->assertEquals('Assigned', $event->assignment_status_label);
        $this->assertCount(1, $event->staff);
        $this->assertEquals($this->employee->id, $event->staff->first()->employee_id);
    }

    /** @test */
    public function test_event_assignment_assign_now_workflow()
    {
        $response = $this->actingAs($this->admin)->post(route('events.store'), [
            'name'            => 'Independence Day Celebration',
            'event_type'      => 'cultural',
            'event_date'      => today()->addDays(14)->toDateString(),
            'start_time'      => '08:30',
            'end_time'        => '11:00',
            'venue'           => 'School Parade Ground',
            'audience'        => 'all',
            'is_published'    => 1,
            'assignment_mode' => 'now',
            'staff'           => [
                [
                    'employee_id' => $this->employee->id,
                    'role'        => 'in_charge',
                    'duties'      => 'Parade coordination',
                ]
            ],
            'description'     => 'Flag hoisting and student parade.',
        ]);

        $response->assertRedirect();

        $event = Event::where('name', 'Independence Day Celebration')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->is_assigned);
        $this->assertEquals('Assigned', $event->assignment_status_label);
        $this->assertCount(1, $event->staff);

        // View index page to verify Assigned badge
        $indexResp = $this->actingAs($this->admin)->get(route('events.index'));
        $indexResp->assertStatus(200);
        $indexResp->assertSee('Independence Day Celebration');
        $indexResp->assertSee('Assigned');
    }

    /** @test */
    public function test_circulars_index_page_loads_and_has_calendar_button()
    {
        $response = $this->actingAs($this->admin)->get(route('circulars.index'));

        $response->assertStatus(200);
        $response->assertSee('Circulars &amp; Official Orders', false);
        $response->assertSee('Calendar View');
        $response->assertSee('Issue Circular / Order');
    }

    /** @test */
    public function test_events_index_assignment_filters()
    {
        // Event with assignment
        $assigned = Event::create([
            'name'         => 'Assigned Test Event',
            'event_type'   => 'other',
            'event_date'   => today()->addDays(2),
            'audience'     => 'all',
            'is_published' => true,
            'created_by'   => $this->admin->id,
        ]);
        EventStaff::create([
            'event_id'    => $assigned->id,
            'employee_id' => $this->employee->id,
            'role'        => 'in_charge',
        ]);

        // Event without assignment
        $unassigned = Event::create([
            'name'         => 'Unassigned Test Event',
            'event_type'   => 'other',
            'event_date'   => today()->addDays(3),
            'audience'     => 'all',
            'is_published' => true,
            'created_by'   => $this->admin->id,
        ]);

        // Filter assigned
        $respAssigned = $this->actingAs($this->admin)->get(route('events.index', ['assignment' => 'assigned']));
        $respAssigned->assertStatus(200);
        $respAssigned->assertSee('Assigned Test Event');
        $respAssigned->assertDontSee('Unassigned Test Event');

        // Filter pending
        $respPending = $this->actingAs($this->admin)->get(route('events.index', ['assignment' => 'pending']));
        $respPending->assertStatus(200);
        $respPending->assertSee('Unassigned Test Event');
        $respPending->assertDontSee('Assigned Test Event');
    }

    /** @test */
    public function test_calendar_date_events_modal_structure_and_scroll_lock_attributes()
    {
        $response = $this->actingAs($this->admin)->get(route('events.calendar', [
            'month' => now()->month,
            'year'  => now()->year,
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Viewport teleport to body to escape ancestor transforms & overflow clipping
        $this->assertStringContainsString('x-teleport="body"', $html);

        // 2. Fixed overlay with top z-index, backdrop blur, and wheel stop
        $this->assertStringContainsString('fixed inset-0 z-[9999] flex items-center justify-center', $html);
        $this->assertStringContainsString('backdrop-blur-xs overscroll-contain', $html);
        $this->assertStringContainsString('@wheel.self.prevent', $html);

        // 3. Background scroll locking (both main container and body)
        $this->assertStringContainsString("mainEl.style.overflow = 'hidden'", $html);
        $this->assertStringContainsString("document.body.style.overflow = 'hidden'", $html);
        $this->assertStringContainsString('syncScrollLock()', $html);
        $this->assertStringContainsString('closeDayModal()', $html);

        // 4. Modal dialog card with controlled bounded height and flex-col
        $this->assertStringContainsString('max-w-lg w-full flex flex-col overflow-hidden', $html);
        $this->assertStringContainsString('max-height: min(520px, 80vh);', $html);

        // 5. Fixed header and footer with flex-shrink-0
        $this->assertStringContainsString('Scheduled Records', $html);
        $this->assertStringContainsString('flex-shrink-0', $html);
        $this->assertStringContainsString('+ Add Event on this Date', $html);

        // 6. Independently scrollable internal event list
        $this->assertStringContainsString('flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-4', $html);

        // 7. Empty state support
        $this->assertStringContainsString('No scheduled events or circulars', $html);
    }

    /** @test */
    public function test_calendar_date_events_modal_with_many_events_renders_correctly()
    {
        $targetDate = now()->startOfMonth()->addDays(12);

        // Create 20 events on that single date to verify multi-event support
        for ($i = 1; $i <= 20; $i++) {
            Event::create([
                'name'         => "Academic Symposium Session {$i}",
                'event_type'   => ($i % 2 === 0) ? 'academic' : 'meeting',
                'event_date'   => $targetDate,
                'start_time'   => sprintf('%02d:00', ($i % 12) + 8),
                'venue'        => "Hall Room {$i}",
                'audience'     => 'teachers',
                'description'  => "Description for academic symposium session {$i}",
                'is_published' => true,
                'created_by'   => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('events.calendar', [
            'month' => $targetDate->month,
            'year'  => $targetDate->year,
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Ensure session events are in the payload
        $this->assertStringContainsString('Academic Symposium Session 1', $html);
        $this->assertStringContainsString('Academic Symposium Session 20', $html);

        // Ensure original card design elements exist
        $this->assertStringContainsString('Pending Assignment', $html);
        $this->assertStringContainsString('View Details', $html);
        $this->assertStringContainsString('Edit Event', $html);
    }
}

