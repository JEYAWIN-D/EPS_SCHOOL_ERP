@extends('layouts.admin')

@section('title', $notice->title . ' — Notice Details')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.notices.index') }}" class="hover:text-indigo-600">Notice Board</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Details</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="noticeShow()">

  {{-- Top Navigation & Actions --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('activities.notices.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
      &larr; Back to Notice Board
    </a>

    <div class="flex items-center gap-2">
      @if($notice->status === 'pending_approval')
        @canany(['manage notices', 'approve notices'])
          <button @click="approveNotice('approve')" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
            Approve & Publish
          </button>
          <button @click="approveNotice('reject')" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition border border-rose-200">
            Reject
          </button>
        @endcanany
      @endif

      @if($notice->status === 'draft')
        <button @click="publishNotice()" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition">
          Publish Now
        </button>
      @endif
    </div>
  </div>

  {{-- Main Notice Card --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-6 sm:p-8">
      <div class="flex flex-wrap items-center gap-2.5 mb-3">
        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider
          {{ $notice->priority === 'urgent' ? 'bg-rose-100 text-rose-800 border border-rose-200' : ($notice->priority === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
          {{ $notice->priority }} Priority
        </span>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 capitalize">
          Category: {{ $notice->notice_type }}
        </span>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
          Audience: {{ ucfirst($notice->target_audience) }}
        </span>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold
          {{ $notice->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
          Status: {{ ucfirst(str_replace('_', ' ', $notice->status)) }}
        </span>
      </div>

      <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
        {{ $notice->title }}
      </h1>

      <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">
        <span>Published: <strong class="text-slate-700">{{ $notice->publish_date->format('d M Y') }}</strong></span>
        @if($notice->expiry_date)
          <span>&bull;</span>
          <span>Expires: <strong class="text-slate-700">{{ $notice->expiry_date->format('d M Y') }}</strong></span>
        @endif
        <span>&bull;</span>
        <span>Author: <strong class="text-slate-700">{{ $notice->createdBy?->name ?? 'System' }}</strong></span>
      </div>

      <div class="mt-6 prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-line">
        {{ $notice->content }}
      </div>
    </div>

    {{-- Acknowledgement Section for Current User --}}
    @if($notice->requires_acknowledgement)
      <div class="px-6 py-5 bg-indigo-50/60 border-t border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h4 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">Formal Notice Acknowledgement</h4>
          @if($myAcknowledgement)
            <p class="text-xs text-emerald-700 font-semibold mt-0.5 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <span>Acknowledged by you on {{ $myAcknowledgement->acknowledged_at->format('d M Y, h:i A') }}</span>
            </p>
          @else
            <p class="text-xs text-indigo-700 mt-0.5">Please confirm that you have read and understood the details of this circular.</p>
          @endif
        </div>

        @if(!$myAcknowledgement)
          <button @click="acknowledgeNotice()" :disabled="ackLoading" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition disabled:opacity-50">
            <span x-show="!ackLoading">I Acknowledge & Understand</span>
            <span x-show="ackLoading">Recording...</span>
          </button>
        @endif
      </div>
    @endif
  </div>

  {{-- Acknowledgement Tracking Table (Admins / Author) --}}
  @if($notice->requires_acknowledgement)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Acknowledgement Compliance Report</h3>
          <p class="text-xs text-slate-400">Total recorded acknowledgements: {{ $notice->acknowledgements->count() }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200/70 text-slate-500 uppercase font-semibold text-[10px] tracking-wider">
              <th class="py-3 px-4">User</th>
              <th class="py-3 px-4">Acknowledged At</th>
              <th class="py-3 px-4">Feedback / Note</th>
              <th class="py-3 px-4">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($notice->acknowledgements as $ack)
              <tr class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">{{ $ack->user?->name ?? 'User #' . $ack->user_id }}</td>
                <td class="py-3 px-4 text-slate-600">{{ $ack->acknowledged_at->format('d M Y, h:i A') }}</td>
                <td class="py-3 px-4 text-slate-500">{{ $ack->feedback_note ?? '—' }}</td>
                <td class="py-3 px-4 font-mono text-[11px] text-slate-400">{{ $ack->ip_address ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="py-6 text-center text-slate-400">No acknowledgements recorded yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

</div>

<script>
function noticeShow() {
  return {
    ackLoading: false,
    async acknowledgeNotice() {
      this.ackLoading = true;
      try {
        const res = await fetch('{{ route('api.notices.acknowledge', $notice->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ feedback_note: 'Read and acknowledged.' }),
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.reload();
        } else {
          alert(data.message || 'Error recording acknowledgement');
        }
      } catch (err) {
        alert('Failed: ' + err.message);
      } finally {
        this.ackLoading = false;
      }
    },
    async approveNotice(action) {
      const reason = action === 'reject' ? prompt('Reason for rejection:') : null;
      if (action === 'reject' && !reason) return;

      try {
        const res = await fetch('{{ route('api.notices.approve', $notice->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ action: action, reason: reason }),
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.reload();
        } else {
          alert(data.message || 'Error processing request');
        }
      } catch (err) {
        alert('Failed: ' + err.message);
      }
    },
    async publishNotice() {
      try {
        const res = await fetch('{{ route('api.notices.publish', $notice->id) }}', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.reload();
        }
      } catch (err) {
        alert('Failed: ' + err.message);
      }
    }
  }
}
</script>
@endsection
