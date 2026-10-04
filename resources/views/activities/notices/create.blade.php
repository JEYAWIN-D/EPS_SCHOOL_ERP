@extends('layouts.admin')

@section('title', 'Create New Notice')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <a href="{{ route('activities.notices.index') }}" class="hover:text-indigo-600">Notice Board</a>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Create</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12" x-data="noticeForm()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Create Official Notice</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Author announcements, academic notices, and distribute with acknowledgement tracking.</p>
    </div>
    <a href="{{ route('activities.notices.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 transition">
      Cancel
    </a>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
    <form @submit.prevent="submitNotice" class="space-y-6">

      {{-- Title --}}
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Notice Title <span class="text-red-500">*</span></label>
        <input type="text" x-model="form.title" required placeholder="e.g., Annual Sports Day 2026 Schedule & Guidelines"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
      </div>

      {{-- Grid: Category, Priority, Audience --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Category</label>
          <select x-model="form.notice_type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
            <option value="general">General Notice</option>
            <option value="academic">Academic & Exams</option>
            <option value="circular">Administrative Circular</option>
            <option value="event">School Event</option>
            <option value="fee">Fee Reminder</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Priority Level</label>
          <select x-model="form.priority" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent Alert</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Target Audience <span class="text-red-500">*</span></label>
          <select x-model="form.target_audience" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
            <option value="all">Entire School (Everyone)</option>
            <option value="staff">All Staff & Faculty</option>
            <option value="students">All Students</option>
            <option value="parents">All Parents</option>
            <option value="class_specific">Specific Class</option>
          </select>
        </div>
      </div>

      {{-- Conditional Class Selector --}}
      <div x-show="form.target_audience === 'class_specific'" x-transition class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Select Target Class</label>
        <select x-model="form.target_class_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Choose Class...</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Publication & Expiry Dates --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Publish Date <span class="text-red-500">*</span></label>
          <input type="date" x-model="form.publish_date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Expiry Date (Optional)</label>
          <input type="date" x-model="form.expiry_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      {{-- Content Body --}}
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Notice Body & Details <span class="text-red-500">*</span></label>
        <textarea x-model="form.content" rows="6" required placeholder="Type the complete notice announcement, instructions, and guidelines here..."
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-hidden leading-relaxed"></textarea>
      </div>

      {{-- Options & Acknowledgement Toggle --}}
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" x-model="form.requires_acknowledgement" class="w-4 h-4 text-indigo-600 rounded-sm border-slate-300">
          <div>
            <span class="text-xs font-bold text-slate-800">Require User Acknowledgement</span>
            <p class="text-[11px] text-slate-500">Recipients must click 'Acknowledge' on their portal, generating compliance audit receipts.</p>
          </div>
        </label>

        @canany(['manage notices', 'publish notices', 'approve notices'])
        <label class="flex items-center gap-3 cursor-pointer pt-2 border-t border-slate-200/60">
          <input type="checkbox" x-model="form.requires_approval" class="w-4 h-4 text-indigo-600 rounded-sm border-slate-300">
          <div>
            <span class="text-xs font-bold text-slate-800">Submit as Draft for Principal Approval</span>
            <p class="text-[11px] text-slate-500">Notice will route to the Approval Inbox before being distributed to users.</p>
          </div>
        </label>
        @endcanany
      </div>

      {{-- Submit Buttons --}}
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <a href="{{ route('activities.notices.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
          Cancel
        </a>
        <button type="submit" :disabled="loading" class="btn-primary px-6 py-2.5 text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50">
          <span x-show="!loading" x-text="form.requires_approval ? 'Submit for Approval' : 'Publish Notice & Notify'">Publish Notice</span>
          <span x-show="loading">Publishing...</span>
        </button>
      </div>

    </form>
  </div>
</div>

<script>
function noticeForm() {
  return {
    loading: false,
    form: {
      title: '',
      content: '',
      notice_type: 'general',
      priority: 'normal',
      target_audience: 'all',
      target_class_id: '',
      publish_date: '{{ now()->toDateString() }}',
      expiry_date: '',
      requires_acknowledgement: true,
      requires_approval: false,
    },
    async submitNotice() {
      this.loading = true;
      try {
        const res = await fetch('{{ route('api.notices.store') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.status === 'success') {
          window.location.href = '{{ route('activities.notices.index') }}';
        } else {
          alert(data.message || 'Error creating notice');
        }
      } catch (err) {
        alert('Failed to submit notice: ' + err.message);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
@endsection
