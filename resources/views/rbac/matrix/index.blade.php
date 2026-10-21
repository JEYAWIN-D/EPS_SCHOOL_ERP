@extends('layouts.app')

@section('title', 'Role & Permission Matrix — RBAC')

@section('content')
<div class="space-y-6" x-data="permissionMatrixApp()">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
        <span>Access Control</span>
        <span>/</span>
        <span class="text-indigo-600">Permission Matrix</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Granular Permission Matrix</h1>
      <p class="text-sm text-slate-500 mt-0.5">Configure action-level capabilities and operational boundaries across all School ERP modules.</p>
    </div>

    {{-- Actions & Copy Bar --}}
    <div class="flex items-center gap-2.5 flex-wrap">
      {{-- Copy Permissions Button --}}
      <button type="button" @click="showCopyModal = true" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
        Copy From Another Role
      </button>

      {{-- Select All / Clear All --}}
      <button type="button" @click="selectAll()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
        Select All
      </button>
      <button type="button" @click="clearAll()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
        Clear All
      </button>

      {{-- Save Button --}}
      <button type="button" @click="submitMatrix()" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Save Matrix Changes
        <span x-show="unsavedCount > 0" class="ml-1 px-1.5 py-0.5 rounded-full bg-indigo-800 text-[10px] text-white" x-text="unsavedCount"></span>
      </button>
    </div>
  </div>

  {{-- Role Selector Bar & Search Bar --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      {{-- Role Pills --}}
      <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wide mr-1 flex-shrink-0">Role:</span>
        @foreach($roles as $role)
          <a href="{{ route('rbac.matrix.index', ['role_id' => $role->id]) }}"
             class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition flex-shrink-0 {{ $selectedRole && $selectedRole->id === $role->id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            {{ ucwords(str_replace('_', ' ', $role->name)) }}
            @if($role->name === 'super_admin')
              <span class="ml-1 text-[10px] opacity-75">★</span>
            @endif
          </a>
        @endforeach
      </div>

      {{-- Search Module Input --}}
      <div class="w-full lg:w-72 relative flex-shrink-0">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="search" placeholder="Search module or action..." class="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
      </div>
    </div>
  </div>

  {{-- Super Admin Notice if selected --}}
  @if($selectedRole && $selectedRole->name === 'super_admin')
    <div class="p-4 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 flex items-start gap-3">
      <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      <div class="text-xs">
        <p class="font-bold text-purple-950">Super Administrator possesses Universal Execution Privileges</p>
        <p class="mt-0.5 text-purple-800">By design, Super Admin bypasses individual permission gates and has full read, write, execution, and security administration privileges across the entire ERP.</p>
      </div>
    </div>
  @endif

  {{-- The Interactive Permission Matrix Form --}}
  <form id="matrixForm" method="POST" action="{{ route('rbac.matrix.update', $selectedRole->id ?? 1) }}">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-800 text-white font-bold uppercase tracking-wider text-[11px] sticky top-0 z-10">
              <th class="py-3 px-4 min-w-[220px]">Module & Submodules</th>
              <th class="py-3 px-3 text-center">View</th>
              <th class="py-3 px-3 text-center">Create</th>
              <th class="py-3 px-3 text-center">Edit</th>
              <th class="py-3 px-3 text-center">Delete</th>
              <th class="py-3 px-3 text-center">Submit</th>
              <th class="py-3 px-3 text-center">Approve</th>
              <th class="py-3 px-3 text-center">Verify</th>
              <th class="py-3 px-3 text-center">Assign</th>
              <th class="py-3 px-3 text-center">Print</th>
              <th class="py-3 px-3 text-center">Export</th>
              <th class="py-3 px-3 text-center">Reports</th>
              <th class="py-3 px-3 text-center">Config</th>
              <th class="py-3 px-4 text-center">Quick Select</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @php
              $matrixActions = ['view', 'create', 'edit', 'delete', 'submit', 'approve', 'verify', 'assign', 'print', 'export', 'view_reports', 'configure'];
            @endphp

            @foreach($moduleCatalog as $modKey => $modData)
              <tr class="hover:bg-slate-50/80 transition" x-show="matchesSearch('{{ strtolower($modData['name']) }} {{ strtolower($modData['description']) }}')">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <span>{{ $modData['name'] }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $modData['description'] }}</p>
                  {{-- Submodules pills --}}
                  <div class="flex flex-wrap gap-1 mt-1.5">
                    @foreach($modData['submodules'] as $subKey => $subTitle)
                      <span class="text-[9.5px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                        {{ $subTitle }}
                      </span>
                    @endforeach
                  </div>
                </td>

                @foreach($matrixActions as $act)
                  @php
                    $isSupported = in_array($act, $modData['default_actions'], true);
                    $permName = "{$modKey}:{$act}";
                    $legacyName = "{$act} {$modKey}";
                    $legacyManage = "manage {$modKey}";
                    $isChecked = in_array($permName, $selectedRolePermissions, true) || in_array($legacyName, $selectedRolePermissions, true) || in_array($legacyManage, $selectedRolePermissions, true);
                  @endphp
                  <td class="py-3 px-3 text-center align-middle">
                    @if($isSupported)
                      <input type="checkbox"
                             name="permissions[]"
                             value="{{ $permName }}"
                             {{ $isChecked ? 'checked' : '' }}
                             @change="trackChange()"
                             class="perm-checkbox w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer transition">
                    @else
                      <span class="text-slate-200 select-none">&mdash;</span>
                    @endif
                  </td>
                @endforeach

                <td class="py-3 px-4 text-center align-middle whitespace-nowrap">
                  <button type="button" @click="toggleRow($event)" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded bg-indigo-50 hover:bg-indigo-100 transition">
                    Row All
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

  </form>

  {{-- Modal: Copy Permissions from another role --}}
  <div x-show="showCopyModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display: none;" @keydown.escape.window="showCopyModal = false">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.outside="showCopyModal = false">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h3 class="text-base font-bold text-slate-900">Copy Permissions Template</h3>
        <button type="button" @click="showCopyModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
      </div>

      <p class="text-xs text-slate-600">
        Copying permissions will overwrite the current permissions of <strong>{{ ucwords(str_replace('_', ' ', $selectedRole->name ?? '')) }}</strong> with the permissions of the selected role.
      </p>

      <form method="POST" action="{{ route('rbac.matrix.copy', $selectedRole->id ?? 1) }}">
        @csrf
        <div class="space-y-3">
          <label class="block text-xs font-bold text-slate-700 uppercase">Select Source Role</label>
          <select name="source_role_id" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg">
            @foreach($roles as $r)
              @if($selectedRole && $r->id !== $selectedRole->id)
                <option value="{{ $r->id }}">{{ ucwords(str_replace('_', ' ', $r->name)) }} ({{ $r->permissions->count() }} permissions)</option>
              @endif
            @endforeach
          </select>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 mt-4">
          <button type="button" @click="showCopyModal = false" class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 transition">Cancel</button>
          <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition">Copy & Replace</button>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
function permissionMatrixApp() {
  return {
    search: '',
    showCopyModal: false,
    unsavedCount: 0,

    matchesSearch(text) {
      if (!this.search) return true;
      return text.includes(this.search.toLowerCase());
    },

    trackChange() {
      this.unsavedCount++;
    },

    selectAll() {
      document.querySelectorAll('.perm-checkbox').forEach(cb => {
        if (!cb.checked) {
          cb.checked = true;
          this.unsavedCount++;
        }
      });
    },

    clearAll() {
      if (confirm('Clear all selected permissions for this role?')) {
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
          if (cb.checked) {
            cb.checked = false;
            this.unsavedCount++;
          }
        });
      }
    },

    toggleRow(e) {
      const row = e.target.closest('tr');
      const checkboxes = row.querySelectorAll('.perm-checkbox');
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      checkboxes.forEach(cb => {
        cb.checked = !allChecked;
      });
      this.unsavedCount++;
    },

    submitMatrix() {
      document.getElementById('matrixForm').submit();
    }
  }
}
</script>
@endsection
