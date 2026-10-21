<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password — Erode Public School ERP</title>
  <link rel="icon" type="image/png" href="{{ asset('images/school-seal-badge.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: radial-gradient(circle at 50% 100px, rgba(140, 40, 38, 0.04) 0%, #f8fafc 55%, #f1f5f9 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
  </style>
</head>
<body class="text-slate-800">

  <!-- Header -->
  <header class="w-full bg-white border-b-2 border-[#8c2826] py-3.5 px-6 shadow-xs flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="{{ asset('images/school-logo.png') }}" alt="Logo" class="w-9 h-9 object-contain">
      <div>
        <h1 class="text-sm font-extrabold text-[#8c2826] tracking-tight">ERODE PUBLIC SCHOOL</h1>
        <p class="text-[11px] text-slate-500 font-medium">Senior Secondary &bull; ERP Access Control</p>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </button>
    </form>
  </header>

  <!-- Main Card -->
  <main class="flex-1 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl border border-slate-200/90 shadow-xl p-6 sm:p-8">
      
      <!-- Security Icon & Badge -->
      <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mb-3.5 shadow-xs">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
          First-Time Login Security Setup
        </span>
        <h2 class="text-xl font-extrabold text-slate-900 mt-2">Change Temporary Password</h2>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">
          Your account was provisioned with a temporary password. To secure your account, please set a new personal password.
        </p>
      </div>

      <!-- User Identification Pill -->
      <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3 mb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-[#8c2826]/10 text-[#8c2826] font-bold text-sm flex items-center justify-center flex-shrink-0">
          {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div class="min-w-0">
          <p class="text-xs font-bold text-slate-900 truncate">{{ $user->name }}</p>
          <p class="text-[11px] text-slate-500 flex items-center gap-1.5 font-mono">
            <span>Username: {{ $user->username ?? $user->email }}</span>
          </p>
        </div>
      </div>

      @if($errors->any())
        <div class="p-3.5 mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
          <p class="font-bold mb-1">Please correct the following:</p>
          <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <!-- Change Form -->
      <form method="POST" action="{{ route('password.force_change.post') }}" class="space-y-4">
        @csrf

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1" for="current_password">
            Temporary / Current Password
          </label>
          <input
            type="password"
            name="current_password"
            id="current_password"
            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-[#8c2826]/20 focus:border-[#8c2826] transition"
            placeholder="Enter the initial temporary password"
            required
          >
          <p class="text-[11px] text-slate-400 mt-1">Hint: Format DDMMYYYY based on Date of Birth</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1" for="password">
            New Secure Password
          </label>
          <input
            type="password"
            name="password"
            id="password"
            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-[#8c2826]/20 focus:border-[#8c2826] transition"
            placeholder="At least 8 characters"
            required
            minlength="8"
          >
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1" for="password_confirmation">
            Confirm New Password
          </label>
          <input
            type="password"
            name="password_confirmation"
            id="password_confirmation"
            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-[#8c2826]/20 focus:border-[#8c2826] transition"
            placeholder="Re-enter your new password"
            required
            minlength="8"
          >
        </div>

        <div class="p-3 bg-amber-50/60 border border-amber-200/60 rounded-xl text-[11px] text-amber-800 space-y-1">
          <p class="font-bold flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Password Guidelines:
          </p>
          <ul class="list-disc list-inside space-y-0.5 text-amber-700">
            <li>Minimum 8 characters</li>
            <li>Must be different from your temporary password</li>
            <li>Never share your credentials with colleagues</li>
          </ul>
        </div>

        <button
          type="submit"
          class="w-full py-2.5 px-4 rounded-xl bg-[#8c2826] hover:bg-[#72201e] text-white font-bold text-sm shadow-md shadow-[#8c2826]/20 transition flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Set Password & Access Portal
        </button>
      </form>

    </div>
  </main>

  <footer class="py-3 text-center text-xs text-slate-400">
    &copy; {{ date('Y') }} Erode Public School Senior Secondary. All rights reserved.
  </footer>

</body>
</html>
