@extends('layouts.app')
@section('title', 'Monthly Collection Report')
@section('content')
@php
  $year = $year ?? $startYear ?? now()->year;
@endphp
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="page-title">Monthly Collection Report</h1>
      <p class="page-subtitle">Month-wise fee collection for {{ $year }}</p>
    </div>
    <a href="{{ route('fees.index') }}" class="btn btn-secondary">Back</a>
  </div>

  <div class="card">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
      <div>
        <label class="label">Year</label>
        <select name="year" class="select">
          @for($y = now()->year; $y >= now()->year - 4; $y--)
          <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
          @endfor
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Load</button>
    </form>
  </div>

  @php
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $maxVal = max($data) ?: 1;
  @endphp

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="card">
      <h2 class="font-semibold text-slate-800 mb-4">Monthly Breakdown</h2>
      <div class="table-wrap">
        <table class="w-full">
          <thead>
            <tr>
              <th class="th">Month</th>
              <th class="th text-right">Collection</th>
              <th class="th">Bar</th>
            </tr>
          </thead>
          <tbody>
            @foreach($months as $i => $month)
            @php $val = $data[$i+1] ?? 0; @endphp
            <tr class="tr">
              <td class="td font-medium">{{ $month }} {{ $year }}</td>
              <td class="td text-right font-semibold">₹{{ number_format($val, 2) }}</td>
              <td class="td w-40">
                <div class="bg-slate-200 rounded-full h-2">
                  <div class="h-2 rounded-full bg-indigo-500" style="width:{{ $maxVal > 0 ? round($val/$maxVal*100) : 0 }}%"></div>
                </div>
              </td>
            </tr>
            @endforeach
            <tr class="tr font-bold">
              <td class="td">Total</td>
              <td class="td text-right text-indigo-700">₹{{ number_format($total, 2) }}</td>
              <td class="td"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2 class="font-semibold text-slate-800 mb-4">Collection Chart</h2>
      <canvas id="monthlyChart" height="300"></canvas>
    </div>
  </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: @json($months),
    datasets: [{
      label: 'Collection (₹)',
      data: @json(array_values($data)),
      backgroundColor: 'rgba(99,102,241,0.7)',
      borderRadius: 4,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
@endpush
@endsection
