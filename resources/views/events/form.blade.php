@extends('layouts.app')
@section('title', isset($event) ? 'Edit Event — ' . $event->name : 'Create New Event')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="eventFormHandler()">

  {{-- Header with Breadcrumbs & Action --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
        <a href="{{ route('events.index') }}" class="hover:text-maroon-700 transition">Events & Calendar</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">{{ isset($event) ? 'Edit Event' : 'Create Event' }}</span>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-maroon-800 to-amber-600 flex items-center justify-center text-white shadow-md shadow-maroon-900/10">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            {{ isset($event) ? 'Edit Event' : 'Create New Event' }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-500">
            Schedule school functions, publish circular notices, and configure audience targeting.
          </p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Events
      </a>
    </div>
  </div>

  {{-- Validation Errors Display --}}
  @if($errors->any())
    <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm shadow-xs animate-shake">
      <div class="flex items-center gap-2 font-bold mb-2">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Please resolve the following errors:</span>
      </div>
      <ul class="list-disc list-inside space-y-1 pl-4 text-xs">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Form Card --}}
  <form method="POST"
        action="{{ isset($event) ? route('events.update', $event->id) : route('events.store') }}"
        enctype="multipart/form-data"
        @submit="syncDescription"
        class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-8">
    @csrf
    @if(isset($event)) @method('PUT') @endif

    {{-- Section 1: Basic Information --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-maroon-50 text-maroon-700 flex items-center justify-center text-xs font-bold">1</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Event Core Details</h3>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Event Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" name="name"
                   value="{{ old('name', $event->name ?? '') }}"
                   required
                   placeholder="e.g., Annual Science Fair & Robotics Expo 2026"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-maroon-600 focus:border-maroon-600 focus:outline-hidden transition shadow-xs">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Event Type <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <select name="event_type" required
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 bg-white focus:ring-2 focus:ring-maroon-600 focus:border-maroon-600 focus:outline-hidden appearance-none shadow-xs transition">
                @foreach(['academic' => 'Academic & Competitions', 'cultural' => 'Cultural & Arts', 'sports' => 'Sports & Athletics', 'holiday' => 'Holiday & Celebrations', 'meeting' => 'Assembly & Meetings', 'other' => 'Other Activity'] as $k => $label)
                  <option value="{{ $k }}" @selected(old('event_type', $event->event_type ?? 'academic') === $k)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Audience Targeting <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <select name="audience" required x-model="audience"
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 bg-white focus:ring-2 focus:ring-maroon-600 focus:border-maroon-600 focus:outline-hidden appearance-none shadow-xs transition">
                <option value="all">🌍 All School Community (Everyone)</option>
                <option value="students">🎓 Students Only</option>
                <option value="staff">👨‍🏫 Staff & Faculty Only</option>
                <option value="parents">👨‍👩‍👧 Parents Only</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">
              Controls who can view this event on their role dashboard and calendar.
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Section 2: Date, Time & Venue --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-xs font-bold">2</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Date, Time & Venue</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Event Date <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="date" name="event_date"
                   value="{{ old('event_date', isset($event) && $event->event_date ? $event->event_date->toDateString() : '') }}"
                   required
                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs transition">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Start Time
          </label>
          <input type="time" name="start_time"
                 value="{{ old('start_time', $event->start_time ?? '') }}"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs transition">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            End Time
          </label>
          <input type="time" name="end_time"
                 value="{{ old('end_time', $event->end_time ?? '') }}"
                 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs transition">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
          Venue / Location
        </label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </span>
          <input type="text" name="venue"
                 value="{{ old('venue', $event->venue ?? '') }}"
                 placeholder="e.g., Main Auditorium / Athletics Ground / Google Meet URL"
                 class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs transition">
        </div>
      </div>
    </div>

    {{-- Section 3: Description & Agenda --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs font-bold">3</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Description & Details</h3>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
          Event Overview & Agenda
        </label>

        {{-- Rich Toolbar --}}
        <div class="flex items-center gap-1 p-1.5 bg-slate-50 border border-slate-200 border-b-0 rounded-t-xl text-slate-600 text-xs">
          <button type="button" @click="fmt('bold')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 hover:shadow-xs transition font-bold" title="Bold">B</button>
          <button type="button" @click="fmt('italic')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 hover:shadow-xs transition italic" title="Italic">I</button>
          <button type="button" @click="fmt('underline')" class="px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 hover:shadow-xs transition underline" title="Underline">U</button>
          <div class="w-px h-4 bg-slate-300 mx-1"></div>
          <button type="button" @click="fmt('insertUnorderedList')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 hover:shadow-xs transition" title="Bullet List">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            List
          </button>
          <button type="button" @click="fmt('insertOrderedList')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md hover:bg-white hover:text-slate-900 hover:shadow-xs transition" title="Numbered List">
            1. List
          </button>
          <div class="w-px h-4 bg-slate-300 mx-1"></div>
          <button type="button" @click="fmt('removeFormat')" class="px-2 py-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-white transition" title="Clear Formatting">
            Clear
          </button>
        </div>

        {{-- Visual Editor Box --}}
        <div id="desc-editor"
             contenteditable="true"
             class="w-full min-h-[130px] p-4 bg-white border border-slate-200 rounded-b-xl text-sm text-slate-800 leading-relaxed focus:ring-2 focus:ring-maroon-600 focus:outline-hidden shadow-xs transition"
             style="white-space: pre-wrap;">{!! old('description', $event->description ?? '') !!}</div>

        <textarea name="description" id="desc-hidden" class="hidden">{{ old('description', $event->description ?? '') }}</textarea>
      </div>
    </div>

    {{-- Section 4: Banner Image Upload --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-xs font-bold">4</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Event Banner Image</h3>
      </div>

      <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 hover:bg-slate-50 transition">
        <div class="flex flex-col sm:flex-row items-center gap-4">
          @if(isset($event) && $event->banner_image)
            <div class="relative w-28 h-20 rounded-xl overflow-hidden border border-slate-200 flex-shrink-0 shadow-xs">
              <img src="{{ Storage::url($event->banner_image) }}" alt="Banner" class="w-full h-full object-cover">
            </div>
          @else
            <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
          @endif

          <div class="flex-1 text-center sm:text-left">
            <input type="file" name="banner_image" id="banner_image" accept="image/*"
                   class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-maroon-50 file:text-maroon-700 hover:file:bg-maroon-100 cursor-pointer">
            <p class="text-[11px] text-slate-500 mt-1">Recommended size: 1200x500 px. Maximum file size: 2MB (PNG, JPG, WebP).</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Section 5: Settings, Publishing & RSVP --}}
    <div class="space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
        <span class="w-6 h-6 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center text-xs font-bold">5</span>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Publishing & RSVP Settings</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Card: Publish Immediately --}}
        <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 cursor-pointer transition select-none">
          <input type="checkbox" name="is_published" value="1"
                 @checked(old('is_published', $event->is_published ?? true))
                 class="w-4 h-4 mt-0.5 rounded-md border-slate-300 text-maroon-700 focus:ring-maroon-600">
          <div>
            <span class="block text-xs font-bold text-slate-900">Publish Immediately</span>
            <span class="block text-[11px] text-slate-500 mt-0.5">
              Make event visible to target audience right away. Uncheck to save as draft.
            </span>
          </div>
        </label>

        {{-- Card: Allow RSVP --}}
        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
          <label class="flex items-start gap-3 cursor-pointer select-none">
            <input type="checkbox" name="allow_rsvp" value="1" x-model="allowRsvp"
                   @checked(old('allow_rsvp', $event->allow_rsvp ?? false))
                   class="w-4 h-4 mt-0.5 rounded-md border-slate-300 text-maroon-700 focus:ring-maroon-600">
            <div>
              <span class="block text-xs font-bold text-slate-900">Allow Attendees to RSVP</span>
              <span class="block text-[11px] text-slate-500 mt-0.5">
                Targeted members can confirm attendance with Going / Not Going.
              </span>
            </div>
          </label>

          <div x-show="allowRsvp" x-transition class="pt-2 border-t border-slate-200/60 flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-700 whitespace-nowrap">Attendee Limit:</label>
            <input type="number" name="max_rsvp"
                   value="{{ old('max_rsvp', $event->max_rsvp ?? '') }}"
                   min="1"
                   placeholder="Unlimited"
                   class="w-28 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-maroon-600 focus:outline-hidden">
          </div>
        </div>
      </div>
    </div>

    {{-- Bottom Action Buttons --}}
    <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
      <a href="{{ route('events.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold text-center transition">
        Discard Changes
      </a>

      <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-900 hover:from-maroon-900 hover:to-black text-white text-xs font-bold shadow-md shadow-maroon-900/20 hover:shadow-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ isset($event) ? 'Update Event Details' : 'Create & Save Event' }}</span>
      </button>
    </div>

  </form>
</div>

<script>
function eventFormHandler() {
  return {
    allowRsvp: @json(old('allow_rsvp', (bool)($event->allow_rsvp ?? false))),
    audience: @json(old('audience', $event->audience ?? 'all')),
    fmt(cmd) {
      document.execCommand(cmd, false, null);
      document.getElementById('desc-editor').focus();
    },
    syncDescription() {
      document.getElementById('desc-hidden').value = document.getElementById('desc-editor').innerHTML;
    }
  }
}
</script>
@endsection
