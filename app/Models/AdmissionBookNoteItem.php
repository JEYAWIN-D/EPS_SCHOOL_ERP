<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionBookNoteItem extends Model
{
    protected $table = 'admission_book_note_items';

    protected $fillable = [
        'admission_id',
        'student_id',
        'enquiry_id',
        'source_checklist_id',
        'item_type',
        'gender',
        'sku',
        'item_name',
        'quantity',
        'issued_quantity',
        'is_issued',
        'issued_at',
        'issued_by',
        'remarks',
    ];

    protected $casts = [
        'admission_id'        => 'integer',
        'student_id'          => 'integer',
        'enquiry_id'          => 'integer',
        'source_checklist_id' => 'integer',
        'quantity'            => 'integer',
        'issued_quantity'     => 'integer',
        'is_issued'           => 'boolean',
        'issued_at'           => 'datetime',
    ];

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, (int)$this->quantity - (int)$this->issued_quantity);
    }

    public function getIsFullyIssuedAttribute(): bool
    {
        return (int)$this->issued_quantity >= (int)$this->quantity;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class, 'enquiry_id');
    }

    public function sourceChecklist(): BelongsTo
    {
        return $this->belongsTo(BookNoteChecklist::class, 'source_checklist_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
