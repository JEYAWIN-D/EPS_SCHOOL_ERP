<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enquiry_number', 'student_name', 'dob', 'gender',
        'class_id', 'parent_name', 'parent_mobile', 'parent_email',
        'address', 'source', 'notes', 'status', 'follow_up_date',
        'academic_year_id', 'assigned_to', 'created_by',
        'previous_school', 'previous_class', 'previous_percentage',
        'rejection_reason', 'waitlist_position',
        'entrance_test_date', 'entrance_test_time', 'entrance_test_venue', 'entrance_test_invigilator', 'entrance_test_marks',
        'interview_date', 'interview_time', 'interview_interviewer', 'interview_feedback',
        'documents', 'doc_checklist', 'referral_name',
        'missing_docs', 'docs_flag_note',
        'payment_terms', 'total_admission_fee', 'amount_collected', 'pending_amount',
        'payment_mode', 'payment_account', 'payment_date', 'payment_status', 'fee_breakdown',
        'last_school_studied', 'last_class_studied',
        'father_name', 'father_mobile', 'father_occupation', 'father_qualification', 'father_income',
        'mother_name', 'mother_mobile', 'mother_occupation', 'mother_qualification', 'mother_income',
        'referred_by', 'follow_up_remarks',
        'stream_group', 'year_of_passing', 'previous_school_attended', 'board',
        'dress_size', 'shoe_size', 'second_language',
        'converted_student_id', 'converted_at', 'converted_by',
        'non_conversion_reason', 'non_conversion_remarks',
        'closed_by', 'closed_at',
    ];

    protected $casts = [
        'dob'                 => 'date',
        'follow_up_date'      => 'date',
        'entrance_test_date'  => 'date',
        'interview_date'      => 'date',
        'payment_date'        => 'date',
        'converted_at'        => 'datetime',
        'closed_at'           => 'datetime',
        'documents'           => 'array',
        'doc_checklist'       => 'array',
        'missing_docs'        => 'array',
        'fee_breakdown'       => 'array',
        'total_admission_fee' => 'decimal:2',
        'amount_collected'    => 'decimal:2',
        'pending_amount'      => 'decimal:2',
    ];

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Classes::class, 'class_id');
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assignedTo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followUps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EnquiryFollowUp::class)->orderByDesc('created_at');
    }

    public function statusHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EnquiryStatusHistory::class, 'enquiry_id')->orderByDesc('changed_at');
    }

    public function drafts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdmissionDraft::class, 'enquiry_id')->orderByDesc('updated_at');
    }

    public function convertedStudent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function convertedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function closedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
        $num = 'ENQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (static::withTrashed()->where('enquiry_number', $num)->exists()) {
            $count++;
            $num = 'ENQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }
        return $num;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new'                  => 'badge-blue',
            'contacted'            => 'badge-cyan',
            'follow_up', 'follow_up_required' => 'badge-amber',
            'interested'           => 'badge-indigo',
            'visit_scheduled'      => 'badge-purple',
            'admission_pending'    => 'badge-amber',
            'converted', 'enrolled'=> 'badge-green',
            'not_interested', 'lost'=> 'badge-red',
            'on_hold'              => 'badge-slate',
            default                => 'badge-slate',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new'                  => 'New',
            'contacted'            => 'Contacted',
            'follow_up'            => 'Follow Up',
            'follow_up_required'   => 'Follow-up Required',
            'interested'           => 'Interested',
            'visit_scheduled'      => 'Visit Scheduled',
            'admission_pending'    => 'Admission Pending',
            'converted'            => 'Converted',
            'not_interested'       => 'Not Interested',
            'lost'                 => 'Lost',
            'on_hold'              => 'On Hold',
            'enrolled'             => 'Enrolled',
            default                => ucwords(str_replace('_', ' ', $this->status ?? 'New')),
        };
    }

    public function bookNoteItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AdmissionBookNoteItem::class, 'enquiry_id');
    }
}
