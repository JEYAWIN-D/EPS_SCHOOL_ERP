@extends('layouts.app')
@section('title', $event->name)
@section('content')
<div class="max-w-3xl space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="{{ route('events.index') }}" class="btn-sm btn-secondary">← Events</a>
    <div class="flex gap-2">
      <a href="{{ route('events.edit', $event->id) }}" class="btn-sm btn-secondary">Edit</a>
      @if(auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']))
      <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Delete this event?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn-sm btn-secondary text-rose-600">Delete</button>
      </form>
      @endif
    </div>
  </div>

  @if(session('success')) <div class="alert-success">{{ session('success') }}</div> @endif

  {{-- Banner --}}
  @if($event->banner_image)
  <img src="{{ Storage::url($event->banner_image) }}" alt="{{ $event->name }}" class="w-full h-56 object-cover rounded-2xl">
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
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4 text-sm">
      <div>
        <p class="text-slate-400 text-xs">Date</p>
        <p class="font-semibold text-slate-700">{{ $event->event_date->format('l, d F Y') }}</p>
      </div>
      <div>
        <p class="text-slate-400 text-xs">Time</p>
        <p class="font-semibold text-slate-700">
          @if($event->start_time) {{ substr($event->start_time,0,5) }}@if($event->end_time) – {{ substr($event->end_time,0,5) }}@endif @else All Day @endif
        </p>
      </div>
      @if($event->venue)
      <div class="col-span-2">
        <p class="text-slate-400 text-xs">Venue</p>
        <p class="font-semibold text-slate-700">{{ $event->venue }}</p>
      </div>
      @endif
    </div>

    @if($event->description)
    <div class="prose text-sm text-slate-600 max-w-none">{{ $event->description }}</div>
    @endif
  </div>

  {{-- RSVP --}}
  @if($event->allow_rsvp)
  <div class="card">
    <h3 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
      RSVP
      @if($rsvpCounts->isNotEmpty())
        <span class="text-xs font-normal text-slate-400">
          {{ $rsvpCounts->get('attending',0) }} attending
          · {{ $rsvpCounts->get('maybe',0) }} maybe
          · {{ $rsvpCounts->get('not_attending',0) }} not attending
        </span>
      @endif
    </h3>
    @auth
    <form method="POST" action="{{ route('events.rsvp', $event->id) }}" class="flex flex-wrap gap-3 items-end">
      @csrf
      <div>
        <label class="label">Your Response</label>
        <select name="status" class="select">
          <option value="attending" @selected($myRsvp?->status==='attending')>✅ Attending</option>
          <option value="maybe" @selected($myRsvp?->status==='maybe')>🤔 Maybe</option>
          <option value="not_attending" @selected($myRsvp?->status==='not_attending')>❌ Not Attending</option>
        </select>
      </div>
      <div>
        <label class="label">Guests</label>
        <input type="number" name="guest_count" value="{{ $myRsvp?->guest_count ?? 0 }}" class="input w-24" min="0">
      </div>
      <div class="flex-1 min-w-48">
        <label class="label">Note (optional)</label>
        <input type="text" name="note" value="{{ $myRsvp?->note ?? '' }}" class="input" placeholder="Any message or dietary preference…">
      </div>
      <button type="submit" class="btn-primary btn-sm">Save RSVP</button>
    </form>
    @endauth
  </div>
  @endif

  {{-- Photo Gallery --}}
  <div class="card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-slate-700">Photo Gallery ({{ $event->photos->count() }})</h3>
    </div>

    @if($event->photos->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
      @foreach($event->photos as $photo)
      <div class="relative group">
        <img src="{{ Storage::url($photo->photo_path) }}" alt="{{ $photo->caption }}" class="w-full h-32 object-cover rounded-xl">
        @if($photo->caption)
        <p class="text-xs text-slate-400 mt-1 truncate">{{ $photo->caption }}</p>
        @endif
        <form method="POST" action="{{ route('events.photos.delete', $photo->id) }}" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100">
          @csrf @method('DELETE')
          <button type="submit" class="bg-white/90 rounded-lg px-2 py-1 text-xs text-rose-600 font-medium hover:bg-white">✕</button>
        </form>
      </div>
      @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('events.photos.upload', $event->id) }}" enctype="multipart/form-data" class="space-y-2">
      @csrf
      <label class="label">Upload Photos</label>
      <input type="file" name="photos[]" multiple accept="image/*" class="input">
      <button type="submit" class="btn-sm btn-secondary">Upload</button>
    </form>
  </div>
</div>
@endsection
