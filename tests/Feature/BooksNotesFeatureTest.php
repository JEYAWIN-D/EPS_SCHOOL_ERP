<?php

namespace Tests\Feature;

use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\BookNoteChecklist;
use App\Models\AdmissionBookNoteItem;
use App\Models\Classes;
use App\Models\Student;
use App\Models\User;
use App\Services\BookNoteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BooksNotesFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected AcademicYear $year;
    protected BookNoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BookNoteService::class);
        $this->year = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::current();
        
        $this->adminUser = User::where('email', 'admin@school.com')->first() 
            ?? User::first() 
            ?? User::factory()->create(['role' => 'admin']);
    }

    public function test_seeded_classes_and_totals()
    {
        $this->assertNotNull($this->year, 'Academic year 2026-27 should exist');

        // Check PRE KG (Class 15)
        $preKgClass = Classes::where('name', 'Pre-KG')->orWhere('name', 'PRE KG')->first();
        if ($preKgClass) {
            $checklist = $this->service->getChecklist($this->year->id, $preKgClass->id);
            $total = count($checklist['books']) + count($checklist['notes']);
            $this->assertEquals(12, $total, 'PRE KG should have 12 items (10 Ripples books, 1 Tamil, 1 Diary)');
        }

        // Check VI STD (Class 21)
        $viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
        if ($viClass) {
            $checklist = $this->service->getChecklist($this->year->id, $viClass->id);
            $this->assertCount(8, $checklist['books'], 'VI STD should have 8 books');
            $this->assertCount(29, $checklist['notes'], 'VI STD should have 29 notes');
            $total = count($checklist['books']) + count($checklist['notes']);
            $this->assertEquals(37, $total, 'VI STD should have 37 items total');
        }
    }

    public function test_xi_group_strict_isolation()
    {
        $xiClass = Classes::where('name', 'XI')->orWhere('name', 'XI STD')->first();
        $this->assertNotNull($xiClass, 'Class XI should exist');

        $bioGroup = AcademicGroup::where('code', 'BIO')->first();
        $csGroup = AcademicGroup::where('code', 'CS')->first();
        $accountsGroup = AcademicGroup::where('code', 'ACCOUNTS')->first();

        $this->assertNotNull($bioGroup, 'BIO group should exist');
        $this->assertNotNull($csGroup, 'CS group should exist');
        $this->assertNotNull($accountsGroup, 'Accounts group should exist');

        // 1. Fetch BIO checklist
        $bioChecklist = $this->service->getChecklist($this->year->id, $xiClass->id, $bioGroup->id);
        $bioBooks = collect($bioChecklist['books'])->pluck('name');
        $this->assertTrue($bioBooks->contains('NCERT Biology'), 'BIO checklist must contain NCERT Biology');
        $this->assertFalse($bioBooks->contains('SUMITA ARORA COMPUTERSCIENCE BOOK AND PRACTICE BOOK'), 'BIO checklist must NOT contain CS books');
        $this->assertFalse($bioBooks->contains('NCERT Accountancy Part I'), 'BIO checklist must NOT contain Accountancy');
        $this->assertCount(8, $bioChecklist['books'], 'BIO should have 8 books');
        $this->assertCount(2, $bioChecklist['notes'], 'BIO should have 2 notes');

        // 2. Fetch CS checklist
        $csChecklist = $this->service->getChecklist($this->year->id, $xiClass->id, $csGroup->id);
        $csBooks = collect($csChecklist['books'])->pluck('name');
        $this->assertFalse($csBooks->contains('NCERT Biology'), 'CS checklist must NOT contain Biology');
        $this->assertTrue($csBooks->contains('SUMITA ARORA COMPUTERSCIENCE BOOK AND PRACTICE BOOK'), 'CS checklist must contain CS books');
        $this->assertFalse($csBooks->contains('NCERT Accountancy Part I'), 'CS checklist must NOT contain Accountancy');
        $this->assertCount(9, $csChecklist['books'], 'CS should have 9 books');
        $this->assertCount(2, $csChecklist['notes'], 'CS should have 2 notes');

        // 3. Fetch Accounts checklist
        $accountsChecklist = $this->service->getChecklist($this->year->id, $xiClass->id, $accountsGroup->id);
        $accountsBooks = collect($accountsChecklist['books'])->pluck('name');
        $this->assertTrue($accountsBooks->contains('NCERT Accountancy Part I'), 'Accounts checklist must contain Accountancy Part I');
        $this->assertFalse($accountsBooks->contains('NCERT Biology'), 'Accounts checklist must NOT contain Biology');
        $this->assertCount(9, $accountsChecklist['books'], 'Accounts should have 9 books');
        $this->assertCount(3, $accountsChecklist['notes'], 'Accounts should have 3 notes');

        // 4. Fetching XI without group must return empty list (prevents leaking any group data)
        $emptyChecklist = $this->service->getChecklist($this->year->id, $xiClass->id, null);
        $this->assertEmpty($emptyChecklist['books'], 'Class XI without group must return 0 books');
        $this->assertEmpty($emptyChecklist['notes'], 'Class XI without group must return 0 notes');
    }

    public function test_duplicate_prevention()
    {
        $viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
        $this->assertNotNull($viClass);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // Attempt to insert duplicate book that already exists for VI STD
        $this->service->createItem([
            'academic_year_id' => $this->year->id,
            'class_id'         => $viClass->id,
            'group_id'         => null,
            'item_type'        => 'BOOK',
            'item_name'        => 'NCERT Maths', // Already exists in VI STD
            'quantity'         => 1,
            'display_order'    => 1,
            'status'           => 'active',
            'created_by'       => $this->adminUser->id,
        ]);
    }

    public function test_crud_and_status_toggle()
    {
        $viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
        $this->assertNotNull($viClass);

        // 1. Create a unique custom note
        $item = $this->service->createItem([
            'academic_year_id' => $this->year->id,
            'class_id'         => $viClass->id,
            'group_id'         => null,
            'item_type'        => 'NOTE',
            'item_name'        => 'Special Test Note for Unit Testing ' . uniqid(),
            'quantity'         => 3,
            'display_order'    => 99,
            'status'           => 'active',
            'created_by'       => $this->adminUser->id,
        ]);

        $this->assertNotNull($item->id);
        $this->assertEquals(3, $item->quantity);

        // 2. Update quantity
        $updated = $this->service->updateItem($item, [
            'quantity'      => 5,
            'display_order' => 100,
            'updated_by'    => $this->adminUser->id,
        ]);
        $this->assertEquals(5, $updated->quantity);

        // 3. Toggle Status to inactive
        $toggled = $this->service->toggleStatus($updated);
        $this->assertEquals('inactive', $toggled->status);

        // 4. Delete item (it has no admission references yet, so should be hard deleted)
        $result = $this->service->deleteOrDeactivate($toggled);
        $this->assertEquals('deleted', $result['action']);
        $this->assertNull(BookNoteChecklist::find($item->id));
    }

    public function test_admission_snapshot_immutability_and_protection()
    {
        $viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
        $this->assertNotNull($viClass);

        // Create temporary student
        $student = Student::first();
        if (!$student) {
            $student = Student::factory()->create();
        }

        // Generate snapshot for this student
        $count = $this->service->snapshotForAdmission(
            $this->year->id,
            $viClass->id,
            null,
            $student->id,
            $student->id,
            null
        );

        $this->assertEquals(37, $count, 'Should have snapshotted 37 items for Class VI admission');

        // Verify snapshot records in database
        $snapItems = AdmissionBookNoteItem::where('student_id', $student->id)->get();
        $this->assertEquals(37, $snapItems->count());

        // Find one source checklist item
        $firstSnap = $snapItems->first();
        $sourceItem = BookNoteChecklist::find($firstSnap->source_checklist_id);
        $this->assertNotNull($sourceItem);

        // Attempting to delete a source item that has been used in admissions should DEACTIVATE it, NOT delete it
        $res = $this->service->deleteOrDeactivate($sourceItem);
        $this->assertEquals('deactivated', $res['action'], 'Referenced checklist item should be deactivated, not deleted');
        
        $reloaded = BookNoteChecklist::find($sourceItem->id);
        $this->assertNotNull($reloaded, 'Source checklist item must still exist in database');
        $this->assertEquals('inactive', $reloaded->status);

        // Clean up test snapshot items
        AdmissionBookNoteItem::where('student_id', $student->id)->delete();
        $sourceItem->update(['status' => 'active']);
    }

    public function test_api_checklist_endpoint()
    {
        $viClass = Classes::where('name', 'VI')->orWhere('name', 'VI STD')->first();
        $this->assertNotNull($viClass);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('api.books-notes.checklist', [
                'academicYearId' => $this->year->id,
                'classId'        => $viClass->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'academicYear' => ['id', 'name'],
            'class'        => ['id', 'name'],
            'group',
            'books',
            'notes',
        ]);
    }
}
