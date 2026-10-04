<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Notice extends Model
{
    protected $fillable = [
        'title',
        'content',
        'notice_type',
        'priority',
        'target_audience',
        'target_class_id',
        'publish_date',
        'expiry_date',
        'scheduled_at',
        'archived_at',
        'attachment',
        'reference_no',
        'issuing_authority',
        'signed_by_name',
        'signatory_designation',
        'order_category',
        'is_pinned',
        'is_published',
        'status',
        'requires_approval',
        'requires_acknowledgement',
        'approval_status',
        'rejection_reason',
        'school_id',
        'created_by',
    ];

    protected $casts = [
        'publish_date'             => 'date',
        'expiry_date'              => 'date',
        'scheduled_at'             => 'datetime',
        'archived_at'              => 'datetime',
        'is_published'             => 'boolean',
        'is_pinned'                => 'boolean',
        'requires_approval'        => 'boolean',
        'requires_acknowledgement' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'target_class_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NoticeRecipient::class);
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(NoticeAcknowledgement::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(ActivityAttachment::class, 'attachable');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function scopeActive($q)
    {
        return $q->where(function ($query) {
            $query->where('is_published', true)
                  ->orWhere('status', 'published');
        })
        ->where('publish_date', '<=', today())
        ->where(fn($sub) => $sub->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()));
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasAnyRole(['super_admin', 'principal', 'admin'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('target_audience', 'all')
              ->orWhere(function ($sub) use ($user) {
                  if ($user->hasRole('teacher') || $user->employee_id) {
                      $sub->where('target_audience', 'staff');
                  }
                  if ($user->hasRole('student')) {
                      $sub->orWhere('target_audience', 'students');
                      if ($user->student?->class_id) {
                          $sub->orWhere('target_class_id', $user->student->class_id);
                      }
                  }
                  if ($user->hasRole('parent')) {
                      $sub->orWhere('target_audience', 'parents');
                  }
              })
              ->orWhereHas('recipients', function ($r) use ($user) {
                  $r->where('recipient_type', 'all')
                    ->orWhere(fn($s) => $s->where('recipient_type', 'employee')->where('recipient_id', $user->employee_id))
                    ->orWhere(fn($s) => $s->where('recipient_type', 'student')->where('recipient_id', $user->student_id));
              });
        });
    }

    public function scopeCirculars($q)
    {
        return $q->where(fn($sub) => $sub->where('notice_type', 'circular')->orWhereNotNull('reference_no'));
    }

    public function scopeOrders($q)
    {
        return $q->where(fn($sub) => $sub->whereIn('notice_type', ['order', 'directive', 'memo'])->orWhereNotNull('order_category'));
    }

    public function scopePinnedFirst($q)
    {
        return $q->orderByDesc('is_pinned')->orderByDesc('publish_date')->orderByDesc('created_at');
    }
}
