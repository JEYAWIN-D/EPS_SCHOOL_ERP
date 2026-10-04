<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookNoteChecklist extends Model
{
    protected $table = 'book_note_checklists';

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'group_id',
        'item_type',
        'gender',
        'sku',
        'item_name',
        'quantity',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'academic_year_id' => 'integer',
        'class_id'         => 'integer',
        'group_id'         => 'integer',
        'quantity'         => 'integer',
        'display_order'    => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function admissionItems(): HasMany
    {
        return $this->hasMany(AdmissionBookNoteItem::class, 'source_checklist_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBooks($query)
    {
        return $query->where('item_type', 'BOOK');
    }

    public function scopeNotes($query)
    {
        return $query->where('item_type', 'NOTE');
    }

    public function scopeForYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeForClassAndGroup($query, int $classId, ?int $groupId = null)
    {
        $query->where('class_id', $classId);
        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }
        return $query;
    }
}
