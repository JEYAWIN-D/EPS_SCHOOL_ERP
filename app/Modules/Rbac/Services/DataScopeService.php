<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DataScopeService
{
    /**
     * Apply data scope to a Student query builder.
     */
    public static function applyStudentScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        // Check if user has universal 'all' scope
        if ($user->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists()) {
            return $query;
        }

        $userScopes = $user->dataScopes()->where('is_active', true)->get();

        // If no scopes are assigned and user is a regular staff, check their class teacher assignment
        $classIds = [];
        $sectionIds = [];
        $academicYears = [];

        foreach ($userScopes as $scope) {
            if ($scope->scope_type === 'class_id') {
                $classIds = array_merge($classIds, $scope->parsed_values);
            } elseif ($scope->scope_type === 'section_id') {
                $sectionIds = array_merge($sectionIds, $scope->parsed_values);
            } elseif ($scope->scope_type === 'academic_year') {
                $academicYears = array_merge($academicYears, $scope->parsed_values);
            }
        }

        // Check class teacher assignments & subject allocations if employee is linked
        if (empty($classIds) && empty($sectionIds)) {
            $empId = $user->employee_id ?? $user->employee?->id ?? \Illuminate\Support\Facades\DB::table('employees')->where('user_id', $user->id)->value('id');
            if ($empId) {
                $cta = \Illuminate\Support\Facades\DB::table('staff_class_teacher_assignments')
                    ->where('employee_id', $empId)
                    ->where('is_current', true)
                    ->get();
                foreach ($cta as $item) {
                    if ($item->class_id) $classIds[] = $item->class_id;
                    if ($item->section_id) $sectionIds[] = $item->section_id;
                }

                $tsa = \Illuminate\Support\Facades\DB::table('teacher_subject_allocations')
                    ->where('employee_id', $empId)
                    ->get();
                foreach ($tsa as $item) {
                    if ($item->class_id) $classIds[] = $item->class_id;
                    if ($item->section_id) $sectionIds[] = $item->section_id;
                }
            }

            if ($user->hasAnyRole(['teacher', 'class_teacher', 'subject_teacher', 'hod']) && empty($classIds) && empty($sectionIds)) {
                return $query->whereRaw('1 = 0');
            }
        }

        // Apply filters if restrictions found
        if (!empty($classIds)) {
            $query->whereIn('class_id', array_unique($classIds));
        }
        if (!empty($sectionIds)) {
            $query->whereIn('section_id', array_unique($sectionIds));
        }
        if (!empty($academicYears)) {
            $query->whereIn('academic_year_id', array_unique($academicYears));
        }

        return $query;
    }

    /**
     * Apply data scope to an Employee query builder.
     */
    public static function applyEmployeeScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists()) {
            return $query;
        }

        $departments = [];
        foreach ($user->dataScopes()->where('scope_type', 'department')->where('is_active', true)->get() as $scope) {
            $departments = array_merge($departments, $scope->parsed_values);
        }

        if (!empty($departments)) {
            $query->whereIn('department_id', $departments);
        }

        return $query;
    }

    /**
     * Apply transport route scope.
     */
    public static function applyTransportScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists()) {
            return $query;
        }

        $routeIds = [];
        foreach ($user->dataScopes()->where('scope_type', 'route_id')->where('is_active', true)->get() as $scope) {
            $routeIds = array_merge($routeIds, $scope->parsed_values);
        }

        if (!empty($routeIds)) {
            $query->whereIn('id', $routeIds);
        }

        return $query;
    }

    /**
     * Apply warehouse scope.
     */
    public static function applyWarehouseScope(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists()) {
            return $query;
        }

        $warehouseIds = [];
        foreach ($user->dataScopes()->where('scope_type', 'warehouse_id')->where('is_active', true)->get() as $scope) {
            $warehouseIds = array_merge($warehouseIds, $scope->parsed_values);
        }

        if (!empty($warehouseIds)) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        return $query;
    }

    /**
     * Validate if a single record is within user's allowed data scope.
     */
    public static function isRecordInScope(User $user, string $entity, Model $record): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists()) {
            return true;
        }

        switch ($entity) {
            case 'students':
                $classIds = $user->getScopeValues('class_id');
                if ($classIds !== null && !empty($classIds) && !in_array((string)$record->class_id, $classIds)) {
                    return false;
                }
                $sectionIds = $user->getScopeValues('section_id');
                if ($sectionIds !== null && !empty($sectionIds) && !in_array((string)$record->section_id, $sectionIds)) {
                    return false;
                }
                return true;

            case 'employees':
                $deptIds = $user->getScopeValues('department');
                if ($deptIds !== null && !empty($deptIds) && !in_array((string)$record->department_id, $deptIds)) {
                    return false;
                }
                return true;

            case 'transport':
                $routeIds = $user->getScopeValues('route_id');
                if ($routeIds !== null && !empty($routeIds) && !in_array((string)$record->id, $routeIds)) {
                    return false;
                }
                return true;

            case 'inventory':
                $warehouseIds = $user->getScopeValues('warehouse_id');
                if ($warehouseIds !== null && !empty($warehouseIds) && !in_array((string)$record->warehouse_id, $warehouseIds)) {
                    return false;
                }
                return true;

            default:
                return true;
        }
    }
}
