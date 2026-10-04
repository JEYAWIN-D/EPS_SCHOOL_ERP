@extends('layouts.app')
@section('title', $event->name)
@section('content')
<div class="max-w-3xl space-y-6 pb-12">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="{{ route('events.index') }}" class="btn-sm btn-secondary">← Events</a>
    <div class="flex gap-2">
      @if($canEdit)
        <a href="{{ route('events.edit', $event->id) }}" class="btn-sm btn-secondary">Edit</a>
      @endif
      @if($canDelete)
      <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Permanently delete this event and all associated records?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn-sm btn-secondary text-rose-600">Delete</button>
      </form>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center gap-2 shadow-2xs">
      <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-2xs">
      <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Banner --}}
  @if($event->banner_image)
  <div class="w-full h-56 rounded-2xl overflow-hidden border border-slate-200 shadow-2xs">
    <img src="{{ Storage::url($event->banner_image) }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
  </div>
  @endif

  <div class="card space-y-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $event->name }}</h1>
        <div class="flex flex-wrap gap-2 mt-2">
          @php $typeColors=['academic'=>'badge-blue','cultural'=>'badge-pink','sports'=>'badge-green','holiday'=>'badge-amber','meeting'=>'badge-blue','other'=>'badge-slate']; @endphp
          <span class="{{ $typeColors[$event->event_type] ?? 'badge-slate' }}">{{ ucfirst($event->event_type) }}</span>
          @if($event->is_published) <span class="badge-green">Published</span> @else <span class="badge-slate">Draft</span> @endif
          <span class="badge-slate">{{ ucfirst($event->audience) }}</span>
          @if($event->is_assigned)
            <span class="badge-green">✓ Assigned ({{ $event->staff->count() }})</span>
          @else
            <span class="badge-amber">⏳ Pending Assignment</span>
          @endif
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4 text-sm">
      <div>
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Date</p>
        <p class="font-semibold text-slate-700">{{ $event->event_date->format('l, d F Y') }}</p>
      </div>
      <div>
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Time</p>
        <p class="font-semibold text-slate-700">
          @if($event->start_time) {{ substr($event->start_time,0,5) }}@if($event->end_time) – {{ substr($event->end_time,0,5) }}@endif @else All Day @endif
        </p>
      </div>
      @if($event->venue)
      <div class="col-span-2">
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Venue</p>
        <p class="font-semibold text-slate-700">{{ $event->venue }}</p>
      </div>
      @endif
    </div>

    {{-- Event In-Charge & Assignment Details --}}
    <div class="pt-3 border-t border-slate-100">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Faculty &amp; Staff Assignment</h3>
        @if($canEdit)
          <a href="{{ route('events.edit', $event->id) }}#assignment-section" class="text-xs font-semibold text-maroon-700 hover:text-maroon-900">
            {{ $event->is_assigned ? 'Edit Assignment' : '+ Assign Staff Now' }}
          </a>
        @endif
      </div>
      @if($event->staff->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          @foreach($event->staff as $st)
            <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between text-xs">
              <div>
                <p class="font-bold text-slate-800">{{ $st->employee?->full_name ?? 'Staff Member' }}</p>
                <p class="text-[11px] text-slate-500">{{ $st->duties ?: 'Assigned staff' }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white border border-slate-200 text-slate-700">
                {{ match($st->role) { 'in_charge' => 'Lead Convener', 'coordinator' => 'Coordinator', default => 'Support' } }}
              </span>
            </div>
          @endforeach
        </div>
      @else
        <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200/80 text-xs text-amber-800 flex items-center justify-between">
          <span>This event was saved with <strong>Pending Assignment</strong>. No staff or organizers are designated yet.</span>
          @if($canEdit)
            <a href="{{ route('events.edit', $event->id) }}#assignment-section" class="btn-xs btn-primary font-bold">Assign Now</a>
          @endif
        </div>
      @endif
    </div>

    @if($event->description)
    <div class="prose text-sm text-slate-600 max-w-none pt-2 border-t border-slate-100">{!! $event->description !!}</div>
    @endif

  </div>

  {{-- RSVP --}}
  @if($event->allow_rsvp)
  <div class="card space-y-3">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
        <span>RSVP & Attendance Confirmation</span>
        @if($rsvpCounts->isNotEmpty())
          <span class="text-xs font-normal text-slate-400">
            · {{ $rsvpCounts->get('attending',0) }} attending
            · {{ $rsvpCounts->get('maybe',0) }} maybe
            · {{ $rsvpCounts->get('not_attending',0) }} not attending
          </span>
        @endif
      </h3>
      @if($event->max_rsvp)
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
          Capacity: {{ $event->max_rsvp }} spots
        </span>
      @endif
    </div>

    @auth
    <form method="POST" action="{{ route('events.rsvp', $event->id) }}" class="flex flex-wrap gap-3 items-end pt-2">
      @csrf
      <div>
        <label class="label">Your Response</label>
        <select name="status" class="select text-xs">
          <option value="attending" @selected($myRsvp?->status==='attending')>✅ Attending</option>
          <option value="maybe" @selected($myRsvp?->status==='maybe')>🤔 Maybe</option>
          <option value="not_attending" @selected($myRsvp?->status==='not_attending')>❌ Not Attending</option>
        </select>
      </div>
      <div>
        <label class="label">Guests</label>
        <input type="number" name="guest_count" value="{{ $myRsvp?->guest_count ?? 0 }}" class="input w-24 text-xs" min="0" max="20">
      </div>
      <div class="flex-1 min-w-48">
        <label class="label">Note (optional)</label>
        <input type="text" name="note" value="{{ $myRsvp?->note ?? '' }}" class="input text-xs" placeholder="Any message or dietary preference…">
      </div>
      <button type="submit" class="btn-primary btn-sm">Save RSVP</button>
    </form>
    @endauth
  </div>
  @endif

  {{-- Photo Gallery --}}
  <div class="card space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-slate-800 text-sm">Photo Gallery ({{ $event->photos->count() }})</h3>
    </div>

    @if($event->photos->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      @foreach($event->photos as $photo)
        @php
          $canDeletePhoto = $canDelete || $canEdit || ($photo->uploaded_by === auth()->id());
        @endphp
        <div class="relative group rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
          <img src="{{ Storage::url($photo->photo_path) }}" alt="{{ $photo->caption }}" class="w-full h-32 object-cover">
          @if($photo->caption)
            <p class="text-xs text-slate-600 p-1.5 truncate bg-white/90 border-t border-slate-100">{{ $photo->caption }}</p>
          @endif
          @if($canDeletePhoto)
            <form method="POST" action="{{ route('events.photos.delete', $photo->id) }}" class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition">
              @csrf @method('DELETE')
              <button type="submit" onclick="return confirm('Delete this photo?')" class="bg-white/90 hover:bg-white text-rose-600 rounded-lg p-1.5 shadow-sm text-xs" title="Delete Photo">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          @endif
        </div>
      @endforeach
    </div>
    @endif

    @if($canEdit)
      <form method="POST" action="{{ route('events.photos.upload', $event->id) }}" enctype="multipart/form-data" class="space-y-2 pt-2 border-t border-slate-100">
        @csrf
        <label class="label">Upload Event Photos</label>
        <div class="flex items-center gap-2">
          <input type="file" name="photos[]" multiple accept="image/*" class="input flex-1 text-xs">
          <button type="submit" class="btn-sm btn-secondary">Upload</button>
        </div>
      </form>
    @endif
  </div>
</div>
@endsection
