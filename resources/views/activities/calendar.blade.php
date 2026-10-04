@extends('layouts.admin')

@section('title', 'Unified School Activity Calendar')

@section('breadcrumb')
  <span>Collaboration</span>
  <span class="text-slate-300">/</span>
  <span class="text-slate-700 font-medium">Unified Calendar</span>
@endsection

@section('content')
<div class="space-y-6 pb-12" x-data="unifiedCalendar()">

  {{-- Calendar Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">Unified School Activity Calendar</h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Real-time consolidated view of events, staff meetings, deadlines, circulars, exams, and holidays.</p>
    </div>

    {{-- Month Navigator --}}
    <div class="flex items-center gap-2">
      <button @click="prevMonth()" class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition" title="Previous Month">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <span class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold text-slate-800 shadow-xs min-w-[160px] text-center" x-text="monthYearTitle"></span>
      <button @click="nextMonth()" class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition" title="Next Month">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
      <button @click="todayMonth()" class="px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 font-bold text-xs hover:bg-indigo-100 transition">Today</button>
    </div>
  </div>

  {{-- Filter & Legend Bar --}}
  <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3 text-xs">
      <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Filter Types:</span>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="event" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-emerald-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
        <span class="font-medium text-slate-700">Events</span>
      </label>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="meeting" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-purple-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-purple-500"></span>
        <span class="font-medium text-slate-700">Meetings</span>
      </label>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="task" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-amber-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500"></span>
        <span class="font-medium text-slate-700">Tasks Due</span>
      </label>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="notice" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-cyan-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
        <span class="font-medium text-slate-700">Notices</span>
      </label>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="holiday" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-amber-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400"></span>
        <span class="font-medium text-slate-700">Holidays</span>
      </label>
      <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" value="exam" x-model="selectedTypes" @change="fetchActivities()" class="w-3.5 h-3.5 text-slate-600 rounded-sm">
        <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-500"></span>
        <span class="font-medium text-slate-700">Examinations</span>
      </label>
    </div>

    {{-- Fast Action Buttons --}}
    <div class="flex items-center gap-2">
      <a href="{{ route('activities.events.create') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-800">+ Event</a>
      <span class="text-slate-300">|</span>
      <a href="{{ route('activities.meetings.create') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Meeting</a>
      <span class="text-slate-300">|</span>
      <a href="{{ route('activities.tasks.create') }}" class="text-xs font-bold text-slate-600 hover:text-slate-800">+ Task</a>
    </div>
  </div>

  {{-- Calendar Grid --}}
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    {{-- Weekday Header --}}
    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/80 text-center text-xs font-bold text-slate-600 py-3 uppercase tracking-wider">
      <div>Sun</div>
      <div>Mon</div>
      <div>Tue</div>
      <div>Wed</div>
      <div>Thu</div>
      <div>Fri</div>
      <div>Sat</div>
    </div>

    {{-- Days Grid --}}
    <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 min-h-[600px]">
      <template x-for="day in calendarDays" :key="day.dateStr">
        <div @click="selectDay(day)"
             class="min-h-[110px] p-2 flex flex-col justify-between transition cursor-pointer hover:bg-slate-50/80 group"
             :class="{
               'bg-slate-50/50 text-slate-300': !day.isCurrentMonth,
               'bg-indigo-50/40 ring-2 ring-indigo-500/20 inset-0 z-10': day.isToday
             }">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center"
                  :class="day.isToday ? 'bg-indigo-600 text-white' : (day.isCurrentMonth ? 'text-slate-700' : 'text-slate-300')"
                  x-text="day.dayNumber">
            </span>
            <span x-show="day.activities.length > 2" class="text-[10px] font-bold text-slate-400" x-text="'+' + (day.activities.length - 2)"></span>
          </div>

          {{-- Day Activities Preview --}}
          <div class="space-y-1 my-1 overflow-hidden">
            <template x-for="act in day.activities.slice(0, 2)" :key="act.id">
              <div class="px-1.5 py-0.5 rounded text-[10px] font-semibold truncate border transition"
                   :style="'background-color:' + act.color + '18; border-color:' + act.color + '40; color:' + act.color">
                <span x-text="act.title"></span>
              </div>
            </template>
          </div>

          <div class="h-1"></div>
        </div>
      </template>
    </div>
  </div>

  {{-- Selected Day Details Modal / Drawer --}}
  <div x-show="selectedDayModal" x-transition class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="selectedDayModal = false">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-base font-bold text-slate-900" x-text="selectedDay ? selectedDay.dateFormatted : ''"></h3>
          <p class="text-xs text-slate-400" x-text="selectedDay ? selectedDay.activities.length + ' scheduled activities' : ''"></p>
        </div>
        <button @click="selectedDayModal = false" class="text-slate-400 hover:text-slate-600 p-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="max-h-80 overflow-y-auto space-y-2.5">
        <template x-for="act in (selectedDay ? selectedDay.activities : [])" :key="act.id">
          <a :href="act.url" class="p-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-slate-50 transition block">
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-bold text-slate-800" x-text="act.title"></span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                    :style="'background-color:' + act.color + '20; color:' + act.color"
                    x-text="act.type"></span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-1">
              <span x-text="act.category"></span>
              <span x-show="act.venue">&bull;</span>
              <span x-show="act.venue" x-text="act.venue"></span>
            </div>
          </a>
        </template>
        <div x-show="selectedDay && selectedDay.activities.length === 0" class="py-8 text-center text-slate-400 text-xs">
          No activities scheduled on this day.
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function unifiedCalendar() {
  return {
    currentYear: {{ $year }},
    currentMonth: {{ $month }},
    activities: [],
    selectedTypes: ['event', 'meeting', 'task', 'notice', 'holiday', 'exam'],
    selectedDayModal: false,
    selectedDay: null,
    get monthYearTitle() {
      const d = new Date(this.currentYear, this.currentMonth - 1, 1);
      return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    },
    get calendarDays() {
      const firstDayOfMonth = new Date(this.currentYear, this.currentMonth - 1, 1);
      const lastDayOfMonth  = new Date(this.currentYear, this.currentMonth, 0);

      const days = [];
      const startDow = firstDayOfMonth.getDay(); // 0=Sun
      const todayStr = new Date().toISOString().split('T')[0];

      // Leading padding days from previous month
      for (let i = startDow - 1; i >= 0; i--) {
        const d = new Date(this.currentYear, this.currentMonth - 1, -i);
        const dStr = d.toISOString().split('T')[0];
        days.push({
          dateStr: dStr,
          dayNumber: d.getDate(),
          isCurrentMonth: false,
          isToday: dStr === todayStr,
          dateFormatted: d.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
          activities: this.getActivitiesForDate(dStr)
        });
      }

      // Current month days
      for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
        const d = new Date(this.currentYear, this.currentMonth - 1, i);
        // format date string YYYY-MM-DD
        const monthPad = String(this.currentMonth).padStart(2, '0');
        const dayPad   = String(i).padStart(2, '0');
        const dStr = `${this.currentYear}-${monthPad}-${dayPad}`;
        days.push({
          dateStr: dStr,
          dayNumber: i,
          isCurrentMonth: true,
          isToday: dStr === todayStr,
          dateFormatted: d.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
          activities: this.getActivitiesForDate(dStr)
        });
      }

      // Trailing padding days to fill 35 or 42 grid cells
      const totalCells = days.length <= 35 ? 35 : 42;
      const remaining = totalCells - days.length;
      for (let i = 1; i <= remaining; i++) {
        const d = new Date(this.currentYear, this.currentMonth, i);
        const dStr = d.toISOString().split('T')[0];
        days.push({
          dateStr: dStr,
          dayNumber: d.getDate(),
          isCurrentMonth: false,
          isToday: dStr === todayStr,
          dateFormatted: d.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
          activities: this.getActivitiesForDate(dStr)
        });
      }

      return days;
    },
    getActivitiesForDate(dateStr) {
      return this.activities.filter(act => {
        if (!this.selectedTypes.includes(act.type)) return false;
        const actDate = act.start ? act.start.split('T')[0] : '';
        return actDate === dateStr;
      });
    },
    selectDay(day) {
      this.selectedDay = day;
      this.selectedDayModal = true;
    },
    prevMonth() {
      if (this.currentMonth === 1) {
        this.currentMonth = 12;
        this.currentYear--;
      } else {
        this.currentMonth--;
      }
      this.fetchActivities();
    },
    nextMonth() {
      if (this.currentMonth === 12) {
        this.currentMonth = 1;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
      this.fetchActivities();
    },
    todayMonth() {
      const now = new Date();
      this.currentYear = now.getFullYear();
      this.currentMonth = now.getMonth() + 1;
      this.fetchActivities();
    },
    async fetchActivities() {
      try {
        const res = await fetch(`{{ route('api.calendar.activities') }}?month=${this.currentMonth}&year=${this.currentYear}`);
        const data = await res.json();
        if (data.status === 'success') {
          this.activities = data.data || [];
        }
      } catch (err) {
        console.error('Failed to load activities', err);
      }
    },
    init() {
      this.fetchActivities();
    }
  }
}
</script>
@endsection
