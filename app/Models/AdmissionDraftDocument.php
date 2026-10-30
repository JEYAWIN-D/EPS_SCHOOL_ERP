<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AdmissionDraftDocument extends Model
{
    protected $fillable = [
        'draft_id',
        'document_type',
        'original_filename',
        'stored_path',
        'mime_type',
        'file_size',
        'upload_status',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size'   => 'integer',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(AdmissionDraft::class, 'draft_id');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->stored_path ? Storage::disk('public')->url($this->stored_path) : null;
    }
}

