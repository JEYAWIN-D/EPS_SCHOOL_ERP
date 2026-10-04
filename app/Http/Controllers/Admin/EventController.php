<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\EventPhoto;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $events = Event::with('createdBy')
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
            ->orderBy('event_date', 'desc')
            ->paginate(20)->withQueryString();

        $totalEvents    = Event::forUser($user)->count();
        $todayEvents    = Event::forUser($user)->whereDate('event_date', today())->count();
        $upcomingCount  = Event::forUser($user)->whereDate('event_date', '>', today())->where('is_published', true)->count();
        $completedCount = Event::forUser($user)->whereDate('event_date', '<', today())->count();
        $thisMonthCount = Event::forUser($user)->whereMonth('event_date', now()->month)->whereYear('event_date', now()->year)->count();

        $upcomingEvents = Event::forUser($user)->whereDate('event_date', '>=', today())
            ->where('is_published', true)
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return view('events.index', compact(
            'events', 'totalEvents', 'todayEvents',
            'upcomingCount', 'completedCount', 'thisMonthCount', 'upcomingEvents'
        ));
    }

    public function calendar(Request $request)
    {
        $user   = Auth::user();
        $month  = (int) ($request->month ?? now()->month);
        $year   = (int) ($request->year  ?? now()->year);
        $start  = Carbon::createFromDate($year, $month, 1);
        $end    = $start->copy()->endOfMonth();

        $events = Event::forUser($user)
            ->where('is_published', true)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('event_date')
            ->get()
            ->groupBy(fn($e) => $e->event_date->day);

        // For FullCalendar JSON
        if ($request->wantsJson()) {
            $allEvents = Event::forUser($user)
                ->where('is_published', true)
                ->whereBetween('event_date', [
                    Carbon::createFromDate($year, $month, 1)->subMonth()->toDateString(),
                    Carbon::createFromDate($year, $month, 1)->addMonths(2)->toDateString(),
                ])
                ->get();

            return response()->json($allEvents->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->name,
                'start' => $e->event_date->toDateString(),
                'color' => match($e->event_type) {
                    'academic'  => '#6366f1',
                    'cultural'  => '#ec4899',
                    'sports'    => '#10b981',
                    'holiday'   => '#f59e0b',
                    'meeting'   => '#3b82f6',
                    default     => '#64748b',
                },
                'url'   => route('events.show', $e->id),
            ]));
        }

        $days = range(1, $end->day);
        $startDow = $start->dayOfWeek; // 0=Sun

        return view('events.calendar', compact('events', 'days', 'startDow', 'month', 'year'));
    }

    public function show(int $id)
    {
        $event  = Event::with('rsvps.user', 'photos')->findOrFail($id);
        $myRsvp = Auth::check()
            ? EventRsvp::where('event_id', $id)->where('user_id', Auth::id())->first()
            : null;
        $rsvpCounts = $event->rsvps->countBy('status');

        return view('events.show', compact('event', 'myRsvp', 'rsvpCounts'));
    }

    public function create()
    {
        return view('events.form');
    }

    public function store(Request $request)
    {
        if ($request->filled('start_time')) {
            try {
                $request->merge(['start_time' => Carbon::parse($request->start_time)->format('H:i')]);
            } catch (\Exception $e) {}
        }
        if ($request->filled('end_time')) {
            try {
                $request->merge(['end_time' => Carbon::parse($request->end_time)->format('H:i')]);
            } catch (\Exception $e) {}
        }

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'event_type'  => 'required|in:academic,cultural,sports,holiday,meeting,other',
            'event_date'  => 'required|date',
            'start_time'  => 'nullable|string',
            'end_time'    => 'nullable|string',
            'venue'       => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'audience'    => 'required|in:all,students,staff,parents',
            'allow_rsvp'  => 'boolean',
            'max_rsvp'    => 'nullable|integer|min:1',
            'is_published'=> 'boolean',
            'banner_image'=> 'nullable|image|max:2048',
        ]);

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['start_time'] >= $data['end_time']) {
                return back()->withInput()->withErrors(['end_time' => 'The end time field must be after start time.']);
            }
        }

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('events/banners', 'public');
        }

        $data['created_by'] = Auth::id();
        $event = Event::create($data);

        if ($event->is_published) {
            $this->notifyEventPublished($event);
        }

        return redirect()->route('events.show', $event->id)->with('success', 'Event created.');
    }

    public function edit(int $id)
    {
        $event = Event::findOrFail($id);
        return view('events.form', compact('event'));
    }

    public function update(Request $request, int $id)
    {
        $event = Event::findOrFail($id);

        if ($request->filled('start_time')) {
            try {
                $request->merge(['start_time' => Carbon::parse($request->start_time)->format('H:i')]);
            } catch (\Exception $e) {}
        }
        if ($request->filled('end_time')) {
            try {
                $request->merge(['end_time' => Carbon::parse($request->end_time)->format('H:i')]);
            } catch (\Exception $e) {}
        }

        $data  = $request->validate([
            'name'        => 'required|string|max:200',
            'event_type'  => 'required|in:academic,cultural,sports,holiday,meeting,other',
            'event_date'  => 'required|date',
            'start_time'  => 'nullable|string',
            'end_time'    => 'nullable|string',
            'venue'       => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'audience'    => 'required|in:all,students,staff,parents',
            'allow_rsvp'  => 'boolean',
            'max_rsvp'    => 'nullable|integer|min:1',
            'is_published'=> 'boolean',
            'banner_image'=> 'nullable|image|max:2048',
        ]);

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['start_time'] >= $data['end_time']) {
                return back()->withInput()->withErrors(['end_time' => 'The end time field must be after start time.']);
            }
        }

        if ($request->hasFile('banner_image')) {
            if ($event->banner_image) Storage::disk('public')->delete($event->banner_image);
            $data['banner_image'] = $request->file('banner_image')->store('events/banners', 'public');
        }

        $wasPublished = $event->is_published;
        $event->update($data);

        if (!$wasPublished && $event->fresh()->is_published) {
            $this->notifyEventPublished($event->fresh());
        }

        return redirect()->route('events.show', $event->id)->with('success', 'Event updated.');
    }

    public function destroy(int $id)
    {
        $user = auth()->user();
        $event = Event::findOrFail($id);

        $canDelete = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);

        abort_unless($canDelete, 403, 'Unauthorized: Only administrator, principal, and correspondent can delete events.');

        if ($event->banner_image) Storage::disk('public')->delete($event->banner_image);
        $event->delete();
        
        return redirect()->route('events.index')->with('success', "Event '{$event->name}' deleted successfully.");
    }

    public function rsvp(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        if (!$event->allow_rsvp) abort(403, 'RSVP not open for this event.');

        $data = $request->validate([
            'status'      => 'required|in:attending,not_attending,maybe',
            'guest_count' => 'integer|min:0',
            'note'        => 'nullable|string',
        ]);

        if ($event->max_rsvp && in_array($data['status'], ['attending', 'maybe'])) {
            $attendingCount = EventRsvp::where('event_id', $id)
                ->whereIn('status', ['attending', 'maybe'])
                ->where('user_id', '!=', Auth::id())
                ->count();
            if ($attendingCount >= $event->max_rsvp) {
                return back()->with('error', 'Sorry, this event has reached its maximum capacity of ' . $event->max_rsvp . ' attendees.');
            }
        }

        EventRsvp::updateOrCreate(
            ['event_id' => $id, 'user_id' => Auth::id()],
            $data
        );

        return back()->with('success', 'RSVP saved.');
    }

    public function uploadPhotos(Request $request, int $id)
    {
        $event = Event::findOrFail($id);
        $request->validate(['photos.*' => 'required|image|max:4096', 'captions.*' => 'nullable|string']);

        foreach ($request->file('photos', []) as $k => $file) {
            $path = $file->store('events/photos', 'public');
            EventPhoto::create([
                'event_id'    => $id,
                'photo_path'  => $path,
                'caption'     => $request->captions[$k] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
        }

        return back()->with('success', count($request->file('photos', [])) . ' photos uploaded.');
    }

    public function deletePhoto(int $photoId)
    {
        $photo = EventPhoto::findOrFail($photoId);
        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();
        return back()->with('success', 'Photo deleted.');
    }

    private function notifyEventPublished(Event $event): void
    {
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
            'created_by'      => Auth::id(),
        ]);
    }
}
