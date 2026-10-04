@extends('layouts.app')
@section('title', 'Official Circulars & Orders')

@section('content')
<div class="space-y-6 pb-12">

  {{-- Top Navigation Switcher: Events vs Circulars --}}
  <div class="flex items-center gap-3 p-1.5 bg-slate-200/70 rounded-2xl w-fit border border-slate-300/60 shadow-2xs">
    <a href="{{ route('events.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      School Events
    </a>
    <a href="{{ route('circulars.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-white text-maroon-900 shadow-xs transition flex items-center gap-2 border border-slate-200/80">
      <svg class="w-4 h-4 text-maroon-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Circulars &amp; Official Orders
    </a>
  </div>

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-maroon-800 to-amber-600 flex items-center justify-center text-white shadow-md shadow-maroon-900/10">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
      </div>
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Circulars &amp; Official Orders</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
          Official directives, administrative orders, government orders (G.O.), and compliance circulars.
        </p>
      </div>
    </div>

    @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']))
    <div class="flex items-center gap-2">
      <a href="{{ route('circulars.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-900 hover:from-maroon-900 hover:to-black text-white text-xs font-bold shadow-md shadow-maroon-900/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Issue Circular / Order
      </a>
    </div>
    @endif
  </div>

  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm shadow-xs flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  {{-- KPI Cards --}}
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <a href="{{ route('circulars.index') }}" class="card hover:border-slate-300 transition group">
      <div class="stat-icon bg-gradient-to-br from-blue-500 to-indigo-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-slate-800">{{ $totalCirculars }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Total Records</p>
    </a>

    <a href="{{ route('circulars.index', ['category' => 'order']) }}" class="card hover:border-amber-300 transition group {{ request('category') === 'order' ? 'ring-2 ring-amber-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-amber-500 to-orange-500 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-amber-600">{{ $ordersCount }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Official Orders</p>
    </a>

    <a href="{{ route('circulars.index', ['category' => 'urgent']) }}" class="card hover:border-rose-300 transition group {{ request('category') === 'urgent' ? 'ring-2 ring-rose-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-rose-500 to-red-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-rose-600">{{ $urgentCount }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Urgent Directives</p>
    </a>

    <a href="{{ route('circulars.index', ['category' => 'pinned']) }}" class="card hover:border-purple-300 transition group {{ request('category') === 'pinned' ? 'ring-2 ring-purple-500' : '' }}">
      <div class="stat-icon bg-gradient-to-br from-violet-500 to-purple-600 mb-3 group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
      </div>
      <p class="stat-number text-2xl font-black text-purple-700">{{ $pinnedCount }}</p>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-0.5">Pinned to Top</p>
    </a>
  </div>

  {{-- Category Filter Tabs --}}
  <div class="flex items-center justify-between flex-wrap gap-3 pt-2 border-b border-slate-200">
    <div class="flex items-center gap-2 flex-wrap -mb-px">
      @php
        $currCat = request('category', '');
        $tabs = [
          ''          => ['label' => 'All Directives',  'count' => $totalCirculars],
          'circular'  => ['label' => 'Circulars',       'count' => null],
          'order'     => ['label' => 'Official Orders', 'count' => $ordersCount],
          'urgent'    => ['label' => 'Urgent Priority', 'count' => $urgentCount],
          'pinned'    => ['label' => 'Pinned Directives', 'count' => $pinnedCount],
        ];
      @endphp

      @foreach($tabs as $val => $tab)
        @php $isActive = ($currCat === $val); @endphp
        <a href="{{ request()->fullUrlWithQuery(['category' => $val ?: null]) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition select-none {{ $isActive ? 'border-maroon-700 text-maroon-800 bg-maroon-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
          <span>{{ $tab['label'] }}</span>
          @if($tab['count'] !== null)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $isActive ? 'bg-maroon-700 text-white' : 'bg-slate-100 text-slate-600' }}">
              {{ $tab['count'] }}
            </span>
          @endif
        </a>
      @endforeach
    </div>
  </div>

  {{-- Filters Bar --}}
  <form method="GET" action="{{ route('circulars.index') }}" class="card flex flex-wrap gap-4 items-end bg-slate-50/50 p-4 rounded-xl border border-slate-200">
    @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif

    <div class="flex-1 min-w-[220px]">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Search Circulars &amp; Orders</label>
      <input type="text" name="search" value="{{ request('search') }}" class="input w-full" placeholder="Search by reference no, subject, signatory…">
    </div>

    <div class="w-48">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Target Audience</label>
      <select name="audience" class="select w-full">
        <option value="">All Audiences</option>
        <option value="all" @selected(request('audience')==='all')>🌍 All Community</option>
        <option value="staff" @selected(request('audience')==='staff')>👨‍🏫 Staff & Faculty</option>
        <option value="students" @selected(request('audience')==='students')>🎓 Students Only</option>
        <option value="parents" @selected(request('audience')==='parents')>👨‍👩‍👧 Parents Only</option>
      </select>
    </div>

    <div class="w-36">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Date</label>
      <input type="date" name="from" value="{{ request('from') }}" class="input w-full">
    </div>

    <div class="w-36">
      <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">To Date</label>
      <input type="date" name="to" value="{{ request('to') }}" class="input w-full">
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="btn-primary btn-sm px-4">Filter</button>
      <a href="{{ route('circulars.index') }}" class="btn-sm btn-secondary">Reset</a>
    </div>
  </form>

  {{-- Circulars & Orders Grid --}}
  <div class="space-y-4">
    @forelse($circulars as $c)
      @php
        $user = auth()->user();
        $canDelete = $user && $user->hasAnyRole(['super_admin', 'admin', 'principal', 'correspondent', 'correspondant']);
        $myAck = $c->acknowledgements->where('user_id', $user?->id)->first();
      @endphp

      <div class="card hover:shadow-md transition border {{ $c->is_pinned ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/90 bg-white' }} rounded-2xl p-5 space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2 flex-wrap text-xs">
              {{-- Reference No --}}
              <span class="px-2.5 py-0.5 rounded-lg font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200 shadow-2xs">
                {{ $c->reference_no ?? 'EPS/CIR/' . $c->id }}
              </span>

              {{-- Type badge --}}
              <span class="px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] tracking-wide {{ in_array($c->notice_type, ['order','directive']) ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                {{ $c->notice_type === 'order' ? 'Official Order' : ucfirst($c->notice_type) }}
              </span>

              {{-- Priority badge --}}
              @if($c->priority === 'urgent')
                <span class="px-2 py-0.5 rounded-full font-extrabold text-[10px] uppercase bg-rose-100 text-rose-700 border border-rose-200 animate-pulse">
                  Urgent
                </span>
              @elseif($c->priority === 'high')
                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase bg-amber-100 text-amber-800 border border-amber-200">
                  Important
                </span>
              @endif

              {{-- Audience badge --}}
              <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] bg-slate-50 text-slate-600 border border-slate-200">
                Audience: {{ ucfirst($c->target_audience) }}
              </span>

              @if($c->is_pinned)
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                  ★ Pinned
                </span>
              @endif
            </div>

            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug">
              <a href="{{ route('circulars.show', $c->id) }}" class="hover:text-maroon-700 transition">
                {{ $c->title }}
              </a>
            </h3>

            <p class="text-xs text-slate-500 flex items-center gap-3 flex-wrap">
              <span><strong>Issued:</strong> {{ $c->publish_date ? $c->publish_date->format('d M Y') : $c->created_at->format('d M Y') }}</span>
              @if($c->issuing_authority)
                <span>&bull;</span>
                <span><strong>Authority:</strong> {{ $c->issuing_authority }}</span>
              @endif
              @if($c->signed_by_name)
                <span>&bull;</span>
                <span><strong>Signatory:</strong> {{ $c->signed_by_name }} ({{ $c->signatory_designation ?? 'Authorized' }})</span>
              @endif
            </p>
          </div>

          {{-- Action Buttons --}}
          <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('circulars.show', $c->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition inline-flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              View
            </a>

            <button type="button"
                    onclick="shareCircular('{{ addslashes($c->title) }}', '{{ addslashes($c->reference_no ?? '') }}', '{{ route('circulars.show', $c->id) }}')"
                    class="px-2.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-semibold transition inline-flex items-center gap-1"
                    title="Share via Link / WhatsApp">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Share
            </button>

            @if($canDelete)
              <a href="{{ route('circulars.edit', $c->id) }}" class="px-2.5 py-1.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition" title="Edit">
                Edit
              </a>

              <form method="POST" action="{{ route('circulars.destroy', $c->id) }}" onsubmit="return confirm('Permanently delete circular [{{ addslashes($c->reference_no ?? $c->title) }}]? This cannot be undone.')" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold transition inline-flex items-center gap-1" title="Delete">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  Delete
                </button>
              </form>
            @endif
          </div>

        </div>

        {{-- Content Snippet --}}
        <div class="text-xs text-slate-600 line-clamp-2 leading-relaxed pl-1 border-l-2 border-slate-200">
          {{ strip_tags($c->content) }}
        </div>

        {{-- Footer Details: Attachments & Acknowledgement --}}
        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
          <div class="flex items-center gap-3">
            @if($c->attachment)
              <a href="{{ Storage::url($c->attachment) }}" target="_blank" class="inline-flex items-center gap-1.5 font-bold text-indigo-600 hover:text-indigo-800 transition">
                <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <span>Download Attachment (PDF/DOC)</span>
              </a>
            @else
              <span class="text-slate-400">No external attachment</span>
            @endif

            @if($c->requires_acknowledgement)
              <span class="text-slate-300">&bull;</span>
              <span class="text-slate-500">
                <strong>{{ $c->acknowledgements_count }}</strong> acknowledged
              </span>
            @endif
          </div>

          {{-- Quick Acknowledge Button if required --}}
          @if($c->requires_acknowledgement)
            @if($myAck)
              <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Acknowledged on {{ $myAck->acknowledged_at->format('d M, H:i') }}
              </span>
            @else
              <form method="POST" action="{{ route('circulars.acknowledge', $c->id) }}" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-bold text-xs shadow-xs transition inline-flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Acknowledge Receipt
                </button>
              </form>
            @endif
          @endif
        </div>
      </div>
    @empty
      <div class="card text-center py-16 bg-white rounded-2xl border border-slate-200">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="font-bold text-slate-700">No circulars or official orders found.</p>
        <p class="text-xs text-slate-400 mt-1">Check back later or adjust your filters.</p>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  <div class="pt-4">
    {{ $circulars->withQueryString()->links() }}
  </div>
</div>

<script>
function shareCircular(title, ref, url) {
  const shareText = `*OFFICIAL CIRCULAR - ERODE PUBLIC SCHOOL*\n*Ref*: ${ref}\n*Subject*: ${title}\n\nRead full circular details at:\n${url}`;
  
  if (navigator.share) {
    navigator.share({
      title: title,
      text: shareText,
      url: url,
    }).catch(() => copyToClipboard(url));
  } else {
    // Open WhatsApp Web share or copy
    const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`;
    window.open(waUrl, '_blank');
  }
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    alert('Circular link copied to clipboard!');
  });
}
</script>
@endsection
