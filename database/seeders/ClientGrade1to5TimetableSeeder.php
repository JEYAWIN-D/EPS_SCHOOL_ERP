<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\User;
use App\Http\Controllers\Admin\ClassesController;
use Illuminate\Support\Facades\Cache;

class ClientGrade1to5TimetableSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::firstOrCreate(
                ['name' => '2025-2026'],
                ['start_date' => '2025-06-01', 'end_date' => '2026-04-30', 'is_current' => true]
            );

        // 1. Ensure required subject catalog exists with accurate client names & codes
        $subjectsConfig = [
            ['name' => 'Tamil',                        'code' => 'TAM',      'type' => 'language', 'medium' => 'Tamil',   'stream' => 'primary'],
            ['name' => 'English',                      'code' => 'ENG',      'type' => 'language', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Mathematics',                  'code' => 'MAT',      'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Environmental Studies (EVS)',  'code' => 'EVS',      'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Science',                      'code' => 'SCI',      'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Social Science',               'code' => 'SOC',      'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Hindi',                        'code' => 'HIN',      'type' => 'language', 'medium' => 'Hindi',   'stream' => 'primary'],
            ['name' => 'Computer Science',             'code' => 'CS',       'type' => 'practical','medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Computer Science (Lab)',       'code' => 'CS-LAB',   'type' => 'practical','medium' => 'English', 'stream' => 'primary'],
            ['name' => 'General Knowledge',            'code' => 'GK',       'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Library',                      'code' => 'LIB',      'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Physical Education (PET)',     'code' => 'PET',      'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Swimming',                     'code' => 'SWIMMING', 'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Yoga',                         'code' => 'YOGA',     'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Extra Curricular Activities',  'code' => 'ECA',      'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Drawing',                      'code' => 'DRAWING',  'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'EKSAQ Activity',               'code' => 'EKSAQ',    'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Diary & Wrap-up',              'code' => 'DIARY',    'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
        ];

        $subjectMap = [];
        foreach ($subjectsConfig as $sc) {
            $s = Subject::updateOrCreate(
                ['code' => $sc['code']],
                [
                    'name'      => $sc['name'],
                    'type'      => $sc['type'],
                    'medium'    => $sc['medium'],
                    'stream'    => $sc['stream'],
                    'is_active' => true,
                ]
            );
            $subjectMap[$sc['code']] = $s;
        }

        // Map abbreviations from document to subject codes
        $abbrToCode = [
            'EVS'       => 'EVS',
            'LIB'       => 'LIB',
            'MAT'       => 'MAT',
            'MATHS'     => 'MAT',
            'MT'        => 'MAT',
            'ENG'       => 'ENG',
            'TAM'       => 'TAM',
            'HIN'       => 'HIN',
            'SWIMMING'  => 'SWIMMING',
            'C.S'       => 'CS',
            'CS'        => 'CS',
            'C.S(L)'    => 'CS-LAB',
            'YOGA'      => 'YOGA',
            'PET'       => 'PET',
            'ECA'       => 'ECA',
            'G.K'       => 'GK',
            'GK'        => 'GK',
            'DRAWING'   => 'DRAWING',
            'EKSAQ'     => 'EKSAQ',
            'SOC'       => 'SOC',
            'SCI'       => 'SCI',
            'DIARY'     => 'DIARY',
        ];

        // 2. Map faculty names to employee records
        $facultyCache = [];
        $facultyKeywords = [
            'Lavanya'         => 'Lavanya',
            'Rasigapriya'     => 'Rasigapriya',
            'Sneha'           => 'Sneha',
            'Balaharini'      => 'Balaharini',
            'Yasodha'         => 'Yasodha',
            'Saranya'         => 'Saranya',
            'Haripriya'       => 'Haripriya',
            'Mohanasundari'   => 'Mohanasundari',
            'Kavitha'         => 'Kavitha',
            'Geethabarathi'   => 'Geethabarathi',
            'Valarmathi'      => 'Valarmathi',
            'Gophika'         => 'Gophika',
            'Rithika Sree'    => 'Rithika',
            'Shobika Barathi' => 'Shobika',
            'Manju'           => 'Manju',
            'Meenakshi'       => 'Meenakshi',
            'Natarajan'       => 'Natarajan',
        ];

        foreach ($facultyKeywords as $key => $kw) {
            $emp = Employee::where('first_name', 'ilike', "%{$kw}%")
                ->orWhere('last_name', 'ilike', "%{$kw}%")
                ->first();
            if (!$emp) {
                $code = 'EMP-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $key), 0, 3)) . rand(100, 999);
                $emp = Employee::create([
                    'employee_code'   => $code,
                    'first_name'      => $key,
                    'last_name'       => '',
                    'gender'          => 'female',
                    'designation'     => 'Teacher',
                    'department'      => 'Academics',
                    'status'          => 'active',
                    'is_active'       => true,
                    'employment_type' => 'permanent',
                    'joining_date'    => '2025-06-01',
                ]);
            }
            $facultyCache[$key] = $emp;
        }

        // 3. Define accurate Class & Section configuration for Grades 1 to 5
        // Document source of truth:
        // Grade I: A, B, C
        // Grade II: A, B
        // Grade III: A, B, C
        // Grade IV: A, B
        // Grade V: A, B
        $classConfigs = [
            1 => [
                'name' => 'I',
                'active_sections' => [
                    'A' => 'Rasigapriya',
                    'B' => 'Sneha',
                    'C' => 'Balaharini',
                ]
            ],
            2 => [
                'name' => 'II',
                'active_sections' => [
                    'A' => 'Yasodha',
                    'B' => 'Saranya',
                ]
            ],
            3 => [
                'name' => 'III',
                'active_sections' => [
                    'A' => 'Haripriya',
                    'B' => 'Mohanasundari',
                    'C' => 'Kavitha',
                ]
            ],
            4 => [
                'name' => 'IV',
                'active_sections' => [
                    'A' => 'Geethabarathi',
                    'B' => 'Valarmathi',
                ]
            ],
            5 => [
                'name' => 'V',
                'active_sections' => [
                    'A' => 'Gophika',
                    'B' => 'Rithika Sree',
                ]
            ],
        ];

        $sectionModelMap = [];
        $gradeClassMap = [];

        foreach ($classConfigs as $numVal => $cfg) {
            $cls = Classes::where('numeric_value', $numVal)->first();
            if (!$cls) {
                $cls = Classes::firstOrCreate(
                    ['name' => $cfg['name']],
                    ['numeric_value' => $numVal, 'sort_order' => $numVal + 3, 'is_active' => true]
                );
            }
            $gradeClassMap[$numVal] = $cls;

            // Helper to get or create User for Class Teacher
            $getCtUserId = function(?Employee $ctEmp, string $ctName): ?int {
                if (!$ctEmp) return null;
                $u = User::where('employee_id', $ctEmp->id)->first();
                if (!$u) {
                    $emailName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ctName));
                    $u = User::firstOrCreate(
                        ['email' => $emailName . '@schoolerp.in'],
                        [
                            'name'         => $ctEmp->first_name . ($ctEmp->last_name ? ' ' . $ctEmp->last_name : ''),
                            'password'     => bcrypt('Demo@2026'),
                            'employee_id'  => $ctEmp->id,
                            'is_active'    => true,
                        ]
                    );
                    if ($u->employee_id !== $ctEmp->id) {
                        $u->update(['employee_id' => $ctEmp->id]);
                    }
                    if (!$u->hasRole('Teacher')) {
                        try { $u->assignRole('Teacher'); } catch (\Throwable $e) {}
                    }
                }
                return $u->id;
            };

            // Update sections
            $allSectionsForClass = Section::where('class_id', $cls->id)->get();
            foreach ($allSectionsForClass as $sec) {
                if (isset($cfg['active_sections'][$sec->name])) {
                    $ctName = $cfg['active_sections'][$sec->name];
                    $ctEmp = $facultyCache[$ctName] ?? null;
                    $ctUserId = $getCtUserId($ctEmp, $ctName);
                    $sec->update([
                        'is_active'        => true,
                        'class_teacher_id' => $ctUserId,
                    ]);
                    $sectionModelMap["{$numVal}-{$sec->name}"] = $sec;
                } else {
                    // Unused section in Grades 1-5 (e.g. D or C where absent from docx)
                    $sec->update(['is_active' => false]);
                }
            }

            // Ensure active sections are created if they didn't exist
            foreach ($cfg['active_sections'] as $secName => $ctName) {
                $key = "{$numVal}-{$secName}";
                if (!isset($sectionModelMap[$key])) {
                    $ctEmp = $facultyCache[$ctName] ?? null;
                    $ctUserId = $getCtUserId($ctEmp, $ctName);
                    $sec = Section::create([
                        'class_id'         => $cls->id,
                        'name'             => $secName,
                        'academic_year_id' => $currentYear->id,
                        'capacity'         => 40,
                        'is_active'        => true,
                        'class_teacher_id' => $ctUserId,
                    ]);
                    $sectionModelMap[$key] = $sec;
                }
            }
        }

        // 4. Exact Client Timetable Schedules (Monday to Saturday, Periods 1 to 8)
        $scheduleData = [
            '1-A' => [
                'ct' => 'Rasigapriya',
                'days' => [
                    1 => ['EVS', 'LIB', 'MAT', 'ENG', 'TAM', 'EVS', 'HIN', 'DIARY'],
                    2 => ['EVS', 'TAM', 'ENG', 'SWIMMING', 'SWIMMING', 'MAT', 'C.S', 'DIARY'],
                    3 => ['EVS', 'HIN', 'MAT', 'ENG', 'TAM', 'C.S', 'MAT', 'DIARY'],
                    4 => ['EVS', 'ENG', 'TAM', 'YOGA', 'ENG', 'MAT', 'PET', 'DIARY'],
                    5 => ['EVS', 'HIN', 'MAT', 'C.S', 'ENG', 'ECA', 'TAM', 'DIARY'],
                    6 => ['EVS', 'TAM', 'ENG', 'MAT', 'PET', 'TAM', 'G.K', 'DIARY'],
                ]
            ],
            '1-B' => [
                'ct' => 'Sneha',
                'days' => [
                    1 => ['MAT', 'TAM', 'EVS', 'LIB', 'HIN', 'MAT', 'ENG', 'DIARY'],
                    2 => ['MAT', 'ENG', 'TAM', 'SWIMMING', 'SWIMMING', 'EVS', 'PET', 'DIARY'],
                    3 => ['YOGA', 'C.S', 'EVS', 'HIN', 'ENG', 'EVS', 'TAM', 'DIARY'],
                    4 => ['MAT', 'TAM', 'C.S', 'MAT', 'ENG', 'EVS', 'TAM', 'DIARY'],
                    5 => ['MAT', 'TAM', 'EVS', 'ENG', 'HIN', 'ECA', 'PET', 'DIARY'],
                    6 => ['MAT', 'ENG', 'C.S', 'ENG', 'G.K', 'EVS', 'TAM', 'DIARY'],
                ]
            ],
            '1-C' => [
                'ct' => 'Balaharini',
                'days' => [
                    1 => ['TAM', 'MAT', 'TAM', 'EVS', 'ENG', 'HIN', 'MAT', 'DIARY'],
                    2 => ['YOGA', 'EVS', 'MAT', 'SWIMMING', 'SWIMMING', 'ENG', 'TAM', 'DIARY'],
                    3 => ['TAM', 'ENG', 'HIN', 'EVS', 'MAT', 'G.K', 'LIB', 'DIARY'],
                    4 => ['TAM', 'EVS', 'MAT', 'C.S', 'ENG', 'PET', 'EVS', 'DIARY'],
                    5 => ['ENG', 'MAT', 'HIN', 'TAM', 'EVS', 'ECA', 'C.S', 'DIARY'],
                    6 => ['ENG', 'EVS', 'MAT', 'TAM', 'C.S', 'ENG', 'PET', 'DIARY'],
                ]
            ],
            '2-A' => [
                'ct' => 'Yasodha',
                'days' => [
                    1 => ['ENG', 'TAM', 'YOGA', 'MAT', 'C.S', 'ENG', 'EVS', 'DIARY'],
                    2 => ['TAM', 'HIN', 'EVS', 'C.S', 'G.K', 'TAM', 'MAT', 'DIARY'],
                    3 => ['ENG', 'MAT', 'ENG', 'TAM', 'EVS', 'MAT', 'PET', 'DIARY'],
                    4 => ['ENG', 'MAT', 'HIN', 'EVS', 'TAM', 'SWIMMING', 'SWIMMING', 'DIARY'],
                    5 => ['ENG', 'EVS', 'TAM', 'LIB', 'ECA', 'HIN', 'EVS', 'DIARY'],
                    6 => ['ENG', 'MAT', 'PET', 'EVS', 'TAM', 'MAT', 'C.S(L)', 'DIARY'],
                ]
            ],
            '2-B' => [
                'ct' => 'Saranya',
                'days' => [
                    1 => ['HIN', 'EVS', 'ENG', 'TAM', 'MAT', 'C.S', 'YOGA', 'DIARY'],
                    2 => ['EVS', 'MAT', 'TAM', 'ENG', 'PET', 'ENG', 'EVS', 'DIARY'],
                    3 => ['HIN', 'ENG', 'MAT', 'EVS', 'TAM', 'PET', 'ENG', 'DIARY'],
                    4 => ['EVS', 'ENG', 'EVS', 'MAT', 'C.S', 'SWIMMING', 'SWIMMING', 'DIARY'],
                    5 => ['HIN', 'LIB', 'MAT', 'EVS', 'ECA', 'ENG', 'TAM', 'DIARY'],
                    6 => ['TAM', 'TAM', 'EVS', 'MAT', 'ENG', 'C.S(L)', 'G.K', 'DIARY'],
                ]
            ],
            '3-A' => [
                'ct' => 'Haripriya',
                'days' => [
                    1 => ['ENG', 'MAT', 'C.S', 'EVS', 'ENG', 'LIB', 'TAM', 'DIARY'],
                    2 => ['HIN', 'ENG', 'MAT', 'TAM', 'EVS', 'EKSAQ', 'PET', 'DIARY'],
                    3 => ['ENG', 'YOGA', 'TAM', 'MAT', 'C.S', 'TAM', 'EVS', 'DIARY'],
                    4 => ['HIN', 'EVS', 'MAT', 'ENG', 'EVS', 'TAM', 'MAT', 'DIARY'],
                    5 => ['EVS', 'TAM', 'ECA', 'MAT', 'C.S(L)', 'ENG', 'PET', 'DIARY'],
                    6 => ['HIN', 'EVS', 'MAT', 'G.K', 'DRAWING', 'TAM', 'ENG', 'DIARY'],
                ]
            ],
            '3-B' => [
                'ct' => 'Mohanasundari',
                'days' => [
                    1 => ['MAT', 'C.S', 'DRAWING', 'ENG', 'TAM', 'EVS', 'PET', 'DIARY'],
                    2 => ['MAT', 'TAM', 'HIN', 'ENG', 'MAT', 'LIB', 'EVS', 'DIARY'],
                    3 => ['C.S(L)', 'ENG', 'YOGA', 'EVS', 'ENG', 'MAT', 'TAM', 'DIARY'],
                    4 => ['MAT', 'EKSAQ', 'EVS', 'EVS', 'HIN', 'ENG', 'TAM', 'DIARY'],
                    5 => ['MAT', 'C.S', 'ECA', 'EVS', 'TAM', 'TAM', 'ENG', 'DIARY'],
                    6 => ['MAT', 'ENG', 'EVS', 'PET', 'HIN', 'G.K', 'TAM', 'DIARY'],
                ]
            ],
            '3-C' => [
                'ct' => 'Kavitha',
                'days' => [
                    1 => ['TAM', 'EKSAQ', 'G.K', 'C.S', 'MAT', 'ENG', 'EVS', 'DIARY'],
                    2 => ['TAM', 'C.S', 'EVS', 'TAM', 'ENG', 'PET', 'HIN', 'DIARY'],
                    3 => ['TAM', 'MAT', 'LIB', 'ENG', 'MAT', 'ENG', 'EVS', 'DIARY'],
                    4 => ['YOGA', 'MAT', 'TAM', 'EVS', 'ENG', 'PET', 'HIN', 'DIARY'],
                    5 => ['C.S(L)', 'TAM', 'ECA', 'ENG', 'EVS', 'MAT', 'MAT', 'DIARY'],
                    6 => ['TAM', 'HIN', 'ENG', 'EVS', 'MAT', 'DRAWING', 'EVS', 'DIARY'],
                ]
            ],
            '4-A' => [
                'ct' => 'Geethabarathi',
                'days' => [
                    1 => ['YOGA', 'HIN', 'MAT', 'ENG', 'TAM', 'PET', 'SCI', 'DIARY'],
                    2 => ['G.K', 'TAM', 'EKSAQ', 'SCI', 'MAT', 'SOC', 'ENG', 'DIARY'],
                    3 => ['SOC', 'TAM', 'SCI', 'MAT', 'ENG', 'HIN', 'C.S', 'DIARY'],
                    4 => ['SOC', 'TAM', 'ENG', 'MAT', 'PET', 'SCI', 'LIB', 'DIARY'],
                    5 => ['ENG', 'SOC', 'TAM', 'SCI', 'SOC', 'MAT', 'ECA', 'DIARY'],
                    6 => ['C.S(L)', 'SCI', 'HIN', 'TAM', 'SOC', 'MAT', 'ENG', 'DIARY'],
                ]
            ],
            '4-B' => [
                'ct' => 'Valarmathi',
                'days' => [
                    1 => ['C.S(L)', 'SCI', 'SOC', 'MAT', 'SOC', 'ENG', 'TAM', 'DIARY'],
                    2 => ['G.K', 'ENG', 'SOC', 'SOC', 'SCI', 'TAM', 'MAT', 'DIARY'],
                    3 => ['LIB', 'SOC', 'MAT', 'SCI', 'SOC', 'ENG', 'HIN', 'DIARY'],
                    4 => ['MAT', 'HIN', 'SCI', 'TAM', 'YOGA', 'EKSAQ', 'PET', 'DIARY'],
                    5 => ['TAM', 'ENG', 'SCI', 'MAT', 'TAM', 'C.S', 'ECA', 'DIARY'],
                    6 => ['SOC', 'PET', 'TAM', 'SCI', 'MAT', 'ENG', 'HIN', 'DIARY'],
                ]
            ],
            '5-A' => [
                'ct' => 'Gophika',
                'days' => [
                    1 => ['MAT', 'ENG', 'SOC', 'SCI', 'G.K', 'TAM', 'ENG', 'DIARY'],
                    2 => ['LIB', 'SCI', 'SOC', 'HIN', 'TAM', 'MAT', 'YOGA', 'DIARY'],
                    3 => ['MAT', 'EKSAQ', 'SOC', 'ENG', 'PET', 'TAM', 'SCI', 'DIARY'],
                    4 => ['C.S', 'SCI', 'SOC', 'HIN', 'TAM', 'ENG', 'MAT', 'DIARY'],
                    5 => ['TAM', 'MAT', 'SOC', 'HIN', 'PET', 'SCI', 'ECA', 'DIARY'],
                    6 => ['SOC', 'ENG', 'SCI', 'C.S(L)', 'ENG', 'TAM', 'MAT', 'DIARY'],
                ]
            ],
            '5-B' => [
                'ct' => 'Rithika Sree',
                'days' => [
                    1 => ['ENG', 'TAM', 'SOC', 'HIN', 'SCI', 'MAT', 'G.K', 'DIARY'],
                    2 => ['ENG', 'LIB', 'SOC', 'MAT', 'HIN', 'SCI', 'TAM', 'DIARY'],
                    3 => ['ENG', 'MAT', 'SOC', 'C.S', 'SCI', 'YOGA', 'TAM', 'DIARY'],
                    4 => ['ENG', 'MAT', 'SOC', 'EKSAQ', 'SCI', 'C.S(L)', 'TAM', 'DIARY'],
                    5 => ['TAM', 'PET', 'SOC', 'ENG', 'SCI', 'MAT', 'ECA', 'DIARY'],
                    6 => ['ENG', 'TAM', 'SOC', 'MAT', 'SCI', 'HIN', 'PET', 'DIARY'],
                ]
            ],
        ];

        // 5. Client Period Timings
        $clientPeriodTimings = [
            1 => ['09:10:00', '09:50:00'],
            2 => ['09:50:00', '10:30:00'],
            3 => ['10:40:00', '11:20:00'],
            4 => ['11:20:00', '12:00:00'],
            5 => ['12:00:00', '12:40:00'],
            6 => ['13:20:00', '14:00:00'],
            7 => ['14:10:00', '14:50:00'],
            8 => ['14:50:00', '15:00:00'], // Diary
        ];

        // Helper to determine teacher name for each slot based on client cross-reference
        $getTeacherName = function ($classKey, $day, $period, $subject, $ctName) {
            switch ($subject) {
                case 'HIN':     return 'Saranya';
                case 'SCI':     return 'Shobika Barathi';
                case 'YOGA':    return 'Manju';
                case 'PET':     return 'Natarajan';
                case 'LIB':     return 'Meenakshi';
                case 'C.S':
                case 'CS':
                case 'C.S(L)':
                case 'CS-LAB':  return 'Valarmathi';
                case 'DIARY':   return $ctName;
                case 'DRAWING': return 'Meenakshi';
            }

            if ($subject === 'SWIMMING') {
                if ($classKey === '1-A') return 'Rasigapriya';
                if ($classKey === '1-B') return 'Sneha';
                if ($classKey === '1-C') return 'Balaharini';
                if ($classKey === '2-A') return 'Yasodha';
                if ($classKey === '2-B') return 'Geethabarathi';
                return $ctName;
            }

            if ($subject === 'TAM') {
                if (in_array($classKey, ['1-A', '1-B'])) return 'Manju';
                if (in_array($classKey, ['1-C', '2-A', '2-B', '3-A', '3-B'])) return 'Lavanya';
                if (in_array($classKey, ['3-C', '4-A', '4-B', '5-A', '5-B'])) return 'Kavitha';
            }

            if ($subject === 'EVS') {
                if (in_array($classKey, ['1-A', '1-B', '1-C', '2-A'])) return 'Rasigapriya';
                if ($classKey === '2-B') return 'Balaharini';
                if (in_array($classKey, ['3-A', '3-B'])) return 'Geethabarathi';
                if ($classKey === '3-C') return 'Balaharini';
            }

            if ($subject === 'MAT') {
                if (in_array($classKey, ['1-A', '1-B', '1-C', '2-A'])) return 'Sneha';
                if (in_array($classKey, ['2-B', '3-A', '3-B', '3-C'])) return 'Mohanasundari';
                if (in_array($classKey, ['4-A', '4-B', '5-A', '5-B'])) return 'Rithika Sree';
            }

            if ($subject === 'ENG') {
                if (in_array($classKey, ['1-A', '1-B', '2-A', '2-B'])) return 'Yasodha';
                if (in_array($classKey, ['3-A', '3-B', '3-C'])) return 'Haripriya';
                if (in_array($classKey, ['4-A', '4-B', '5-A', '5-B'])) return 'Gophika';
            }

            if ($subject === 'SOC') {
                if (in_array($classKey, ['4-A', '4-B'])) return 'Geethabarathi';
                if (in_array($classKey, ['5-A', '5-B'])) return 'Haripriya';
            }

            if ($subject === 'G.K' || $subject === 'GK') {
                if ($classKey === '1-A') return 'Rasigapriya';
                if ($classKey === '1-B') return 'Sneha';
                if ($classKey === '1-C') return 'Balaharini';
                if ($classKey === '2-A') return 'Yasodha';
                if ($classKey === '2-B') return 'Yasodha';
                if ($classKey === '3-A') return 'Haripriya';
                if ($classKey === '3-B') return 'Mohanasundari';
                if ($classKey === '3-C') return 'Balaharini';
                if ($classKey === '4-A') return 'Geethabarathi';
                if ($classKey === '4-B') return 'Valarmathi';
                if ($classKey === '5-A') return 'Gophika';
                if ($classKey === '5-B') return 'Rithika Sree';
            }

            if ($subject === 'ECA') {
                if ($classKey === '1-A') return 'Gophika';
                if ($classKey === '1-B') return 'Geethabarathi';
                if ($classKey === '1-C') return 'Balaharini';
                if ($classKey === '2-A') return 'Rithika Sree';
                if ($classKey === '2-B') return 'Balaharini';
                if ($classKey === '3-A') return 'Haripriya';
                if ($classKey === '3-B') return 'Geethabarathi';
                if ($classKey === '3-C') return 'Balaharini';
                if ($classKey === '4-A') return 'Geethabarathi';
                if ($classKey === '4-B') return 'Balaharini';
                if ($classKey === '5-A') return 'Gophika';
                if ($classKey === '5-B') return 'Rithika Sree';
            }

            if ($subject === 'EKSAQ') {
                return 'Geethabarathi';
            }

            return $ctName;
        };

        // 6. Delete ONLY existing timetables for Grades 1 to 5
        $gradeClassIds = collect($gradeClassMap)->pluck('id')->all();
        Timetable::whereIn('class_id', $gradeClassIds)->delete();

        // 7. Insert the exact client timetable records
        $now = now();
        $records = [];

        foreach ($scheduleData as $classKey => $cData) {
            $parts = explode('-', $classKey);
            $numVal = (int) $parts[0];
            $secName = $parts[1];

            $cls = $gradeClassMap[$numVal] ?? null;
            $sec = $sectionModelMap[$classKey] ?? null;

            if (!$cls || !$sec) continue;

            $ctName = $cData['ct'];

            foreach ($cData['days'] as $dayNum => $periods) {
                foreach ($periods as $idx => $subAbbr) {
                    $pNum = $idx + 1;
                    $timing = $clientPeriodTimings[$pNum] ?? ['09:10:00', '09:50:00'];

                    $subCode = $abbrToCode[$subAbbr] ?? 'EVS';
                    $subjectObj = $subjectMap[$subCode] ?? null;

                    $teacherName = $getTeacherName($classKey, $dayNum, $pNum, $subAbbr, $ctName);
                    $teacherObj = $facultyCache[$teacherName] ?? ($facultyCache[$ctName] ?? null);

                    $periodType = 'class';
                    if ($subCode === 'SWIMMING' || $subCode === 'YOGA' || $subCode === 'PET' || $subCode === 'ECA' || $subCode === 'DRAWING' || $subCode === 'EKSAQ' || $subCode === 'LIB' || $subCode === 'DIARY') {
                        $periodType = 'activity';
                    } elseif ($subCode === 'CS-LAB') {
                        $periodType = 'practical';
                    }

                    $room = 'Room ' . $numVal . '-' . $secName;
                    if ($subCode === 'SWIMMING') $room = 'Swimming Pool';
                    elseif ($subCode === 'YOGA') $room = 'Yoga Hall';
                    elseif ($subCode === 'LIB') $room = 'Library';
                    elseif ($subCode === 'PET') $room = 'Playground';
                    elseif ($subCode === 'CS-LAB') $room = 'Computer Lab';

                    $records[] = [
                        'class_id'         => $cls->id,
                        'section_id'       => $sec->id,
                        'subject_id'       => $subjectObj?->id ?? 1,
                        'teacher_id'       => $teacherObj?->id,
                        'academic_year_id' => $currentYear->id,
                        'day_of_week'      => $dayNum,
                        'period_number'    => $pNum,
                        'start_time'       => $timing[0],
                        'end_time'         => $timing[1],
                        'room'             => $room,
                        'is_active'        => true,
                        'period_type'      => $periodType,
                        'effective_from'   => '2025-06-01',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($records, 100) as $chunk) {
            Timetable::insert($chunk);
        }

        // Clear module cache
        ClassesController::clearCache();
    }
}
