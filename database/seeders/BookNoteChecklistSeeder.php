<?php

namespace Database\Seeders;

use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\BookNoteChecklist;
use App\Models\Classes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookNoteChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds for Erode Public School CBSE 2026-27 Checklist.
     */
    public function run(): void
    {
        // 1. Ensure Academic Groups exist (BIO, CS, ACCOUNTS)
        $bioGroup = AcademicGroup::where('code', 'BIO_CS')->first();
        if ($bioGroup) {
            $bioGroup->update([
                'code'          => 'BIO',
                'name'          => 'BIO GROUP',
                'description'   => 'Science Stream with Biology, Mathematics, Physics, Chemistry',
                'display_order' => 1,
                'is_active'     => true,
            ]);
        } else {
            $bioGroup = AcademicGroup::firstOrCreate(
                ['code' => 'BIO'],
                [
                    'name'          => 'BIO GROUP',
                    'description'   => 'Science Stream with Biology, Mathematics, Physics, Chemistry',
                    'display_order' => 1,
                    'is_active'     => true,
                ]
            );
        }

        $csGroup = AcademicGroup::firstOrCreate(
            ['code' => 'CS'],
            [
                'name'          => 'CS GROUP',
                'description'   => 'Science Stream with Computer Science, Mathematics, Physics, Chemistry',
                'display_order' => 2,
                'is_active'     => true,
            ]
        );

        $accountsGroup = AcademicGroup::firstOrCreate(
            ['code' => 'ACCOUNTS'],
            [
                'name'          => 'ACCOUNTS GROUP',
                'description'   => 'Commerce Stream with Accountancy, Economics, Entrepreneurship, Computer Science',
                'display_order' => 3,
                'is_active'     => true,
            ]
        );
        $accountsGroup->update(['display_order' => 3]);

        // 2. Identify 2026-27 Academic Year
        $academicYear = AcademicYear::where('name', 'like', '%2026-2027%')
            ->orWhere('name', 'like', '%2026-27%')
            ->first()
            ?? AcademicYear::where('is_current', true)->first();

        if (!$academicYear) {
            $this->command->error('Academic Year 2026-2027 not found. Please create or seed academic years first.');
            return;
        }

        $yearId = $academicYear->id;

        // 3. Helper to resolve Class ID
        $classes = Classes::all()->keyBy(function ($c) {
            return strtoupper(trim($c->name));
        });

        $resolveClass = function (array $names) use ($classes) {
            foreach ($names as $name) {
                $key = strtoupper(trim($name));
                if (isset($classes[$key])) {
                    return $classes[$key]->id;
                }
            }
            // fallback fuzzy search
            foreach ($classes as $c) {
                foreach ($names as $name) {
                    if (str_contains(strtoupper($c->name), strtoupper($name))) {
                        return $c->id;
                    }
                }
            }
            return null;
        };

        // Class mappings
        $classMap = [
            'PRE_KG' => $resolveClass(['Pre-KG', 'PRE KG', 'PRE-KG']),
            'LKG'    => $resolveClass(['LKG', 'JUNIOR KG', 'JR KG']),
            'UKG'    => $resolveClass(['UKG', 'SENIOR KG', 'SR KG']),
            'I'      => $resolveClass(['I', 'I STD', 'CLASS I', 'GRADE I', '1']),
            'II'     => $resolveClass(['II', 'II STD', 'CLASS II', 'GRADE II', '2']),
            'III'    => $resolveClass(['III', 'III STD', 'CLASS III', 'GRADE III', '3']),
            'IV'     => $resolveClass(['IV', 'IV STD', 'CLASS IV', 'GRADE IV', '4']),
            'V'      => $resolveClass(['V', 'V STD', 'CLASS V', 'GRADE V', '5']),
            'VI'     => $resolveClass(['VI', 'VI STD', 'CLASS VI', 'GRADE VI', '6']),
            'VII'    => $resolveClass(['VII', 'VII STD', 'CLASS VII', 'GRADE VII', '7']),
            'VIII'   => $resolveClass(['VIII', 'VIII STD', 'CLASS VIII', 'GRADE VIII', '8']),
            'IX'     => $resolveClass(['IX', 'IX STD', 'CLASS IX', 'GRADE IX', '9']),
            'X'      => $resolveClass(['X', 'X STD', 'CLASS X', 'GRADE X', '10']),
            'XI'     => $resolveClass(['XI', 'XI STD', 'CLASS XI', 'GRADE XI', '11']),
        ];

        // 4. Source Data Definition (ERODE PUBLIC SCHOOL CBSE NOTEBOOK CHECKLIST 2026-27)
        $checklistData = [
            // PRE KG
            'PRE_KG' => [
                'group' => null,
                'items' => [
                    ['type' => 'BOOK', 'name' => 'Ripples – 1 set (10 Books)', 'qty' => 10, 'order' => 1],
                    ['type' => 'BOOK', 'name' => 'Tamil (Sudartamil book-1)', 'qty' => 1, 'order' => 2],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 3],
                ],
            ],

            // JUNIOR KG
            'LKG' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size English Class work', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size English Home work', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size EVS Class work', 'qty' => 1, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size EVS Home work', 'qty' => 1, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Class work', 'qty' => 1, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Home work', 'qty' => 1, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs Checked A4 size Maths note', 'qty' => 1, 'order' => 8],
                    ['type' => 'BOOK', 'name' => 'X seed kit', 'qty' => 1, 'order' => 9],
                    ['type' => 'BOOK', 'name' => 'Tamil book (Sudartamil-2)', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 11],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 12],
                ],
            ],

            // SENIOR KG
            'UKG' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size English Class work', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size English Home work', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size EVS Class work', 'qty' => 1, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs 4 ruled A4 size EVS Home work', 'qty' => 1, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Class work', 'qty' => 1, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Home work', 'qty' => 1, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs Checked A4 size Maths note', 'qty' => 1, 'order' => 8],
                    ['type' => 'BOOK', 'name' => 'X seed kit', 'qty' => 1, 'order' => 9],
                    ['type' => 'BOOK', 'name' => 'Tamil book (Sudartamil-3)', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 11],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 12],
                ],
            ],

            // I STD
            'I' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size English Class work', 'qty' => 2, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Evs Class work', 'qty' => 2, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Class work', 'qty' => 1, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Home work', 'qty' => 1, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Class work', 'qty' => 1, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Class test note', 'qty' => 1, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Home work note', 'qty' => 3, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled Lily (VOC)', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'X Seed book kit', 'qty' => 1, 'order' => 11],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 12],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 13],
                    ['type' => 'BOOK', 'name' => 'Drawing Book', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 16],
                ],
            ],

            // II STD
            'II' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size English Class work', 'qty' => 2, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Evs Class work', 'qty' => 2, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Class work', 'qty' => 1, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Home work', 'qty' => 1, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Tamil Class work', 'qty' => 1, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Class test note', 'qty' => 1, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled A4 size Home work note', 'qty' => 3, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs 2 ruled Lily (VOC)', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'X Seed book kit', 'qty' => 1, 'order' => 11],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 12],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 13],
                    ['type' => 'BOOK', 'name' => 'Drawing Book', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 16],
                ],
            ],

            // III STD
            'III' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size English Class work', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size EVS Class work', 'qty' => 2, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Class work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Home work', 'qty' => 1, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Tamil Class work', 'qty' => 1, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled A4 size Class test note', 'qty' => 1, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size VOC', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Home work note', 'qty' => 3, 'order' => 13],
                    ['type' => 'NOTE', 'name' => 'Drawing note', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'X Seed book kit', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 16],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'Cambridge book', 'qty' => 2, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 20],
                ],
            ],

            // IV STD
            'IV' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Science Class work', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Social Science Class work', 'qty' => 2, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Class work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Tamil Class work', 'qty' => 2, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled A4 size Class test note', 'qty' => 1, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size VOC', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Home work note', 'qty' => 4, 'order' => 13],
                    ['type' => 'NOTE', 'name' => 'Drawing note', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'X Seed book kit', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 16],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'Cambridge book', 'qty' => 2, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 20],
                ],
            ],

            // V STD
            'V' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '40 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Science Class work', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Social Science Class work', 'qty' => 2, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Class work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs Maths ruled lily size Home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Tamil Class work', 'qty' => 2, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled A4 size Class test note', 'qty' => 1, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 1, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size VOC', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled A4 size Home work note', 'qty' => 4, 'order' => 13],
                    ['type' => 'NOTE', 'name' => 'Drawing note', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'X Seed book kit', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 16],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'Cambridge book', 'qty' => 2, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 20],
                ],
            ],

            // VI STD
            'VI' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Test note(Tamil,Eng)', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Test note(mat,sci,soc)', 'qty' => 3, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled long size English Class&home work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled long size Tamil Class&home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled long size Maths Class&home work', 'qty' => 4, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled long size Science Class&home work', 'qty' => 2, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '160 pgs single ruled long size Social Class&home work', 'qty' => 2, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 2, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size GK note', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 13],
                    ['type' => 'NOTE', 'name' => '80 pgs Graph note', 'qty' => 1, 'order' => 14],
                    ['type' => 'NOTE', 'name' => '120 pgs unruled long size C.S', 'qty' => 1, 'order' => 15],
                    ['type' => 'NOTE', 'name' => '80 pgs unruled long size mat,sci', 'qty' => 2, 'order' => 16],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size (mental ability)', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'NCERT English- Poorvi', 'qty' => 1, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'NCERT Science - Curiosity', 'qty' => 1, 'order' => 20],
                    ['type' => 'BOOK', 'name' => 'NCERT Social- Exploring society India and beyond', 'qty' => 1, 'order' => 21],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 22],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 23],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 24],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 25],
                ],
            ],

            // VII STD
            'VII' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(Tamil,Eng)', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(mat,sci,soc)', 'qty' => 3, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled English Class&home work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Tamil Class&home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Maths Class&home work', 'qty' => 4, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Science Class&home work', 'qty' => 2, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Social Class&home work', 'qty' => 2, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 2, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size GK note', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 13],
                    ['type' => 'NOTE', 'name' => '80 pgs Graph note', 'qty' => 1, 'order' => 14],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size (mental ability)', 'qty' => 1, 'order' => 15],
                    ['type' => 'NOTE', 'name' => '120 pgs unruled long size C.S', 'qty' => 1, 'order' => 16],
                    ['type' => 'NOTE', 'name' => '80 pgs unruled long size mat,sci', 'qty' => 2, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'NCERT Honey Comb', 'qty' => 1, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'NCERT An alien Hand', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 20],
                    ['type' => 'BOOK', 'name' => 'NCERT Science', 'qty' => 1, 'order' => 21],
                    ['type' => 'BOOK', 'name' => 'NCERT Our past II', 'qty' => 1, 'order' => 22],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Our Environment', 'qty' => 1, 'order' => 23],
                    ['type' => 'BOOK', 'name' => 'NCERT Social and Political Life', 'qty' => 1, 'order' => 24],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 25],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 26],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 27],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 28],
                ],
            ],

            // VIII STD
            'VIII' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(Tamil,Eng)', 'qty' => 2, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(mat,sci,soc)', 'qty' => 3, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled English Class&home work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Tamil Class&home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Maths Class&home work', 'qty' => 4, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Science Class&home work', 'qty' => 2, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Social Class&home work', 'qty' => 2, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Class work', 'qty' => 2, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs broad line lily size Hindi Home work', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size GK note', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 13],
                    ['type' => 'NOTE', 'name' => '80 pgs Graph note', 'qty' => 1, 'order' => 14],
                    ['type' => 'NOTE', 'name' => '120 pgs unruled long size C.S', 'qty' => 1, 'order' => 15],
                    ['type' => 'NOTE', 'name' => '80 pgs unruled long size mat,sci', 'qty' => 2, 'order' => 16],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size (mental ability)', 'qty' => 4, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'NCERT Honey Dew', 'qty' => 1, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'NCERT Its so happened', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 20],
                    ['type' => 'BOOK', 'name' => 'NCERT Science', 'qty' => 1, 'order' => 21],
                    ['type' => 'BOOK', 'name' => 'NCERT Our past III', 'qty' => 1, 'order' => 22],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Resources and Development', 'qty' => 1, 'order' => 23],
                    ['type' => 'BOOK', 'name' => 'NCERT Social and Political Life III', 'qty' => 1, 'order' => 24],
                    ['type' => 'BOOK', 'name' => 'Hindi book', 'qty' => 1, 'order' => 25],
                    ['type' => 'BOOK', 'name' => 'Computer book', 'qty' => 1, 'order' => 26],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 27],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 28],
                ],
            ],

            // IX STD
            'IX' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(tamil,eng,soc)', 'qty' => 3, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(mat,sci)', 'qty' => 2, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled English Class&home work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Tamil Class&home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Maths Class&home work', 'qty' => 5, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Science Class&home work', 'qty' => 2, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Social Class&home work', 'qty' => 2, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs Graph note', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '120 pgs unruled long size C.S', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs unruled long size mat,sci', 'qty' => 2, 'order' => 13],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size (mental ability)', 'qty' => 4, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'NCERT Bee hives', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'NCERT Moments', 'qty' => 1, 'order' => 16],
                    ['type' => 'BOOK', 'name' => 'NCERT Words & Expressions I', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'NCERT Science', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'NCERT India and the contemporary world', 'qty' => 1, 'order' => 20],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Democratic politics I', 'qty' => 1, 'order' => 21],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Contemporary India I', 'qty' => 1, 'order' => 22],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Economics I', 'qty' => 1, 'order' => 23],
                    ['type' => 'BOOK', 'name' => 'NCERT Computer book- Information Technology', 'qty' => 1, 'order' => 24],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 25],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 26],
                ],
            ],

            // X STD
            'X' => [
                'group' => null,
                'items' => [
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size English composition', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled long size Tamil composition', 'qty' => 1, 'order' => 2],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(tamil,eng,soc)', 'qty' => 3, 'order' => 3],
                    ['type' => 'NOTE', 'name' => '80 pgs long size single ruled Test note(mat,sci)', 'qty' => 2, 'order' => 4],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled English Class&home work', 'qty' => 2, 'order' => 5],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Tamil Class&home work', 'qty' => 2, 'order' => 6],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Maths Class&home work', 'qty' => 7, 'order' => 7],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Science Class&home work', 'qty' => 2, 'order' => 8],
                    ['type' => 'NOTE', 'name' => '160 pgs long size single ruled Social Class&home work', 'qty' => 2, 'order' => 9],
                    ['type' => 'NOTE', 'name' => '80 pgs single ruled lily size Computer science', 'qty' => 1, 'order' => 10],
                    ['type' => 'NOTE', 'name' => '80 pgs Graph note', 'qty' => 1, 'order' => 11],
                    ['type' => 'NOTE', 'name' => '120 pgs unruled long size C.S', 'qty' => 1, 'order' => 12],
                    ['type' => 'NOTE', 'name' => '80 pgs unruled long size mat,sci', 'qty' => 2, 'order' => 13],
                    ['type' => 'BOOK', 'name' => 'NCERT First flight', 'qty' => 1, 'order' => 14],
                    ['type' => 'BOOK', 'name' => 'NCERT Foot prints without feet', 'qty' => 1, 'order' => 15],
                    ['type' => 'BOOK', 'name' => 'NCERT Words & Expressions II', 'qty' => 1, 'order' => 16],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 17],
                    ['type' => 'BOOK', 'name' => 'NCERT Science', 'qty' => 1, 'order' => 18],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Democratic politics II', 'qty' => 1, 'order' => 19],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Understanding economics development', 'qty' => 1, 'order' => 20],
                    ['type' => 'BOOK', 'name' => 'NCERT Social Contemporary India II', 'qty' => 1, 'order' => 21],
                    ['type' => 'BOOK', 'name' => 'NCERT Social India and the contemporary world II', 'qty' => 1, 'order' => 22],
                    ['type' => 'BOOK', 'name' => 'NCERT Computer book- Information Technology', 'qty' => 1, 'order' => 23],
                    ['type' => 'BOOK', 'name' => 'Diary', 'qty' => 1, 'order' => 24],
                    ['type' => 'BOOK', 'name' => 'Tamil book', 'qty' => 1, 'order' => 25],
                ],
            ],

            // GRADE XI - BIO GROUP
            'XI_BIO' => [
                'classKey' => 'XI',
                'group'    => $bioGroup->id,
                'items'    => [
                    ['type' => 'NOTE', 'name' => '160 pgs unruled long size', 'qty' => 3, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '160 pgs ruled long size', 'qty' => 22, 'order' => 2],
                    ['type' => 'BOOK', 'name' => 'English Hornbill', 'qty' => 1, 'order' => 3],
                    ['type' => 'BOOK', 'name' => 'English Snap shot', 'qty' => 1, 'order' => 4],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 5],
                    ['type' => 'BOOK', 'name' => 'NCERT Physics part I', 'qty' => 1, 'order' => 6],
                    ['type' => 'BOOK', 'name' => 'NCERT Physics part II', 'qty' => 1, 'order' => 7],
                    ['type' => 'BOOK', 'name' => 'NCERT Chemistry part I', 'qty' => 1, 'order' => 8],
                    ['type' => 'BOOK', 'name' => 'NCERT Chemistry part II', 'qty' => 1, 'order' => 9],
                    ['type' => 'BOOK', 'name' => 'NCERT Biology', 'qty' => 1, 'order' => 10],
                ],
            ],

            // GRADE XI - CS GROUP
            'XI_CS' => [
                'classKey' => 'XI',
                'group'    => $csGroup->id,
                'items'    => [
                    ['type' => 'NOTE', 'name' => '160 pgs unruled long size', 'qty' => 3, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '160 pgs ruled long size', 'qty' => 22, 'order' => 2],
                    ['type' => 'BOOK', 'name' => 'English Hornbill', 'qty' => 1, 'order' => 3],
                    ['type' => 'BOOK', 'name' => 'English Snap shot', 'qty' => 1, 'order' => 4],
                    ['type' => 'BOOK', 'name' => 'NCERT Maths', 'qty' => 1, 'order' => 5],
                    ['type' => 'BOOK', 'name' => 'NCERT Physics part I', 'qty' => 1, 'order' => 6],
                    ['type' => 'BOOK', 'name' => 'NCERT Physics part II', 'qty' => 1, 'order' => 7],
                    ['type' => 'BOOK', 'name' => 'NCERT Chemistry part I', 'qty' => 1, 'order' => 8],
                    ['type' => 'BOOK', 'name' => 'NCERT Chemistry part II', 'qty' => 1, 'order' => 9],
                    ['type' => 'BOOK', 'name' => 'SUMITA ARORA COMPUTERSCIENCE BOOK AND PRACTICE BOOK', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'SUMITA ARORA COMPUTERSCIENCE PRACTICE BOOK', 'qty' => 1, 'order' => 11],
                ],
            ],

            // GRADE XI - ACCOUNTS GROUP
            'XI_ACCOUNTS' => [
                'classKey' => 'XI',
                'group'    => $accountsGroup->id,
                'items'    => [
                    ['type' => 'NOTE', 'name' => '160 pgs unruled long size', 'qty' => 1, 'order' => 1],
                    ['type' => 'NOTE', 'name' => '160 pgs ruled long size', 'qty' => 20, 'order' => 2],
                    ['type' => 'NOTE', 'name' => 'ACCOUNTANCY', 'qty' => 4, 'order' => 3],
                    ['type' => 'BOOK', 'name' => 'English Hornbill', 'qty' => 1, 'order' => 4],
                    ['type' => 'BOOK', 'name' => 'English Snap shot', 'qty' => 1, 'order' => 5],
                    ['type' => 'BOOK', 'name' => 'NCERT Accountancy Part I', 'qty' => 1, 'order' => 6],
                    ['type' => 'BOOK', 'name' => 'NCERT Accountancy Part II', 'qty' => 1, 'order' => 7],
                    ['type' => 'BOOK', 'name' => 'NCERT Entrepreneurship', 'qty' => 1, 'order' => 8],
                    ['type' => 'BOOK', 'name' => 'NCERT Micro economics', 'qty' => 1, 'order' => 9],
                    ['type' => 'BOOK', 'name' => 'NCERT Statistics for economics', 'qty' => 1, 'order' => 10],
                    ['type' => 'BOOK', 'name' => 'SUMITA ARORA COMPUTERSCIENCE BOOK', 'qty' => 1, 'order' => 11],
                    ['type' => 'BOOK', 'name' => 'SUMITA ARORA COMPUTERSCIENCE PRACTICE BOOK', 'qty' => 1, 'order' => 12],
                ],
            ],
        ];

        $rows = [];
        $now = now();

        foreach ($checklistData as $key => $config) {
            $classLookup = $config['classKey'] ?? $key;
            $classId = $classMap[$classLookup] ?? null;

            if (!$classId) {
                $this->command->warn("Could not find class for key '{$classLookup}'. Skipping.");
                continue;
            }

            $groupId = $config['group'];

            foreach ($config['items'] as $item) {
                $rows[] = [
                    'academic_year_id' => $yearId,
                    'class_id'         => $classId,
                    'group_id'         => $groupId,
                    'item_type'        => $item['type'],
                    'item_name'        => $item['name'],
                    'quantity'         => $item['qty'],
                    'display_order'    => $item['order'],
                    'status'           => 'active',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        // Clean slate for this academic year to avoid duplicates
        BookNoteChecklist::where('academic_year_id', $yearId)->delete();

        foreach (array_chunk($rows, 50) as $chunk) {
            BookNoteChecklist::insert($chunk);
        }

        $this->command->info("Successfully seeded " . count($rows) . " Books & Notes checklist items for Academic Year {$academicYear->name}.");
    }
}
