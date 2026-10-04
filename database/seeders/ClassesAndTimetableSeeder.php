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

class ClassesAndTimetableSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::firstOrCreate(
                ['name' => '2025-2026'],
                ['start_date' => '2025-06-01', 'end_date' => '2026-04-30', 'is_current' => true]
            );

        $employees = Employee::all();
        $teachers = $employees->isNotEmpty() ? $employees : collect();
        $users = User::all();

        // 1. Create or Update Core Classes (Pre-Primary to 12th Standard)
        $classList = [
            ['name' => 'Pre-KG', 'numeric_value' => 0, 'sort_order' => 1],
            ['name' => 'LKG',    'numeric_value' => 0, 'sort_order' => 2],
            ['name' => 'UKG',    'numeric_value' => 0, 'sort_order' => 3],
            ['name' => 'I',      'numeric_value' => 1, 'sort_order' => 4],
            ['name' => 'II',     'numeric_value' => 2, 'sort_order' => 5],
            ['name' => 'III',    'numeric_value' => 3, 'sort_order' => 6],
            ['name' => 'IV',     'numeric_value' => 4, 'sort_order' => 7],
            ['name' => 'V',      'numeric_value' => 5, 'sort_order' => 8],
            ['name' => 'VI',     'numeric_value' => 6, 'sort_order' => 9],
            ['name' => 'VII',    'numeric_value' => 7, 'sort_order' => 10],
            ['name' => 'VIII',   'numeric_value' => 8, 'sort_order' => 11],
            ['name' => 'IX',     'numeric_value' => 9, 'sort_order' => 12],
            ['name' => 'X',      'numeric_value' => 10, 'sort_order' => 13],
            ['name' => 'XI',     'numeric_value' => 11, 'sort_order' => 14],
            ['name' => 'XII',    'numeric_value' => 12, 'sort_order' => 15],
        ];

        foreach ($classList as $cData) {
            Classes::updateOrCreate(
                ['name' => $cData['name']],
                [
                    'sort_order'    => $cData['sort_order'],
                    'numeric_value' => $cData['numeric_value'],
                    'is_active'     => true,
                ]
            );
        }

        // 2. Ensure Sections A, B, C, D exist for every class
        $sections = ['A', 'B', 'C', 'D'];
        $teacherIndex = 0;

        foreach (Classes::orderBy('sort_order')->get() as $cls) {
            foreach ($sections as $sName) {
                $teacherUser = $users->isNotEmpty() ? $users[$teacherIndex % $users->count()] : null;
                $teacherIndex++;

                Section::updateOrCreate(
                    [
                        'class_id' => $cls->id,
                        'name'     => $sName,
                    ],
                    [
                        'academic_year_id' => $currentYear->id,
                        'capacity'         => 40,
                        'is_active'        => true,
                        'class_teacher_id' => $teacherUser?->id,
                    ]
                );
            }
        }

        // 3. Create Complete Realistic Subject Catalog Tiered by School Level
        $subjectsData = [
            // ── A. PRE-PRIMARY / KINDERGARTEN (Pre-KG, LKG, UKG) ──
            ['name' => 'English Rhymes & Phonics',     'code' => 'KG-ENG',   'type' => 'language', 'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Number Play & Math Concepts',  'code' => 'KG-MAT',   'type' => 'theory',   'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Storytelling & Picture Reading','code' => 'KG-STR',   'type' => 'activity', 'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Drawing, Coloring & Craft',    'code' => 'KG-ART',   'type' => 'activity', 'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'General Awareness & EVS',      'code' => 'KG-EVS',   'type' => 'theory',   'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Action Songs & Music',         'code' => 'KG-MUS',   'type' => 'activity', 'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Play & Motor Skills',          'code' => 'KG-ACT',   'type' => 'activity', 'medium' => 'English', 'stream' => 'pre-primary'],
            ['name' => 'Clay Modeling & Sensory Fun',   'code' => 'KG-CLY',   'type' => 'activity', 'medium' => 'English', 'stream' => 'pre-primary'],

            // ── B. PRIMARY SCHOOL (1st to 5th Standard) ──
            ['name' => 'Tamil',                        'code' => 'TAM-0105', 'type' => 'language', 'medium' => 'Tamil',   'stream' => 'primary'],
            ['name' => 'English Reading & Grammar',    'code' => 'ENG-0105', 'type' => 'language', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Mathematics',                  'code' => 'MAT-0105', 'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Environmental Studies (EVS)',  'code' => 'EVS-0105', 'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'General Science',              'code' => 'SCI-0105', 'type' => 'theory',   'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Hindi (Basics)',               'code' => 'HIN-0105', 'type' => 'language', 'medium' => 'Hindi',   'stream' => 'primary'],
            ['name' => 'Computer Basics & Coding',     'code' => 'CSC-0105', 'type' => 'practical','medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Art, Craft & Value Education', 'code' => 'ART-0105', 'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],
            ['name' => 'Physical Education & Games',   'code' => 'PET-0105', 'type' => 'activity', 'medium' => 'English', 'stream' => 'primary'],

            // ── C. MIDDLE SCHOOL (6th to 8th Standard) ──
            ['name' => 'Tamil',                        'code' => 'TAM-0608', 'type' => 'language', 'medium' => 'Tamil',   'stream' => 'middle'],
            ['name' => 'English Literature',           'code' => 'ENG-0608', 'type' => 'language', 'medium' => 'English', 'stream' => 'middle'],
            ['name' => 'Mathematics (Algebra & Geom)', 'code' => 'MAT-0608', 'type' => 'theory',   'medium' => 'English', 'stream' => 'middle'],
            ['name' => 'Science (Physics, Chem, Bio)', 'code' => 'SCI-0608', 'type' => 'theory',   'medium' => 'English', 'stream' => 'middle'],
            ['name' => 'Social Science (Hist & Geo)',  'code' => 'SOC-0608', 'type' => 'theory',   'medium' => 'English', 'stream' => 'middle'],
            ['name' => 'Hindi Language',               'code' => 'HIN-0608', 'type' => 'language', 'medium' => 'Hindi',   'stream' => 'middle'],
            ['name' => 'Computer Science & AI',        'code' => 'CSC-0608', 'type' => 'practical','medium' => 'English', 'stream' => 'middle'],
            ['name' => 'Physical & Health Education',  'code' => 'PET-0608', 'type' => 'activity', 'medium' => 'English', 'stream' => 'middle'],

            // ── D. SECONDARY SCHOOL (9th & 10th Standard) ──
            ['name' => 'Tamil (Language I)',           'code' => 'TAM-0910', 'type' => 'language', 'medium' => 'Tamil',   'stream' => 'secondary'],
            ['name' => 'English Language & Lit',       'code' => 'ENG-0910', 'type' => 'language', 'medium' => 'English', 'stream' => 'secondary'],
            ['name' => 'Mathematics',                  'code' => 'MAT-0910', 'type' => 'theory',   'medium' => 'English', 'stream' => 'secondary'],
            ['name' => 'Science (Physics/Chem/Bio)',   'code' => 'SCI-0910', 'type' => 'theory',   'medium' => 'English', 'stream' => 'secondary'],
            ['name' => 'Social Science',               'code' => 'SOC-0910', 'type' => 'theory',   'medium' => 'English', 'stream' => 'secondary'],
            ['name' => 'Information Technology (IT)',  'code' => 'CSC-0910', 'type' => 'practical','medium' => 'English', 'stream' => 'secondary'],
            ['name' => 'Physical & Health Education',  'code' => 'PET-0910', 'type' => 'activity', 'medium' => 'English', 'stream' => 'secondary'],

            // ── E. HIGHER SECONDARY (11th & 12th Standard) ──
            ['name' => 'Tamil',                        'code' => 'TAM-1112', 'type' => 'language', 'medium' => 'Tamil',   'stream' => 'higher'],
            ['name' => 'English Core',                 'code' => 'ENG-1112', 'type' => 'language', 'medium' => 'English', 'stream' => 'higher'],
            // Group 1 (CS Stream)
            ['name' => 'Mathematics (Advanced)',       'code' => 'MAT-CS11', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Physics',                      'code' => 'PHY-CS11', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Chemistry',                    'code' => 'CHE-CS11', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Computer Science (Python&SQL)','code' => 'CSC-CS11', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            // Group 2 (Biology Stream)
            ['name' => 'Physics (Bio-Stream)',         'code' => 'PHY-BIO',  'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Chemistry (Bio-Stream)',       'code' => 'CHE-BIO',  'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Biology (Botany & Zoology)',   'code' => 'BIO-1112', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Mathematics / Applied Maths',  'code' => 'MAT-BIO',  'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            // Group 3 (Commerce Stream)
            ['name' => 'Commerce & Business Studies',  'code' => 'COM-1112', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Accountancy & Financial Mgmt', 'code' => 'ACC-1112', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Economics & Statistics',       'code' => 'ECO-1112', 'type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Business Mathematics',         'code' => 'BMAT-1112','type' => 'theory',   'medium' => 'English', 'stream' => 'higher'],
            ['name' => 'Computer Applications (Comm)', 'code' => 'CA-1112',  'type' => 'practical','medium' => 'English', 'stream' => 'higher'],

            // ── F. SATURDAY CLUBS & CO-CURRICULAR ──
            ['name' => 'Sports, Athletics & Games',    'code' => 'SAT-SPO',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Yoga, Meditation & Wellness',  'code' => 'SAT-YOG',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Visual Arts, Painting & Craft','code' => 'SAT-ART',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Music, Choir & Classical Dance','code' => 'SAT-MUS', 'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'STEM Lab, Robotics & AI Club', 'code' => 'SAT-ROB',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Debate, MUN & Public Speaking','code' => 'SAT-DEB',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Scouts & Guides / NCC',        'code' => 'SAT-SCT',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
            ['name' => 'Chess & Mind Games League',    'code' => 'SAT-CHS',  'type' => 'activity', 'medium' => 'English', 'stream' => 'all'],
        ];

        $subjectMap = [];
        foreach ($subjectsData as $sData) {
            $subj = Subject::updateOrCreate(
                ['code' => $sData['code']],
                [
                    'name'            => $sData['name'],
                    'type'            => $sData['type'],
                    'medium'          => $sData['medium'] ?? 'English',
                    'stream'          => $sData['stream'] ?? 'all',
                    'is_active'       => true,
                ]
            );
            $subjectMap[$sData['code']] = $subj;
        }

        // 4. Daily 8 Period Timing Schedule
        $periods = [
            1 => ['start' => '09:15:00', 'end' => '10:00:00'],
            2 => ['start' => '10:00:00', 'end' => '10:45:00'],
            3 => ['start' => '11:00:00', 'end' => '11:45:00'],
            4 => ['start' => '11:45:00', 'end' => '12:30:00'],
            5 => ['start' => '13:15:00', 'end' => '14:00:00'],
            6 => ['start' => '14:00:00', 'end' => '14:45:00'],
            7 => ['start' => '15:00:00', 'end' => '15:45:00'],
            8 => ['start' => '15:45:00', 'end' => '16:30:00'],
        ];

        // Saturday Clubs sequence
        $saturdayExtracurricularCodes = [
            1 => 'SAT-SPO',
            2 => 'SAT-YOG',
            3 => 'SAT-ART',
            4 => 'SAT-MUS',
            5 => 'SAT-ROB',
            6 => 'SAT-DEB',
            7 => 'SAT-SCT',
            8 => 'SAT-CHS',
        ];

        // Tiered Curricula
        $kgCurriculum       = ['KG-ENG', 'KG-MAT', 'KG-STR', 'KG-ART', 'KG-EVS', 'KG-MUS', 'KG-ACT', 'KG-CLY'];
        $primaryCurriculum  = ['TAM-0105', 'ENG-0105', 'MAT-0105', 'EVS-0105', 'SCI-0105', 'HIN-0105', 'CSC-0105', 'PET-0105'];
        $middleCurriculum   = ['ENG-0608', 'TAM-0608', 'MAT-0608', 'SCI-0608', 'SOC-0608', 'HIN-0608', 'CSC-0608', 'PET-0608'];
        $secondaryCurriculum= ['MAT-0910', 'SCI-0910', 'SOC-0910', 'ENG-0910', 'TAM-0910', 'CSC-0910', 'PET-0910', 'MAT-0910'];

        $hscGroup1_CS   = ['PHY-CS11', 'CHE-CS11', 'MAT-CS11', 'CSC-CS11', 'ENG-1112', 'TAM-1112', 'MAT-CS11', 'CSC-CS11'];
        $hscGroup2_Bio  = ['BIO-1112', 'PHY-BIO',  'CHE-BIO',  'MAT-BIO',  'ENG-1112', 'TAM-1112', 'BIO-1112', 'CHE-BIO'];
        $hscGroup3_Comm = ['ACC-1112', 'COM-1112', 'ECO-1112', 'BMAT-1112','ENG-1112', 'TAM-1112', 'ACC-1112', 'COM-1112'];
        $hscGroup3_CA   = ['ACC-1112', 'COM-1112', 'ECO-1112', 'CA-1112',  'ENG-1112', 'TAM-1112', 'CA-1112',  'ACC-1112'];

        // Rebuild timetable with distinct, interleaved schedules
        Timetable::query()->delete();

        $allClasses = Classes::orderBy('sort_order')->get();
        $teacherList = $teachers->values();
        $tCount = $teacherList->count();
        $tIdx = 0;
        $now = now();
        $records = [];

        foreach ($allClasses as $cls) {
            $nv = (int) $cls->numeric_value;
            $classOrder = (int) $cls->sort_order;
            $classSections = Section::where('class_id', $cls->id)->get();

            foreach ($classSections as $sec) {
                $secName = $sec->name;

                // Determine Tier Curriculum
                if ($nv === 0) {
                    // Kindergarten / Pre-Primary
                    $tierSubjects = $kgCurriculum;
                } elseif ($nv >= 1 && $nv <= 5) {
                    // Primary (1st to 5th)
                    $tierSubjects = $primaryCurriculum;
                } elseif ($nv >= 6 && $nv <= 8) {
                    // Middle School (6th to 8th)
                    $tierSubjects = $middleCurriculum;
                } elseif ($nv >= 9 && $nv <= 10) {
                    // Secondary (9th & 10th)
                    $tierSubjects = $secondaryCurriculum;
                } else {
                    // Higher Secondary (11th & 12th) - Stream split
                    if ($secName === 'A') {
                        $tierSubjects = $hscGroup1_CS;
                    } elseif ($secName === 'B') {
                        $tierSubjects = $hscGroup2_Bio;
                    } elseif ($secName === 'C') {
                        $tierSubjects = $hscGroup3_Comm;
                    } else {
                        $tierSubjects = $hscGroup3_CA;
                    }
                }

                $subjCount = count($tierSubjects);

                $secOffset = match($secName) {
                    'A' => 0,
                    'B' => 1,
                    'C' => 2,
                    'D' => 3,
                    default => 0,
                };

                // Build Monday to Saturday schedule (Days 1 to 6)
                for ($day = 1; $day <= 6; $day++) {
                    for ($p = 1; $p <= 8; $p++) {
                        $pInfo = $periods[$p];

                        if ($day === 6) {
                            // Saturday: Clubs rotation
                            $satIndex = (1 + $p + $secOffset + $classOrder) % 8;
                            if ($satIndex === 0) $satIndex = 8;
                            $subjCode = $saturdayExtracurricularCodes[$satIndex] ?? 'SAT-SPO';
                        } else {
                            // Weekday: Dynamic interleave formula ensuring distinct subjects for every class & section
                            $subjectIndex = ($classOrder * 3 + $secOffset * 2 + $day * 3 + $p * 5 - 1) % $subjCount;
                            if ($subjectIndex < 0) $subjectIndex += $subjCount;
                            $subjCode = $tierSubjects[$subjectIndex];
                        }

                        $subjObj = $subjectMap[$subjCode] ?? null;
                        if (!$subjObj) continue;

                        $assignedTeacher = $tCount > 0 ? $teacherList[$tIdx % $tCount] : null;
                        $tIdx++;

                        $roomNumber = 'Room ' . ($cls->numeric_value > 0 ? ($cls->numeric_value . '-' . $secName) : ('KG-' . $secName));

                        $records[] = [
                            'class_id'         => $cls->id,
                            'section_id'       => $sec->id,
                            'subject_id'       => $subjObj->id,
                            'teacher_id'       => $assignedTeacher?->id,
                            'academic_year_id' => $currentYear->id,
                            'day_of_week'      => $day,
                            'period_number'    => $p,
                            'start_time'       => $pInfo['start'],
                            'end_time'         => $pInfo['end'],
                            'room'             => $roomNumber,
                            'is_active'        => true,
                            'period_type'      => 'class',
                            'effective_from'   => '2025-06-01',
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($records, 300) as $chunk) {
            Timetable::insert($chunk);
        }
    }
}
