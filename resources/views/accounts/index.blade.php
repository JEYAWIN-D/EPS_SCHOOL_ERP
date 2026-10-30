@extends('layouts.app')

@section('title', 'School Account Management & Cash Ledger — DASA EduERP')

@section('content')
{{-- Global Helper Script for Alpine Component --}}
<script>
function accountManagementData() {
  return {
    transferModalOpen: false,
    detailModalOpen: false,
    selectedTxn: null,
    txnList: @json($paginator->items()),
    scrollToLedger() {
      const el = document.getElementById('transaction-logs');
      const main = document.querySelector('main');
      if (el && main) {
        const targetTop = el.offsetTop - 24;
        main.scrollTo({ top: targetTop, behavior: 'smooth' });
      } else if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },
    init() {
      if (window.location.hash === '#transaction-logs' || '{{ request('account') }}') {
        setTimeout(() => this.scrollToLedger(), 100);
        setTimeout(() => this.scrollToLedger(), 400);
      }
    },
    filterAccountAndScroll(acc) {
      const currentAcc = '{{ $accountFilter }}';
      if (currentAcc === acc) {
        this.scrollToLedger();
      } else {
        window.location.href = "{{ route('accounts.index') }}?account=" + acc + "&date_filter=all#transaction-logs";
      }
    },
    viewTxnByIndex(idx) {
      this.selectedTxn = this.txnList[idx] || null;
      this.detailModalOpen = true;
    },
    formatMoney(num) {
      return '₹' + Number(num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  };
}
</script>

<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="accountManagementData()">

  {{-- ── 1. Clean & Airy Page Header ────────────────────────────────────────── --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider">
        <span class="text-indigo-600 font-extrabold">Finance &amp; Accounts</span>
        <span>&bull;</span>
        <span>Academic Year {{ $currentYear?->name ?? '2025-2026' }}</span>
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
        School Account &amp; Cash Management
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 max-w-2xl font-medium">
        Live tracking of daily fee collections, cash drawers, UPI receipts, and institutional expenses.
      </p>
    </div>

    {{-- Clean Action Toolbar --}}
    <div class="flex items-center flex-wrap gap-2.5">
      <button id="transfer-funds-btn" @click="transferModalOpen = true" type="button"
              class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs hover:shadow-md transition flex items-center gap-2 cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <span>Transfer Funds</span>
      </button>

      <a href="{{ route('expenses.create') }}"
         class="px-4 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Record Expense</span>
      </a>

      <a href="{{ route('accounts.daybook') }}?date={{ today()->toDateString() }}" target="_blank"
         class="px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-1.5" title="Print Day Book">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span class="hidden sm:inline">Day Book</span>
      </a>

      <a href="{{ route('accounts.export', request()->query()) }}"
         class="px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-1.5" title="Export CSV">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span class="hidden sm:inline">Export</span>
      </a>
    </div>
  </div>

  {{-- Flash Alerts --}}
  @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('success') }}</span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 font-bold text-sm">✕</button>
    </div>
  @endif

  @if(isset($errors) && $errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-semibold space-y-1 shadow-xs">
      <p class="font-bold text-rose-800">Please review the following:</p>
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- ── 2. Today's Financial Pulse (4 Clean KPI Cards) ────────────────────── --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Today's Total Collections --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-sm transition space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-600">Today's Collections</span>
        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono tracking-tight">
          ₹{{ number_format($todayInflow, 2) }}
        </p>
        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-medium">
          <span>Admissions: <strong class="text-emerald-700 font-bold">₹{{ number_format($todayAdmissionFees) }}</strong></span>
          <span>Fees: <strong class="text-slate-800 font-bold">₹{{ number_format($todayTuitionFees + $todayTransportFees) }}</strong></span>
        </div>
      </div>
    </div>

    {{-- Today's Total Expenses --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-sm transition space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-600">Today's Expenses</span>
        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-black text-rose-600 font-mono tracking-tight">
          ₹{{ number_format($todayOutflow, 2) }}
        </p>
        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-medium">
          <span>Academic: <strong class="text-slate-800 font-bold">₹{{ number_format($todayAcademicExpenses) }}</strong></span>
          <span>Maintenance: <strong class="text-slate-800 font-bold">₹{{ number_format($todayMaintenanceExpenses) }}</strong></span>
        </div>
      </div>
    </div>

    {{-- Today's Net Cash Balance --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-sm transition space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-600">Today's Net Cash</span>
        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-black font-mono tracking-tight {{ $todayNet >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
          {{ $todayNet >= 0 ? '+' : '' }}₹{{ number_format($todayNet, 2) }}
        </p>
        <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 font-medium">
          <span>{{ $todayNet >= 0 ? 'Surplus cash accumulated today' : 'Daily expenses exceeded intake' }}</span>
        </div>
      </div>
    </div>

    {{-- Total School Funds Available --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-sm transition space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-600">Total Liquid Funds</span>
        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-black text-indigo-900 font-mono tracking-tight">
          ₹{{ number_format($balances['total_balance'], 2) }}
        </p>
        <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 font-medium">
          <span>All cash boxes &amp; UPI combined</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ── 3. School Cash Boxes & Digital Accounts (3 Clean Wallet Cards) ──────── --}}
  <div class="space-y-3">
    <div class="flex items-center justify-between px-1">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Cash Drawers &amp; Digital Accounts</h2>
        <p class="text-xs text-slate-500">Live balance breakdown by collection point</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      {{-- 1. UPI Account --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900">UPI Digital Account</h3>
                <p class="text-[11px] text-slate-400">PhonePe, GPay, Paytm &amp; QR</p>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-100">
              Digital
            </span>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-100">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Available Digital Balance</span>
            <p class="text-2xl font-black text-slate-900 font-mono mt-0.5">
              ₹{{ number_format($balances['upi']['balance'], 2) }}
            </p>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <div class="flex items-center gap-3">
            <span>Today: <strong class="text-emerald-700 font-bold">+₹{{ number_format($todayUpiInflow) }}</strong></span>
            <span>Out: <strong class="text-slate-600 font-bold">₹{{ number_format($todayUpiOutflow) }}</strong></span>
          </div>
          <a href="{{ route('accounts.index', ['account' => 'upi', 'date_filter' => 'all']) }}#transaction-logs"
             @click.prevent="filterAccountAndScroll('upi')"
             class="text-indigo-600 hover:text-indigo-800 font-bold text-[11px] flex items-center gap-0.5 cursor-pointer">
            <span>View Logs</span> &rarr;
          </a>
        </div>
      </div>

      {{-- 2. Cash Box 1 (Front Office) --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900">Front Office Cash (Box 1)</h3>
                <p class="text-[11px] text-slate-400">Reception desk &amp; admissions</p>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
              Counter
            </span>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-100">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Available Cash in Drawer</span>
            <p class="text-2xl font-black text-slate-900 font-mono mt-0.5">
              ₹{{ number_format($balances['cash_box_1']['balance'], 2) }}
            </p>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <div class="flex items-center gap-3">
            <span>Today: <strong class="text-emerald-700 font-bold">+₹{{ number_format($todayBox1Inflow) }}</strong></span>
            <span>Out: <strong class="text-slate-600 font-bold">₹{{ number_format($todayBox1Outflow) }}</strong></span>
          </div>
          <a href="{{ route('accounts.index', ['account' => 'cash_box_1', 'date_filter' => 'all']) }}#transaction-logs"
             @click.prevent="filterAccountAndScroll('cash_box_1')"
             class="text-indigo-600 hover:text-indigo-800 font-bold text-[11px] flex items-center gap-0.5 cursor-pointer">
            <span>View Logs</span> &rarr;
          </a>
        </div>
      </div>

      {{-- 3. Cash Box 2 (Accounts Office Vault) --}}
      <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900">Accounts Vault (Box 2)</h3>
                <p class="text-[11px] text-slate-400">Main vault &amp; official expense fund</p>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
              Vault
            </span>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-100">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Available Vault Cash</span>
            <p class="text-2xl font-black text-slate-900 font-mono mt-0.5">
              ₹{{ number_format($balances['cash_box_2']['balance'], 2) }}
            </p>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <div class="flex items-center gap-3">
            <span>Today: <strong class="text-emerald-700 font-bold">+₹{{ number_format($todayBox2Inflow) }}</strong></span>
            <span>Out: <strong class="text-slate-600 font-bold">₹{{ number_format($todayBox2Outflow) }}</strong></span>
          </div>
          <a href="{{ route('accounts.index', ['account' => 'cash_box_2', 'date_filter' => 'all']) }}#transaction-logs"
             @click.prevent="filterAccountAndScroll('cash_box_2')"
             class="text-indigo-600 hover:text-indigo-800 font-bold text-[11px] flex items-center gap-0.5 cursor-pointer">
            <span>View Logs</span> &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- ── 4. Unified Transaction Ledger & Activity Logs ────────────── --}}
  <div id="transaction-logs" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs space-y-4 overflow-hidden scroll-mt-6">
    {{-- Header & Search Filter Bar --}}
    <div class="p-5 border-b border-slate-100 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-lg font-bold text-slate-900">
            Financial Ledger &amp; Transaction Logs
          </h2>
          <p class="text-xs text-slate-500">Detailed chronological record of all student fees, bus fares, admissions, and expenses</p>
        </div>
        <div class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 self-start sm:self-auto">
          Showing {{ $paginator->total() }} Transactions
        </div>
      </div>

      {{-- Filter Controls Form --}}
      <form id="account-filter-form" method="GET" action="{{ route('accounts.index') }}" class="space-y-3 pt-1">
        <input type="hidden" name="date_filter" value="{{ $dateFilter }}">
        {{-- Quick Date Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2">
          @php
            $pills = [
              'today'      => 'Today',
              'yesterday'  => 'Yesterday',
              'this_week'  => 'This Week',
              'this_month' => 'This Month',
              'all'        => 'All Time',
            ];
          @endphp
          @foreach($pills as $k => $label)
            <a href="{{ route('accounts.index', array_merge(request()->except('page', 'date_from', 'date_to'), ['date_filter' => $k])) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $dateFilter === $k ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
              {{ $label }}
            </a>
          @endforeach
        </div>

        {{-- Dropdowns & Search Input Grid (12-col aligned layout) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end pt-1">
          {{-- Account Filter --}}
          <div class="lg:col-span-3">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Account / Drawer</label>
            <select id="account-select-dropdown" name="account" onchange="this.form.submit()"
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition cursor-pointer">
              <option value="all" {{ $accountFilter === 'all' ? 'selected' : '' }}>All Accounts</option>
              <option value="upi" {{ $accountFilter === 'upi' ? 'selected' : '' }}>UPI Digital Account</option>
              <option value="cash_box_1" {{ $accountFilter === 'cash_box_1' ? 'selected' : '' }}>Front Office Cash (Box 1)</option>
              <option value="cash_box_2" {{ $accountFilter === 'cash_box_2' ? 'selected' : '' }}>Accounts Vault (Box 2)</option>
            </select>
          </div>

          {{-- Flow Filter --}}
          <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Flow Type</label>
            <select name="flow" onchange="this.form.submit()"
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition cursor-pointer">
              <option value="all" {{ $flowFilter === 'all' ? 'selected' : '' }}>All Flows</option>
              <option value="inflow" {{ $flowFilter === 'inflow' ? 'selected' : '' }}>Inflow (Fees &amp; Income)</option>
              <option value="outflow" {{ $flowFilter === 'outflow' ? 'selected' : '' }}>Outflow (Expenses)</option>
              <option value="transfer" {{ $flowFilter === 'transfer' ? 'selected' : '' }}>Internal Transfers</option>
            </select>
          </div>

          {{-- Category Filter --}}
          <div class="lg:col-span-3">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Category</label>
            <select name="category" onchange="this.form.submit()"
                    class="w-full h-10 px-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition cursor-pointer">
              <option value="all" {{ $categoryFilter === 'all' ? 'selected' : '' }}>All Categories</option>
              <option value="admission" {{ $categoryFilter === 'admission' ? 'selected' : '' }}>Admission Fees</option>
              <option value="fees" {{ $categoryFilter === 'fees' ? 'selected' : '' }}>Tuition / Term Fees</option>
              <option value="bus" {{ $categoryFilter === 'bus' ? 'selected' : '' }}>Bus / Transport Fees</option>
              <option value="academic_expense" {{ $categoryFilter === 'academic_expense' ? 'selected' : '' }}>Academic Expenses</option>
              <option value="maintenance_expense" {{ $categoryFilter === 'maintenance_expense' ? 'selected' : '' }}>Maintenance Expenses</option>
              <option value="transfer" {{ $categoryFilter === 'transfer' ? 'selected' : '' }}>Fund Transfers</option>
            </select>
          </div>

          {{-- Search Input with perfectly aligned button and icon --}}
          <div class="lg:col-span-4">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Search Records</label>
            <div class="flex items-center gap-2">
              <div style="position: relative; flex: 1 1 0%; width: 100%;">
                <div style="position: absolute; left: 12px; top: 0; bottom: 0; display: flex; align-items: center; pointer-events: none;">
                  <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Student name, receipt no, UTR..."
                       style="padding-left: 36px;"
                       class="w-full h-10 pr-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition">
              </div>
              <button type="submit"
                      class="h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center shrink-0 cursor-pointer shadow-xs">
                Search
              </button>
              @if($search || $accountFilter !== 'all' || $flowFilter !== 'all' || $categoryFilter !== 'all' || $dateFilter !== 'today')
                <a href="{{ route('accounts.index') }}"
                   class="h-10 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center justify-center shrink-0" title="Reset all filters">
                  Reset
                </a>
              @endif
            </div>
          </div>
        </div>
      </form>
    </div>

    {{-- Transactions Table --}}
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-400 uppercase tracking-wider text-[10px] font-bold">
            <th class="py-3 px-4">Date &amp; Time</th>
            <th class="py-3 px-4">Receipt / Voucher</th>
            <th class="py-3 px-4">Type</th>
            <th class="py-3 px-4">Student / Payee</th>
            <th class="py-3 px-4">Purpose / Category</th>
            <th class="py-3 px-4">Account Box</th>
            <th class="py-3 px-4 text-right">Amount (₹)</th>
            <th class="py-3 px-4 text-center">Staff</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($paginator as $row)
            <tr id="txn-row-{{ $loop->index }}" class="hover:bg-slate-50 transition cursor-pointer" @click="viewTxnByIndex({{ $loop->index }})">
              {{-- Date & Time --}}
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="font-bold text-slate-900 block">{{ $row->formatted_date }}</span>
                <span class="text-[11px] text-slate-400 font-mono">{{ $row->time }}</span>
              </td>

              {{-- Voucher / Receipt --}}
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                  {{ $row->voucher_no }}
                </span>
                @if($row->reference_no && $row->reference_no !== '—')
                  <span class="block text-[10px] text-slate-400 font-mono mt-0.5">Ref: {{ $row->reference_no }}</span>
                @endif
              </td>

              {{-- Flow Badge --}}
              <td class="py-3.5 px-4 whitespace-nowrap">
                @if($row->type === 'inflow')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    <span>Inflow</span>
                  </span>
                @elseif($row->type === 'outflow')
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    <span>Outflow</span>
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span>Transfer</span>
                  </span>
                @endif
              </td>

              {{-- Party (Student / Vendor) --}}
              <td class="py-3.5 px-4">
                <span class="font-bold text-slate-900 block text-xs">{{ $row->party_name }}</span>
                <span class="text-[11px] text-slate-400 block">{{ $row->party_subtitle }}</span>
              </td>

              {{-- What For / Category --}}
              <td class="py-3.5 px-4 max-w-xs">
                <span class="font-bold text-slate-800 block text-xs">{{ $row->category_label }}</span>
                <span class="text-[11px] text-slate-400 truncate block mt-0.5" title="{{ $row->description }}">{{ $row->description }}</span>
              </td>

              {{-- Account / Box --}}
              <td class="py-3.5 px-4 whitespace-nowrap">
                @if($row->account === 'upi')
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                    <span>UPI Account</span>
                  </span>
                @elseif($row->account === 'cash_box_1')
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Cash Box 1</span>
                  </span>
                @elseif($row->account === 'cash_box_2')
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                    <span>Cash Box 2</span>
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-50 text-slate-700 border border-slate-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    <span>{{ $row->account_label }}</span>
                  </span>
                @endif
              </td>

              {{-- Amount --}}
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <span class="font-mono font-extrabold text-sm {{ $row->type === 'inflow' ? 'text-emerald-700' : ($row->type === 'outflow' ? 'text-rose-600' : 'text-indigo-700') }}">
                  {{ $row->type === 'inflow' ? '+' : ($row->type === 'outflow' ? '-' : '') }}₹{{ number_format($row->amount, 2) }}
                </span>
              </td>

              {{-- Staff --}}
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span class="text-[11px] text-slate-500 font-medium">{{ $row->staff_name }}</span>
              </td>

              {{-- Action button --}}
              <td class="py-3.5 px-4 text-right whitespace-nowrap" @click.stop>
                <button id="details-btn-{{ $loop->index }}" type="button" @click="viewTxnByIndex({{ $loop->index }})"
                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                  Details
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-12 text-slate-400">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="font-bold text-slate-700 text-sm">No transactions match your criteria.</p>
                <p class="text-xs text-slate-400 mt-1">Try selecting a different date range or clearing your search.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($paginator->hasPages())
      <div class="p-4 border-t border-slate-100 flex items-center justify-between">
        {{ $paginator->links() }}
      </div>
    @endif
  </div>

  {{-- ── MODAL 1: Transaction Details Modal ───────────────────────── --}}
  <div x-show="detailModalOpen" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 sm:p-7 space-y-5 shadow-2xl relative border border-slate-200 animate-in fade-in zoom-in duration-150"
         @click.away="detailModalOpen = false">

      {{-- Modal Header --}}
      <div class="flex items-start justify-between border-b border-slate-100 pb-3">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                :class="selectedTxn?.type === 'inflow' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (selectedTxn?.type === 'outflow' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200')"
                x-text="selectedTxn?.flow_label"></span>
          <h3 class="text-base font-bold text-slate-900 mt-1" x-text="selectedTxn?.voucher_no"></h3>
          <p class="text-xs text-slate-400" x-text="selectedTxn?.formatted_date + ' • ' + selectedTxn?.time"></p>
        </div>
        <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold p-1">&times;</button>
      </div>

      {{-- Amount Display --}}
      <div class="p-4 rounded-xl text-center space-y-1"
           :class="selectedTxn?.type === 'inflow' ? 'bg-emerald-50/70 border border-emerald-200' : (selectedTxn?.type === 'outflow' ? 'bg-rose-50/70 border border-rose-200' : 'bg-indigo-50/70 border border-indigo-200')">
        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Transaction Amount</span>
        <p class="text-3xl font-black font-mono"
           :class="selectedTxn?.type === 'inflow' ? 'text-emerald-700' : (selectedTxn?.type === 'outflow' ? 'text-rose-600' : 'text-indigo-700')"
           x-text="(selectedTxn?.type === 'inflow' ? '+' : (selectedTxn?.type === 'outflow' ? '-' : '')) + formatMoney(selectedTxn?.amount)">
        </p>
      </div>

      {{-- Details Key-Value List --}}
      <div class="space-y-2.5 text-xs divide-y divide-slate-100">
        <div class="pt-2 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Party / Payee:</span>
          <span class="font-bold text-slate-900 text-right" x-text="selectedTxn?.party_name"></span>
        </div>

        <div class="pt-2 flex justify-between items-center" x-show="selectedTxn?.party_subtitle">
          <span class="text-slate-500 font-medium">Student / Entity Details:</span>
          <span class="font-medium text-slate-700 text-right" x-text="selectedTxn?.party_subtitle"></span>
        </div>

        <div class="pt-2 flex justify-between items-center">
          <span class="text-slate-500 font-medium">What For / Purpose:</span>
          <span class="font-bold text-indigo-700 text-right" x-text="selectedTxn?.category_label"></span>
        </div>

        <div class="pt-2 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Account / Destination:</span>
          <span class="font-bold text-slate-900 text-right" x-text="selectedTxn?.account_label"></span>
        </div>

        <div class="pt-2 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Payment Method:</span>
          <span class="font-mono font-bold text-slate-800 text-right" x-text="selectedTxn?.payment_mode"></span>
        </div>

        <div class="pt-2 flex justify-between items-center" x-show="selectedTxn?.reference_no && selectedTxn?.reference_no !== '—'">
          <span class="text-slate-500 font-medium">Ref / UTR / Cheque:</span>
          <span class="font-mono font-bold text-slate-800 text-right" x-text="selectedTxn?.reference_no"></span>
        </div>

        <div class="pt-2 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Recorded By:</span>
          <span class="font-medium text-slate-700 text-right" x-text="selectedTxn?.staff_name"></span>
        </div>

        <div class="pt-2 space-y-1" x-show="selectedTxn?.description">
          <span class="text-slate-500 font-medium block">Description &amp; Notes:</span>
          <p class="p-2.5 rounded-lg bg-slate-50 text-slate-700 font-medium text-xs leading-relaxed" x-text="selectedTxn?.description"></p>
        </div>
      </div>

      {{-- Modal Footer --}}
      <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
        <template x-if="selectedTxn?.receipt_url && selectedTxn?.receipt_url !== '#'">
          <a :href="selectedTxn?.receipt_url" target="_blank"
             class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>Official Receipt / Voucher</span>
          </a>
        </template>
        <button type="button" @click="detailModalOpen = false"
                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition ml-auto">
          Close
        </button>
      </div>
    </div>
  </div>

  {{-- ── MODAL 2: Inter-Account Transfer Modal ────────────────────── --}}
  <div x-show="transferModalOpen" x-cloak
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 sm:p-7 space-y-4 shadow-2xl relative border border-slate-200 animate-in fade-in zoom-in duration-150"
         @click.away="transferModalOpen = false">

      <div class="flex items-start justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-base font-bold text-slate-900">Transfer School Funds</h3>
          <p class="text-xs text-slate-500">Move cash between office drawers or deposit to digital UPI</p>
        </div>
        <button type="button" @click="transferModalOpen = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
      </div>

      <form method="POST" action="{{ route('accounts.transfer') }}" class="space-y-4 pt-1">
        @csrf

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Transfer From <span class="text-rose-500">*</span></label>
          <select name="from_account" required class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="cash_box_1">Cash Box 1 (Front Office) — Available: ₹{{ number_format($balances['cash_box_1']['balance'], 2) }}</option>
            <option value="cash_box_2">Cash Box 2 (Accounts Vault) — Available: ₹{{ number_format($balances['cash_box_2']['balance'], 2) }}</option>
            <option value="upi">UPI Digital Account — Available: ₹{{ number_format($balances['upi']['balance'], 2) }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Transfer To <span class="text-rose-500">*</span></label>
          <select name="to_account" required class="w-full h-10 px-3 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="cash_box_2">Cash Box 2 (Accounts Vault)</option>
            <option value="cash_box_1">Cash Box 1 (Front Office)</option>
            <option value="upi">UPI Digital Account</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Amount (₹) <span class="text-rose-500">*</span></label>
            <input type="number" step="0.01" min="1" name="amount" required placeholder="0.00"
                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold font-mono text-slate-900 focus:ring-2 focus:ring-indigo-500 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Transfer Date <span class="text-rose-500">*</span></label>
            <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" required
                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Reference / Slip Number</label>
          <input type="text" name="reference_no" placeholder="e.g. SLIP-2026-081"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500 outline-none font-mono">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Remarks / Reason</label>
          <input type="text" name="remarks" placeholder="e.g. Counter cash clearance to main vault"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
          <button type="button" @click="transferModalOpen = false"
                  class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
            Cancel
          </button>
          <button type="submit"
                  class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition">
            Confirm Transfer
          </button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
