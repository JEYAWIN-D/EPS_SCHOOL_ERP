<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\EventPhoto;
use App\Models\EventStaff;
use App\Models\Employee;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $events = Event::with(['createdBy', 'staff.employee'])
            ->forUser($user)
            ->when($request->type, fn($q, $v) => $q->where('event_type', $v))
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->from, fn($q, $v) => $q->whereDate('event_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('event_date', '<=', $v))
            ->when($request->status === 'draft',     fn($q) => $q->where('is_published', false))
            ->when($request->status === 'published', fn($q) => $q->where('is_published', true))
            ->when(in_array($request->status, ['today', 'present']), fn($q) => $q->whereDate('event_date', today()))
            ->when($request->status === 'upcoming',  fn($q) => $q->where('is_published', true)->whereDate('event_date', '>', today()))
            ->when(in_array($request->status, ['past', 'completed']), fn($q) => $q->whereDate('event_date', '<', today()))
            ->when($request->assignment === 'assigned', fn($q) => $q->has('staff'))
            ->when($request->assignment === 'pending', fn($q) => $q->doesntHave('staff'))
            ->orderBy('event_date', 'desc')
            ->paginate(20)->withQueryString();

        // Optimized single query for KPIs (ANSI SQL compatible across PostgreSQL & MySQL)
        $todayStr   = today()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();

        $kpis = Event::forUser($user)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN event_date = ? THEN 1 END) as today_count,
                COUNT(CASE WHEN event_date > ? AND is_published = true THEN 1 END) as upcoming_count,
                COUNT(CASE WHEN event_date < ? THEN 1 END) as completed_count,
                COUNT(CASE WHEN event_date >= ? AND event_date <= ? THEN 1 END) as month_count
            ", [$todayStr, $todayStr, $todayStr, $monthStart, $monthEnd])
            ->first();

        $totalEvents    = (int) ($kpis->total ?? 0);
        $todayEvents    = (int) ($kpis->today_count ?? 0);
        $upcomingCount  = (int) ($kpis->upcoming_count ?? 0);
        $completedCount = (int) ($kpis->completed_count ?? 0);
        $thisMonthCount = (int) ($kpis->month_count ?? 0);

        $upcomingEvents = Event::forUser($user)->whereDate('event_date', '>=', today())
            ->where('is_published', true)
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $canCreate = $this->canManageEvents($user);

        return view('events.index', compact(
            'events', 'totalEvents', 'todayEvents',
            'upcomingCount', 'completedCount', 'thisMonthCount', 'upcomingEvents',
            'canCreate'
        ));
    }

    public function calendar(Request $request)
    {
        $user   = Auth::user();
        $month  = (int) ($request->month ?? now()->month);
        $year   = (int) ($request->year  ?? now()->year);
        $start  = Carbon::createFromDate($year, $month, 1);
        $end    = $start->copy()->endOfMonth();

        $eventsList = Event::with(['staff.employee', 'createdBy'])
            ->forUser($user)
            ->where('is_published', true)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('event_date')
            ->get();

        $events = $eventsList->groupBy(fn($e) => $e->event_date->day);

        $circularsList = Notice::with(['createdBy', 'targetClass'])
            ->forUser($user)
            ->where('is_published', true)
            ->whereBetween('publish_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('publish_date')
            ->get();

        $circulars = $circularsList->filter(fn($n) => $n->notice_type === 'circular' || !empty($n->reference_no))
            ->groupBy(fn($c) => $c->publish_date ? $c->publish_date->day : $c->created_at->day);

        $orders = $circularsList->filter(fn($n) => in_array($n->notice_type, ['order', 'directive', 'memo']) || !empty($n->order_category))
            ->groupBy(fn($o) => $o->publish_date ? $o->publish_date->day : $o->created_at->day);

        // For FullCalendar JSON or API requests
        if ($request->wantsJson()) {
            $rangeStart = Carbon::createFromDate($year, $month, 1)->subMonth()->toDateString();
            $rangeEnd   = Carbon::createFromDate($year, $month, 1)->addMonths(2)->toDateString();

            $allEvents = Event::forUser($user)
                ->where('is_published', true)
                ->whereBetween('event_date', [$rangeStart, $rangeEnd])
                ->get()
                ->map(fn($e) => [
                    'id'          => 'event-' . $e->id,
                    'item_id'     => $e->id,
                    'item_type'   => 'event',
                    'title'       => $e->name,
                    'start'       => $e->event_date->toDateString(),
                    'color'       => match($e->event_type) {
                        'academic'  => '#4f46e5',
                        'cultural'  => '#db2777',
                        'sports'    => '#059669',
                        'holiday'   => '#d97706',
                        'meeting'   => '#2563eb',
                        default     => '#475569',
                    },
                    'url'         => route('events.show', $e->id),
                    'edit_url'    => route('events.edit', $e->id),
                    'is_assigned' => $e->is_assigned,
                ]);

            $allNotices = Notice::forUser($user)
                ->where('is_published', true)
                ->whereBetween('publish_date', [$rangeStart, $rangeEnd])
                ->get()
                ->map(fn($n) => [
                    'id'          => 'notice-' . $n->id,
                    'item_id'     => $n->id,
                    'item_type'   => in_array($n->notice_type, ['order', 'directive', 'memo']) ? 'order' : 'circular',
                    'title'       => ($n->reference_no ? "[{$n->reference_no}] " : '') . $n->title,
                    'start'       => $n->publish_date ? $n->publish_date->toDateString() : $n->created_at->toDateString(),
                    'color'       => in_array($n->notice_type, ['order', 'directive', 'memo']) ? '#7c3aed' : '#0284c7',
                    'url'         => route('circulars.show', $n->id),
                    'edit_url'    => route('circulars.edit', $n->id),
                    'is_assigned' => true,
                ]);

            return response()->json($allEvents->concat($allNotices));
        }

        $days = range(1, $end->day);
        $startDow = $start->dayOfWeek; // 0=Sun

        return view('events.calendar', compact('events', 'circulars', 'orders', 'days', 'startDow', 'month', 'year'));
    }

    public function show(int $id)
    {
        $event  = Event::with(['rsvps.user', 'photos', 'staff.employee', 'createdBy'])->findOrFail($id);
        $myRsvp = Auth::check()
            ? EventRsvp::where('event_id', $id)->where('user_id', Auth::id())->first()
            : null;
        $rsvpCounts = $event->rsvps->countBy('status');
        $user = Auth::user();
        $canEdit = $this->canEditEvent($user, $event);
        $canDelete = $this->canDeleteEvent($user, $event);

        return view('events.show', compact('event', 'myRsvp', 'rsvpCounts', 'canEdit', 'canDelete'));
    }

    public function create()
    {
        $this->authorizeManageEvents();
        $employees = Employee::orderBy('first_name')->get();
        return view('events.form', compact('employees'));
    }

    public function store(Request $request)
    {
        $this->authorizeManageEvents();

        // Safely normalize time inputs
        if ($request->filled('start_time')) {
            try {
                $request->merge(['start_time' => Carbon::parse($request->start_time)->format('H:i')]);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['start_time' => 'Please provide a valid start time format.']);
            }
        }
        if ($request->filled('end_time')) {
            try {
                $request->merge(['end_time' => Carbon::parse($request->end_time)->format('H:i')]);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['end_time' => 'Please provide a valid end time format.']);
            }
        }

        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'event_type'      => 'required|in:academic,cultural,sports,holiday,meeting,other',
            'event_date'      => 'required|date',
            'start_time'      => 'nullable|string',
            'end_time'        => 'nullable|string',
            'venue'           => 'nullable|string|max:200',
            'description'     => 'nullable|string',
            'audience'        => 'required|in:all,students,staff,parents',
            'allow_rsvp'      => 'boolean',
            'max_rsvp'        => 'nullable|integer|min:1',
            'is_published'    => 'boolean',
            'banner_image'    => 'nullable|image|max:2048',
            'assignment_mode' => 'nullable|in:now,later',
            'staff'           => 'nullable|array',
            'staff.*.employee_id' => 'nullable|exists:employees,id',
            'staff.*.role'        => 'nullable|string|in:in_charge,coordinator,support',
            'staff.*.duties'      => 'nullable|string',
        ]);

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['start_time'] === $data['end_time']) {
                return back()->withInput()->withErrors(['end_time' => 'The end time cannot be identical to the start time.']);
            }
        }

        // Sanitize rich text description against XSS injection
        if (!empty($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
        }

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('events/banners', 'public');
        }

        $data['created_by'] = Auth::id();
        $data['status']     = !empty($data['is_published']) ? 'published' : 'draft';
        if (Schema::hasColumn('events', 'school_id') && Auth::user()?->school_id) {
            $data['school_id'] = Auth::user()->school_id;
        }

        $event = Event::create($data);

        // Staff assignment: assign now or assign later
        if ($request->input('assignment_mode') !== 'later' && $request->has('staff') && is_array($request->staff)) {
            foreach ($request->staff as $st) {
                if (!empty($st['employee_id'])) {
                    EventStaff::create([
                        'event_id'    => $event->id,
                        'employee_id' => $st['employee_id'],
                        'role'        => $st['role'] ?? 'in_charge',
                        'duties'      => $st['duties'] ?? null,
                    ]);
                }
            }
        }

        if ($event->is_published) {
            $this->notifyEventPublished($event);
        }

        return redirect()->route('events.show', $event->id)->with('success', 'Event created successfully.');
    }

    public function edit(int $id)
    {
        $event = Event::with('staff.employee')->findOrFail($id);
        $this->authorizeEditEvent($event);

        $employees = Employee::orderBy('first_name')->get();
        return view('events.form', compact('event', 'employees'));
    }

    public function update(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        $this->authorizeEditEvent($event);

        if ($request->filled('start_time')) {
            try {
                $request->merge(['start_time' => Carbon::parse($request->start_time)->format('H:i')]);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['start_time' => 'Please provide a valid start time format.']);
            }
        }
        if ($request->filled('end_time')) {
            try {
                $request->merge(['end_time' => Carbon::parse($request->end_time)->format('H:i')]);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['end_time' => 'Please provide a valid end time format.']);
            }
        }

        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'event_type'      => 'required|in:academic,cultural,sports,holiday,meeting,other',
            'event_date'      => 'required|date',
            'start_time'      => 'nullable|string',
            'end_time'        => 'nullable|string',
            'venue'           => 'nullable|string|max:200',
            'description'     => 'nullable|string',
            'audience'        => 'required|in:all,students,staff,parents',
            'allow_rsvp'      => 'boolean',
            'max_rsvp'        => 'nullable|integer|min:1',
            'is_published'    => 'boolean',
            'banner_image'    => 'nullable|image|max:2048',
            'remove_banner'   => 'nullable|boolean',
            'assignment_mode' => 'nullable|in:now,later',
            'staff'           => 'nullable|array',
            'staff.*.employee_id' => 'nullable|exists:employees,id',
            'staff.*.role'        => 'nullable|string|in:in_charge,coordinator,support',
            'staff.*.duties'      => 'nullable|string',
        ]);

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['start_time'] === $data['end_time']) {
                return back()->withInput()->withErrors(['end_time' => 'The end time cannot be identical to the start time.']);
            }
        }

        // Sanitize rich text description against XSS injection
        if (isset($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
        }

        // Handle banner removal or update
        if ($request->boolean('remove_banner')) {
            if ($event->banner_image) {
                Storage::disk('public')->delete($event->banner_image);
            }
            $data['banner_image'] = null;
        } elseif ($request->hasFile('banner_image')) {
            if ($event->banner_image) {
                Storage::disk('public')->delete($event->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('events/banners', 'public');
        }

        $data['status'] = !empty($data['is_published']) ? 'published' : 'draft';

        $wasPublished = $event->is_published;
        $event->update($data);

        // Update staff assignments
        if ($request->has('assignment_mode') || $request->has('staff')) {
            $event->staff()->delete();
            if ($request->input('assignment_mode') !== 'later' && $request->has('staff') && is_array($request->staff)) {
                foreach ($request->staff as $st) {
                    if (!empty($st['employee_id'])) {
                        EventStaff::create([
                            'event_id'    => $event->id,
                            'employee_id' => $st['employee_id'],
                            'role'        => $st['role'] ?? 'in_charge',
                            'duties'      => $st['duties'] ?? null,
                        ]);
                    }
                }
            }
        }

        if (!$wasPublished && $event->fresh()->is_published) {
            $this->notifyEventPublished($event->fresh());
        }

        return redirect()->route('events.show', $event->id)->with('success', 'Event updated successfully.');
    }

    public function destroy(int $id)
    {
        $event = Event::with('photos')->findOrFail($id);
        $this->authorizeDeleteEvent($event);

        // Delete banner image from public storage
        if ($event->banner_image) {
            Storage::disk('public')->delete($event->banner_image);
        }

        // Delete all photo files from disk to prevent storage leaks
        foreach ($event->photos as $photo) {
            if ($photo->photo_path) {
                Storage::disk('public')->delete($photo->photo_path);
            }
        }

        $event->delete();
        
        return redirect()->route('events.index')->with('success', "Event '{$event->name}' deleted successfully.");
    }

    public function rsvp(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        if (!$event->allow_rsvp) {
            return back()->with('error', 'RSVP is not open for this event.');
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Check audience eligibility
        if ($event->audience !== 'all') {
            $isStaff   = $user->hasRole('teacher') || !empty($user->employee_id);
            $isStudent = $user->hasRole('student');
            $isParent  = $user->hasRole('parent');
            $allowed   = match($event->audience) {
                'staff'    => $isStaff,
                'students' => $isStudent,
                'parents'  => $isParent,
                default    => true,
            };

            if (!$allowed && !$user->hasAnyRole(['super_admin', 'admin', 'principal', 'vice_principal'])) {
                return back()->with('error', 'This event is designated specifically for ' . ucfirst($event->audience) . '.');
            }
        }

        $data = $request->validate([
            'status'      => 'required|in:attending,not_attending,maybe',
            'guest_count' => 'nullable|integer|min:0|max:20',
            'note'        => 'nullable|string|max:500',
        ]);
        $data['guest_count'] = (int) ($data['guest_count'] ?? 0);

        // Capacity check accounting for guest count
        if ($event->max_rsvp && in_array($data['status'], ['attending', 'maybe'])) {
            $currentTotal = (int) EventRsvp::where('event_id', $id)
                ->whereIn('status', ['attending', 'maybe'])
                ->where('user_id', '!=', $user->id)
                ->selectRaw('COALESCE(SUM(1 + guest_count), 0) as total')
                ->value('total');

            $requestedSpots = 1 + $data['guest_count'];
            if (($currentTotal + $requestedSpots) > $event->max_rsvp) {
                $available = max(0, $event->max_rsvp - $currentTotal);
                return back()->with('error', "Sorry, only {$available} attendee spot(s) remaining for this event (Capacity: {$event->max_rsvp}).");
            }
        }

        EventRsvp::updateOrCreate(
            ['event_id' => $id, 'user_id' => $user->id],
            $data
        );

        return back()->with('success', 'RSVP saved successfully.');
    }

    public function uploadPhotos(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        $this->authorizeEditEvent($event);

        $request->validate([
            'photos.*'   => 'required|image|max:4096',
            'captions.*' => 'nullable|string|max:255',
        ]);

        $uploadedCount = 0;
        foreach ($request->file('photos', []) as $k => $file) {
            $path = $file->store('events/photos', 'public');
            EventPhoto::create([
                'event_id'    => $id,
                'photo_path'  => $path,
                'caption'     => $request->captions[$k] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} photo(s) uploaded successfully.");
    }

    public function deletePhoto(int $photoId)
    {
        $photo = EventPhoto::with('event')->findOrFail($photoId);
        $user  = Auth::user();

        // Check if user is the uploader, or can edit/delete this event
        $canDelete = ($photo->uploaded_by === $user?->id)
            || $this->canDeleteEvent($user, $photo->event)
            || $this->canEditEvent($user, $photo->event);

        abort_unless($canDelete, 403, 'Unauthorized: You do not have permission to delete this photo.');

        if ($photo->photo_path) {
            Storage::disk('public')->delete($photo->photo_path);
        }
        $photo->delete();

        return back()->with('success', 'Photo deleted successfully.');
    }

    // ── Authorization & Security Helpers ─────────────────────────────────────

    public function canManageEvents($user): bool
    {
        if (!$user) return false;
        return $user->hasAnyRole(['super_admin', 'admin', 'principal', 'vice_principal', 'correspondent', 'correspondant'])
            || $user->can('create events')
            || $user->can('manage events');
    }

    public function canEditEvent($user, Event $event): bool
    {
        if (!$user) return false;
        if ($this->canManageEvents($user) || $user->can('edit events')) {
            return true;
        }
        // Event creator or assigned staff in-charge
        if ($event->created_by === $user->id || $event->organizer_id === $user->id) {
            return true;
        }
        if (!empty($user->employee_id) && $event->staff()->where('employee_id', $user->employee_id)->exists()) {
            return true;
        }
        return false;
    }

    public function canDeleteEvent($user, ?Event $event = null): bool
    {
        if (!$user) return false;
        return $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant'])
            || $user->can('delete events');
    }

    protected function authorizeManageEvents(): void
    {
        abort_unless($this->canManageEvents(Auth::user()), 403, 'Unauthorized: Only event managers and administrators can perform this action.');
    }

    protected function authorizeEditEvent(Event $event): void
    {
        abort_unless($this->canEditEvent(Auth::user(), $event), 403, 'Unauthorized: You do not have permission to modify this event.');
    }

    protected function authorizeDeleteEvent(Event $event): void
    {
        abort_unless($this->canDeleteEvent(Auth::user(), $event), 403, 'Unauthorized: Only administrator, principal, and correspondent can delete events.');
    }

    // ── HTML Sanitizer Helper ────────────────────────────────────────────────

    protected function sanitizeDescription(?string $html): ?string
    {
        if (empty($html)) return null;
        // Whitelist safe formatting tags
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a><span><h1><h2><h3><h4><h5><h6><div><blockquote><code><pre>';
        $clean = strip_tags($html, $allowed);
        // Strip javascript: and on* event handlers from whitelisted tags
        $clean = preg_replace('/(<[^>]+)(?:on\w+\s*=\s*["\'][^"\']*["\']|href\s*=\s*["\']javascript:[^"\']*["\'])/i', '$1', $clean);
        return trim($clean);
    }

    // ── Notification Helper ──────────────────────────────────────────────────

    private function notifyEventPublished(Event $event): void
    {
        try {
            $audience = $event->audience ?? 'all';
            $target   = match($audience) {
                'students' => 'student',
                'staff'    => 'staff',
                'parents'  => 'parent',
                default    => 'all',
            };

            Notice::create([
                'title'           => 'Event: ' . $event->name,
                'content'         => 'A new event has been published: <strong>' . e($event->name) . '</strong>'
                    . ($event->venue ? ' at ' . e($event->venue) : '')
                    . ' on ' . \Carbon\Carbon::parse($event->event_date)->format('d M Y') . '.',
                'notice_type'     => 'event',
                'target_audience' => $target,
                'publish_date'    => today(),
                'is_published'    => true,
                'school_id'       => $event->school_id ?? Auth::user()?->school_id,
                'created_by'      => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notice generation skipped for event: ' . $e->getMessage());
        }
    }
}
