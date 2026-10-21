<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Department;
use App\Models\Designation;

$depts = [
    ['name' => 'Academics', 'code' => 'ACAD', 'description' => 'Academic curricula and teaching'],
    ['name' => 'Mathematics', 'code' => 'MATH', 'description' => 'Mathematics department'],
    ['name' => 'Sciences', 'code' => 'SCIE', 'description' => 'Physics, Chemistry, Biology department'],
    ['name' => 'English & Languages', 'code' => 'ENGL', 'description' => 'English language and literature'],
    ['name' => 'Tamil Department', 'code' => 'TAML', 'description' => 'Tamil language and literature'],
    ['name' => 'Hindi Department', 'code' => 'HIND', 'description' => 'Hindi language and literature'],
    ['name' => 'Social Sciences', 'code' => 'SOCS', 'description' => 'History, Geography, Civics, Economics'],
    ['name' => 'Computer Science & IT', 'code' => 'CSIT', 'description' => 'Computer science and digital skills'],
    ['name' => 'Commerce & Economics', 'code' => 'COMM', 'description' => 'Commerce, Accountancy and Business Studies'],
    ['name' => 'Physical Education & Sports', 'code' => 'SPRT', 'description' => 'Sports and physical education'],
    ['name' => 'Arts, Crafts & Performing Arts', 'code' => 'ARTS', 'description' => 'Art, music, dance and culture'],
    ['name' => 'Kindergarten & Pre-Primary', 'code' => 'KGPR', 'description' => 'Foundational and early childhood learning'],
    ['name' => 'School Administration', 'code' => 'ADMN', 'description' => 'General school administration'],
    ['name' => 'Human Resources', 'code' => 'HRDP', 'description' => 'HR management and personnel'],
    ['name' => 'Accounts & Finance', 'code' => 'ACCT', 'description' => 'Billing, fees, payroll and accounting'],
    ['name' => 'Examination Cell', 'code' => 'EXAM', 'description' => 'Assessment and examination coordination'],
    ['name' => 'Library & Information', 'code' => 'LIBR', 'description' => 'Library resources and media center'],
    ['name' => 'Health & Student Counseling', 'code' => 'HLTH', 'description' => 'Infirmary and wellness counseling'],
    ['name' => 'Transport & Logistics', 'code' => 'TRNP', 'description' => 'School bus fleet and transport'],
    ['name' => 'Housekeeping & Maintenance', 'code' => 'MAIT', 'description' => 'Facility upkeep, cleaning and repair'],
    ['name' => 'Security & Campus Safety', 'code' => 'SECU', 'description' => 'Campus security and surveillance'],
    ['name' => 'Hostel & Residential Wing', 'code' => 'HOST', 'description' => 'Boarding facilities and warden care']
];

foreach ($depts as $d) {
    Department::firstOrCreate(['name' => $d['name']], $d);
}

$acadDept = Department::where('name', 'Academics')->first();
$admnDept = Department::where('name', 'School Administration')->first();
$hrDept = Department::where('name', 'Human Resources')->first();
$acctDept = Department::where('name', 'Accounts & Finance')->first();
$trnpDept = Department::where('name', 'Transport & Logistics')->first();
$maitDept = Department::where('name', 'Housekeeping & Maintenance')->first();
$kgDept = Department::where('name', 'Kindergarten & Pre-Primary')->first();
$csDept = Department::where('name', 'Computer Science & IT')->first();
$sprtDept = Department::where('name', 'Physical Education & Sports')->first();
$librDept = Department::where('name', 'Library & Information')->first();

$desigs = [
    // Leadership & Administration
    ['name' => 'Principal', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Principal', 'pay_scale' => 'Level 12 (PB-3 ₹78,800–₹1,50,000 GP 7600)'],
    ['name' => 'Vice Principal', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Vice-Principal', 'pay_scale' => 'Level 10 (PB-3 ₹56,100–₹1,15,000 GP 5400)'],
    ['name' => 'Headmistress / Headmaster', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Vice-Principal', 'pay_scale' => 'Level 10 (PB-3 ₹56,100–₹1,15,000 GP 5400)'],
    ['name' => 'Academic Coordinator', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Academic Coordinator', 'pay_scale' => 'Level 9 (PB-2 ₹53,100–₹98,000 GP 5400)'],
    ['name' => 'Examination Controller', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Academic Coordinator', 'pay_scale' => 'Level 9 (PB-2 ₹53,100–₹98,000 GP 5400)'],
    ['name' => 'Administrative Officer (AO)', 'dept_id' => $admnDept?->id, 'spatie_role' => 'Admin Officer', 'pay_scale' => 'Level 9 (PB-2 ₹53,100–₹98,000 GP 5400)'],
    ['name' => 'HR Manager', 'dept_id' => $hrDept?->id, 'spatie_role' => 'HR Officer', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'Accountant', 'dept_id' => $acctDept?->id, 'spatie_role' => 'Finance / Accounts', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'Office Assistant / Clerk', 'dept_id' => $admnDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 2 (₹19,900–₹32,000)'],
    ['name' => 'Front Office / Receptionist', 'dept_id' => $admnDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 2 (₹19,900–₹32,000)'],

    // PGT (Senior Secondary)
    ['name' => 'PGT Mathematics', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT Physics', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT Chemistry', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT Biology', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT Computer Science', 'dept_id' => $csDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT English', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'PGT Commerce & Accountancy', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'Senior Subject Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],
    ['name' => 'Senior Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Senior Teacher', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],

    // TGT (Secondary / Middle)
    ['name' => 'TGT Mathematics', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'TGT Science', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'TGT English', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'TGT Social Science', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'TGT Tamil', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'TGT Hindi', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'Junior Subject Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'Middle School Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],

    // PRT & Kindergarten
    ['name' => 'PRT / Primary Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Assistant Teacher', 'pay_scale' => 'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200)'],
    ['name' => 'Foundational Stage Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Assistant Teacher', 'pay_scale' => 'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200)'],
    ['name' => 'Kindergarten / Nursery Teacher', 'dept_id' => $kgDept?->id, 'spatie_role' => 'Assistant Teacher', 'pay_scale' => 'Level 4 (₹25,500–₹40,000)'],

    // Co-Curricular & Specialists
    ['name' => 'Physical Education Teacher (PET)', 'dept_id' => $sprtDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'Librarian', 'dept_id' => $librDept?->id, 'spatie_role' => 'Librarian', 'pay_scale' => 'Level 7 (PB-2 ₹44,900–₹72,000 GP 4600)'],
    ['name' => 'Art & Craft Teacher', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200)'],
    ['name' => 'Music & Dance Instructor', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200)'],
    ['name' => 'Computer Lab Instructor', 'dept_id' => $csDept?->id, 'spatie_role' => 'Subject Teacher', 'pay_scale' => 'Level 6 (PB-2 ₹35,400–₹58,000 GP 4200)'],
    ['name' => 'Science Lab Assistant', 'dept_id' => $acadDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 4 (₹25,500–₹40,000)'],
    ['name' => 'Student Counselor', 'dept_id' => $acadDept?->id, 'spatie_role' => 'Counselor', 'pay_scale' => 'Level 8 (PB-2 ₹47,600–₹85,000 GP 4800)'],

    // Support Personnel
    ['name' => 'Bus Driver', 'dept_id' => $trnpDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 2 (₹19,900–₹30,000)'],
    ['name' => 'Transport Attendant', 'dept_id' => $trnpDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 1 (₹18,000–₹25,000)'],
    ['name' => 'Housekeeping Staff', 'dept_id' => $maitDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 1 (₹18,000–₹25,000)'],
    ['name' => 'Kindergarten Caretaker', 'dept_id' => $kgDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 1 (₹18,000–₹25,000)'],
    ['name' => 'Campus Security Guard', 'dept_id' => $admnDept?->id, 'spatie_role' => null, 'pay_scale' => 'Level 1 (₹18,000–₹25,000)']
];

foreach ($desigs as $ds) {
    Designation::updateOrCreate(
        ['name' => $ds['name']],
        [
            'department_id' => $ds['dept_id'],
            'spatie_role'   => $ds['spatie_role'],
            'pay_scale'     => $ds['pay_scale'],
            'is_active'     => true
        ]
    );
}

echo "SUCCESS: " . Department::count() . " departments, " . Designation::count() . " designations.\n";
