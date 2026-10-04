<!DOCTYPE html>
<html lang="en" x-data="appShell()" x-init="init()">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  {{-- Fonts: non-render-blocking async swap --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
  <noscript>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  </noscript>
  {{-- Chart.js — deferred --}}
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" defer crossorigin="anonymous"></script>
  {{-- Dynamic Theme Styling --}}
  {!! \App\Models\SchoolSetting::getThemeCssVariables() !!}
  @stack('head')

</head>
<body class="bg-slate-50 font-sans">

@include('partials.mobile-nav-speed')

{{-- ── Toast Notifications ───────────────────────────────── --}}
<div id="toast-container" class="fixed top-3 sm:top-4 right-3 sm:right-4 z-[9999] flex flex-col gap-2 w-[calc(100vw-1.5rem)] sm:w-80 max-w-sm pointer-events-none" x-data="toasts()">
  <template x-for="toast in list" :key="toast.id">
    <div class="bg-white rounded-xl shadow-lg border-l-4 px-4 py-3 flex items-start gap-3 animate-slide-right pointer-events-auto"
         :class="{
           'border-green-500': toast.type==='success',
           'border-red-500':   toast.type==='error',
           'border-amber-500': toast.type==='warning',
           'border-blue-500':  toast.type==='info'
         }">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-800 break-words" x-text="toast.title"></p>
        <p class="text-xs text-slate-500 mt-0.5 break-words" x-text="toast.message" x-show="toast.message"></p>
      </div>
      <button @click="remove(toast.id)" class="text-slate-400 hover:text-slate-600 mt-0.5 p-1 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </template>
</div>

<div class="flex h-screen overflow-hidden">

  {{-- ── Mobile backdrop ──────────────────────────────────────── --}}
  <div x-show="isMobile && sidebarOpen"
       @click="sidebarOpen = false"
       class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
       x-transition:enter="transition-opacity duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-300"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       style="display:none">
  </div>

  {{-- ── Sidebar ────────────────────────────────────────────── --}}
  <aside id="sidebar"
         class="fixed lg:relative inset-y-0 left-0 flex-shrink-0 flex flex-col h-full overflow-hidden transition-all duration-300 ease-in-out z-50 lg:z-30 max-w-[85vw] sm:max-w-none"
         :class="{
           'w-72 translate-x-0 shadow-2xl':      isMobile &&  sidebarOpen,
           'w-72 -translate-x-full':              isMobile && !sidebarOpen,
           'w-64':                               !isMobile &&  sidebarOpen,
           'w-16':                               !isMobile && !sidebarOpen
         }"
         style="background: var(--gradient-sidebar)">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10 flex-shrink-0">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center flex-shrink-0 shadow-blue-glow">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
      </div>
      <div class="flex-1 overflow-hidden" x-show="sidebarOpen || isMobile">
        <p class="text-white font-extrabold text-base leading-tight truncate" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ config('app.name', 'DASA EduERP') }}</p>
        <p class="text-slate-300 text-xs font-medium truncate">2025-2026 Academic Year</p>
      </div>
      {{-- Mobile close button --}}
      <button x-show="isMobile" @click="sidebarOpen = false"
              class="lg:hidden flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition"
              title="Close menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Nav — only this scrolls --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-0.5 min-h-0" style="-ms-overflow-style:none;scrollbar-width:none">
    <style>
      #sidebar nav::-webkit-scrollbar{display:none}
      .nav-group-label {
        display: block;
        padding: 0.25rem 0.75rem;
        margin-top: 1.15rem;
        margin-bottom: 0.25rem;
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #94A3B8 !important;
      }
    </style>

      @php $currentRoute = request()->route()?->getName() ?? ''; @endphp

      {{-- MAIN --}}
      <p class="nav-group-label">MAIN</p>
      <x-nav-item route="dashboard" icon="home" label="Dashboard" :active="str_starts_with($currentRoute, 'dashboard')" :open="$sidebarOpen ?? true" />

      {{-- ACADEMICS --}}
      @canany(['view admissions','view students','view academics','view attendance','view examinations'])
      <p class="nav-group-label">ACADEMICS</p>
      @can('view admissions')
      <x-nav-item route="admissions.index"     icon="clipboard-document-list" label="Admissions"     :active="str_starts_with($currentRoute, 'admissions.') && !str_starts_with($currentRoute, 'admissions.approvals')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="admissions.approvals" icon="shield-check"             label="Approvals Desk" :active="str_starts_with($currentRoute, 'admissions.approvals')" :open="$sidebarOpen ?? true" />
      @endcan
      @can('view students')
      <x-nav-item route="students.index"    icon="users"                   label="Students"    :active="str_starts_with($currentRoute, 'students')"    :open="$sidebarOpen ?? true" />
      @endcan
      @can('view academics')
      <x-nav-item route="classes.index"     icon="academic-class"          label="Classes"     :active="str_starts_with($currentRoute, 'classes')"     :open="$sidebarOpen ?? true" />
      <x-nav-item route="academics.index"   icon="academic-cap"            label="Academics"   :active="str_starts_with($currentRoute, 'academics')"   :open="$sidebarOpen ?? true" />
      @endcan
      @can('view attendance')
      <x-nav-item route="attendance.index"  icon="calendar-days"           label="Attendance"  :active="str_starts_with($currentRoute, 'attendance')"  :open="$sidebarOpen ?? true" />
      @endcan
      @can('view examinations')
      <x-nav-item route="examinations.index" icon="document-text"          label="Examinations" :active="str_starts_with($currentRoute, 'examinations')" :open="$sidebarOpen ?? true" />
      @endcan
      @endcanany

      {{-- FINANCE --}}
      @canany(['view fees','view employees'])
      <p class="nav-group-label">FINANCE</p>
      @can('view fees')
      <x-nav-item route="accounts.index" icon="calculator"   label="Account Management" :active="str_starts_with($currentRoute, 'accounts')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="fees.index"     icon="banknotes"    label="Fee Management"     :active="str_starts_with($currentRoute, 'fees')"     :open="$sidebarOpen ?? true" />
      @endcan
      @can('view employees')
      <x-nav-item route="hr.index"      icon="identification" label="HR & Payroll" :active="str_starts_with($currentRoute, 'hr')"      :open="$sidebarOpen ?? true" />
      @endcan
      @endcanany

      {{-- ADMINISTRATION --}}
      @canany(['view library','view transport','view hostel','view inventory'])
      <p class="nav-group-label">ADMINISTRATION</p>
      @can('view library')
      <x-nav-item route="library.index"   icon="book-open"   label="Library"    :active="str_starts_with($currentRoute, 'library')"   :open="$sidebarOpen ?? true" />
      @endcan
      @can('view transport')
      <x-nav-item route="transport.index" icon="truck"        label="Transport"  :active="str_starts_with($currentRoute, 'transport')" :open="$sidebarOpen ?? true" />
      @endcan
      @can('view hostel')
      <x-nav-item route="hostel.index"    icon="building-office-2" label="Hostel" :active="str_starts_with($currentRoute, 'hostel')"    :open="$sidebarOpen ?? true" />
      @endcan
      @can('view inventory')
      <x-nav-item route="warehouse.index" icon="archive-box"  label="Warehouse"  :active="str_starts_with($currentRoute, 'warehouse') || str_starts_with($currentRoute, 'inventory')" :open="$sidebarOpen ?? true" />
      @endcan
      @endcanany

      {{-- COLLABORATION & ACTIVITY HUB --}}
      <p class="nav-group-label">COLLABORATION</p>
      <x-nav-item route="activities.my-day" icon="home" label="My Day" :active="str_starts_with($currentRoute, 'activities.my-day')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="circulars.index" icon="document-duplicate" label="Circulars & Orders" :active="str_starts_with($currentRoute, 'circulars')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="activities.notices.index" icon="document-text" label="Notices & Bulletins" :active="str_starts_with($currentRoute, 'activities.notices')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="activities.events.index" icon="calendar" label="School Events" :active="str_starts_with($currentRoute, 'activities.events')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="activities.meetings.index" icon="users" label="Meetings & MoM" :active="str_starts_with($currentRoute, 'activities.meetings')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="activities.tasks.index" icon="clipboard-document-list" label="Task Desk" :active="str_starts_with($currentRoute, 'activities.tasks')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="activities.calendar" icon="calendar-days" label="Unified Calendar" :active="str_starts_with($currentRoute, 'activities.calendar')" :open="$sidebarOpen ?? true" />
      @canany(['manage notices', 'approve notices', 'manage tasks', 'approve tasks'])
      <x-nav-item route="activities.approvals" icon="shield-check" label="Approvals Desk" :active="str_starts_with($currentRoute, 'activities.approvals')" :open="$sidebarOpen ?? true" />
      @endcanany
      @can('view reports')
      <x-nav-item route="activities.reports" icon="chart-bar" label="Activity Reports" :active="str_starts_with($currentRoute, 'activities.reports')" :open="$sidebarOpen ?? true" />
      @endcan

      {{-- ENGAGEMENT --}}
      @canany(['send email','view lms','view events','view gate','view alumni'])
      <p class="nav-group-label">ENGAGEMENT</p>
      @can('send email')
      <x-nav-item route="communication.index" icon="chat-bubble-left-right" label="Communication" :active="str_starts_with($currentRoute, 'communication')" :open="$sidebarOpen ?? true" />
      @endcan
      @can('view lms')
      <x-nav-item route="lms.index"     icon="play-circle"    label="LMS"       :active="str_starts_with($currentRoute, 'lms')"     :open="$sidebarOpen ?? true" />
      @endcan
      @can('view events')
      <x-nav-item route="events.index"  icon="calendar"       label="Events"    :active="str_starts_with($currentRoute, 'events')"  :open="$sidebarOpen ?? true" />
      <x-nav-item route="circulars.index" icon="document-duplicate" label="Circulars & Orders" :active="str_starts_with($currentRoute, 'circulars')" :open="$sidebarOpen ?? true" />
      @endcan
      @can('view gate')
      <x-nav-item route="gate.index"     icon="user-plus"     label="Gate/Visitors" :active="str_starts_with($currentRoute, 'gate')" :open="$sidebarOpen ?? true" />
      @endcan
      @can('view alumni')
      <x-nav-item route="alumni.index"  icon="star"           label="Alumni"    :active="str_starts_with($currentRoute, 'alumni')"  :open="$sidebarOpen ?? true" />
      @endcan
      @endcanany

      {{-- SYSTEM --}}
      @canany(['view reports','view audit logs','manage settings'])
      <p class="nav-group-label">SYSTEM</p>
      @can('view reports')
      <x-nav-item route="reports.index"   icon="chart-bar"     label="Reports"    :active="str_starts_with($currentRoute, 'reports')"  :open="$sidebarOpen ?? true" />
      @endcan
      @canany(['view audit logs','manage settings'])
      <x-nav-item route="system.audit-log" icon="shield-check" label="Audit/Security" :active="str_starts_with($currentRoute, 'system')" :open="$sidebarOpen ?? true" />
      @endcanany
      @can('manage settings')
      <x-nav-item route="settings.index"  icon="cog-6-tooth"   label="Settings"   :active="$currentRoute === 'settings.index' || str_starts_with($currentRoute, 'settings.save')" :open="$sidebarOpen ?? true" />
      <x-nav-item route="settings.books-notes.index" icon="book-open" label="Book, Note & Uniform Distribution" :active="str_starts_with($currentRoute, 'settings.books-notes')" :open="$sidebarOpen ?? true" />
      @endcan
      @endcanany

    </nav>

    {{-- Help widget --}}
    <div class="flex-shrink-0 px-3 pb-2"
         x-show="sidebarOpen || isMobile"
         x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         style="display:none">
      <div class="px-3 py-2.5 rounded-xl" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08)">
        <a href="mailto:support@dcinnovision.com"
           class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-xs font-medium mb-1.5">
          <svg class="w-3.5 h-3.5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Help & Support
        </a>
        <p class="text-slate-600 text-[10px]">DASA EduERP &bull; v1.0</p>
      </div>
    </div>

    {{-- Chevron toggle tab — desktop only --}}
    <button x-show="!isMobile" @click="sidebarOpen = !sidebarOpen"
            class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 w-8 h-8 bg-white border border-slate-200 rounded-full items-center justify-center z-40 group transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
            style="box-shadow:0 2px 8px rgba(0,0,0,0.15),0 0 0 1px rgba(255,255,255,0.8)"
            :title="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'">
      <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition-all duration-300"
           :class="sidebarOpen ? '' : 'rotate-180'"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>

  </aside>

  {{-- ── Main area ──────────────────────────────────────────── --}}
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

    {{-- Topbar --}}
    <header class="h-14 bg-white border-b border-slate-200 flex items-center px-2.5 sm:px-4 md:px-6 gap-2 sm:gap-3 flex-shrink-0 z-20">

      {{-- Mobile hamburger --}}
      <button @click="sidebarOpen = true" class="lg:hidden btn-icon flex-shrink-0 w-9 h-9" title="Open navigation menu">
        <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      {{-- Desktop sidebar toggle --}}
      <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:flex btn-icon flex-shrink-0" :title="sidebarOpen ? 'Collapse' : 'Expand'">
        <svg class="w-5 h-5 transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M21 19l-7-7 7-7"/>
        </svg>
      </button>

      {{-- Back Button & Breadcrumb --}}
      <div class="flex-1 flex items-center gap-2 text-xs text-slate-400">
        <button onclick="window.history.back()" type="button" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition text-xs border border-slate-200 shadow-xs cursor-pointer" title="Go to previous page">
          <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          <span>Back</span>
        </button>
        @hasSection('breadcrumb')
          <span>/</span>
          @yield('breadcrumb')
        @endif
      </div>

      {{-- Right actions --}}
      <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0" x-data="{ notifOpen: false, topUserOpen: false }">

        {{-- Academic Year badge --}}
        <span class="badge-blue text-[11px] sm:text-xs cursor-default hidden xs:inline-flex" title="Academic Year">2025-2026</span>

        {{-- Role badge --}}
        @php $roleName = auth()->user()->getRoleNames()->first(); @endphp
        @if($roleName)
          <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 capitalize">
            {{ str_replace('_', ' ', $roleName) }}
          </span>
        @endif

        {{-- Notifications Dropdown (Live Activity Hub Engine) --}}
        <div class="relative" x-data="{
          notifOpen: false,
          unreadCount: 0,
          recent: [],
          async fetchNotifs() {
            try {
              const res = await fetch('{{ route('api.notifications.unread-count') }}');
              const d = await res.json();
              this.unreadCount = d.unread_count || 0;
              this.recent = d.recent || [];
            } catch(e) {}
          },
          async markAllRead() {
            try {
              await fetch('{{ route('api.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') }
              });
              this.unreadCount = 0;
              this.fetchNotifs();
            } catch(e) {}
          }
        }" x-init="fetchNotifs(); setInterval(() => fetchNotifs(), 30000)" @click.outside="notifOpen = false">
          <button @click="notifOpen = !notifOpen; if(notifOpen) { topUserOpen = false; fetchNotifs(); }" class="btn-icon relative w-9 h-9" title="Notifications">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span x-show="unreadCount > 0" class="absolute top-1 right-1 px-1 min-w-[16px] h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white" x-text="unreadCount"></span>
          </button>

          {{-- Notification Box --}}
          <div x-show="notifOpen"
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-100"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="absolute right-0 top-full mt-2 z-50 w-80 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden max-h-[80vh] flex flex-col"
               style="display:none">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
              <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Activity Alerts</span>
              <button @click="markAllRead()" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Mark all read</button>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto max-h-72">
              <template x-for="item in recent" :key="item.id">
                <a :href="item.action_url" class="block p-3 hover:bg-slate-50 transition" :class="!item.read_at ? 'bg-indigo-50/30' : ''">
                  <p class="text-xs font-semibold text-slate-800" x-text="item.title"></p>
                  <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2" x-text="item.message"></p>
                </a>
              </template>
              <div x-show="recent.length === 0" class="p-6 text-center text-slate-400 text-xs">
                No recent notifications.
              </div>
            </div>
            <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center flex-shrink-0">
              <a href="{{ route('activities.my-day') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open My Day Cockpit &rarr;</a>
            </div>
          </div>
        </div>

        {{-- Topbar User Avatar Dropdown (With Log Out) --}}
        <div class="relative" @click.outside="topUserOpen = false">
          <button type="button"
                  @click="topUserOpen = !topUserOpen; if(topUserOpen) notifOpen = false"
                  class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center cursor-pointer ring-2 ring-indigo-100 hover:ring-indigo-300 transition-all select-none flex-shrink-0 focus:outline-hidden"
                  title="{{ auth()->user()->name }} — Click for menu">
            <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
          </button>

          {{-- User Menu Box --}}
          <div x-show="topUserOpen"
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-100"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="absolute right-0 top-full mt-2 z-50 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden"
               style="display:none">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
              <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
              <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
              <span class="inline-block mt-1.5 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] font-bold capitalize">
                {{ str_replace('_', ' ', $roleName ?? 'User') }}
              </span>
            </div>

            <div class="py-1">
              <a href="{{ route('settings.index') }}"
                 class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                My Profile
              </a>
              <a href="{{ route('settings.index') }}"
                 class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
              </a>
            </div>

            {{-- Log Out Option --}}
            <div class="border-t border-slate-100 py-1">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer">
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                  </svg>
                  Sign Out / Log Out
                </button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 pb-safe page-enter">

      {{-- Flash messages --}}
      @if(session('success'))
        <div class="alert-success mb-4 sm:mb-5">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span class="break-words">{{ session('success') }}</span>
        </div>
      @endif
      @if(session('error'))
        <div class="alert-error mb-4 sm:mb-5">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <span class="break-words">{{ session('error') }}</span>
        </div>
      @endif

      @yield('content')
    </main>

  </div>
</div>

@stack('scripts')
<script>
function appShell() {
  return {
    sidebarOpen: true,
    isMobile: window.innerWidth < 1024,
    init() {
      try {
        const stored = localStorage.getItem('sidebar_open');
        if (stored !== null) {
          this.sidebarOpen = JSON.parse(stored);
        }
      } catch(e) {}

      if (this.isMobile) this.sidebarOpen = false;

      const onResize = () => {
        const mobile = window.innerWidth < 1024;
        if (mobile !== this.isMobile) {
          this.isMobile = mobile;
          this.sidebarOpen = !mobile;
        }
      };
      window.addEventListener('resize', onResize);

      this.$watch('sidebarOpen', open => {
        try {
          localStorage.setItem('sidebar_open', JSON.stringify(open));
        } catch(e) {}
        if (this.isMobile) {
          document.body.style.overflow = open ? 'hidden' : '';
        }
      });
    }
  }
}
function toasts() {
  return {
    list: [],
    add(type, title, message = '') {
      const id = Date.now()
      this.list.push({ id, type, title, message })
      setTimeout(() => this.remove(id), 4000)
    },
    remove(id) { this.list = this.list.filter(t => t.id !== id) }
  }
}
</script>
</body>
</html>
