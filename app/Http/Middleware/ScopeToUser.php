<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Injects data-scope attributes into the request for non-admin roles.
 * Controllers use these to filter their queries.
 *
 * Sets on request:
 *   scope_class_ids    — array of class IDs the user may access (teachers)
 *   scope_section_ids  — array of section IDs the user may access (teachers)
 *   scope_student_ids  — array of student IDs the user may access (parents)
 *   scope_student_id   — single student ID (student portal users)
 *   scope_employee_id  — the user's own employee ID (staff members)
 *   is_scoped          — true when any scope constraint is active
 */
class ScopeToUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Full-access roles — no scoping
        if ($user->hasAnyRole(['super_admin', 'admin', 'owner', 'principal', 'vice_principal'])) {
            return $next($request);
        }

        $scope = [
            'scope_class_ids'   => [],
            'scope_section_ids' => [],
            'scope_student_ids' => [],
            'scope_student_id'  => null,
            'scope_employee_id' => $user->employee_id,
            'is_scoped'         => false,
        ];

        // ── Teachers: scope to their assigned classes/sections ─────
        if ($user->hasAnyRole(['class_teacher', 'subject_teacher', 'teacher', 'hod'])) {
            $scope['is_scoped'] = true;
            $empId = $user->employee_id ?? $user->employee?->id ?? DB::table('employees')->where('user_id', $user->id)->value('id');

            if ($empId) {
                $scope['scope_employee_id'] = $empId;
                $currentYearId = \App\Models\AcademicYear::current()?->id;

                // 1. Teacher subject allocations
                $subjectRows = DB::table('teacher_subject_allocations')
                    ->where('employee_id', $empId)
                    ->when($currentYearId, fn($q) => $q->where(function($sq) use ($currentYearId) {
                        $sq->where('academic_year_id', $currentYearId)
                           ->orWhereNull('academic_year_id');
                    }))
                    ->get(['class_id', 'section_id']);

                // 2. Class teacher assignments
                $ctaRows = DB::table('staff_class_teacher_assignments')
                    ->where('employee_id', $empId)
                    ->where('is_current', true)
                    ->when($currentYearId, fn($q) => $q->where(function($sq) use ($currentYearId) {
                        $sq->where('academic_year_id', $currentYearId)
                           ->orWhereNull('academic_year_id');
                    }))
                    ->get(['class_id', 'section_id']);

                $allClassIds = $subjectRows->pluck('class_id')
                    ->concat($ctaRows->pluck('class_id'))
                    ->unique()->filter()->values()->toArray();

                $allSectionIds = $subjectRows->pluck('section_id')
                    ->concat($ctaRows->pluck('section_id'))
                    ->unique()->filter()->values()->toArray();

                $scope['scope_class_ids']   = $allClassIds;
                $scope['scope_section_ids'] = $allSectionIds;
                $scope['scope_class_teacher_class_ids']   = $ctaRows->pluck('class_id')->unique()->filter()->values()->toArray();
                $scope['scope_class_teacher_section_ids'] = $ctaRows->pluck('section_id')->unique()->filter()->values()->toArray();
            }
        }

        // ── Parent: scope to their children ───────────────────────
        if ($user->hasRole('parent')) {
            $scope['scope_student_ids'] = DB::table('parent_students')
                ->where('parent_user_id', $user->id)
                ->pluck('student_id')
                ->toArray();
            $scope['is_scoped'] = true;
        }

        // ── Student: scope to themselves ──────────────────────────
        if ($user->hasRole('student') && $user->student_id) {
            $scope['scope_student_id']  = $user->student_id;
            $scope['scope_student_ids'] = [$user->student_id];
            $scope['is_scoped']         = true;
        }

        $request->merge($scope);

        return $next($request);
    }
}
