<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdCardTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_name',
        'card_type',
        'orientation',
        'physical_width',
        'physical_height',
        'configuration_json',
        'is_active',
        'is_default',
        'version',
        'created_by',
    ];

    protected $casts = [
        'configuration_json' => 'array',
        'is_active'          => 'boolean',
        'is_default'         => 'boolean',
        'physical_width'     => 'float',
        'physical_height'    => 'float',
        'version'            => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(IdCard::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('card_type', $type);
    }

    /**
     * Get default configuration array for given card type and orientation
     */
    public static function getDefaultConfig(string $cardType = 'student', string $orientation = 'landscape'): array
    {
        $base = [
            'school_name'         => 'Erode Public School',
            'affiliation_text'    => 'Affiliated to CBSE, New Delhi Aff No.1931585',
            'motto'               => "TOMORROW'S INSIDE",
            'school_address'      => '420, Vakkil Thottam, Manickam Palayam, Erode, Tamil Nadu – 638004',
            'school_phone'        => '93677 54654',
            'school_email'        => 'admin@erodepublicschool.edu.in',
            'school_website'      => 'https://erodepublicschool.edu.in',
            'primary_color'       => '#892528', // Crimson/Maroon from school crest
            'secondary_color'     => '#3F4358', // Slate Navy from banner
            'accent_gold'         => '#C59B27', // Laurel wreath gold
            'bg_color'            => '#FFFFFF',
            'text_color'          => '#0F172A',
            'font_family'         => 'Plus Jakarta Sans',
            'show_blood_group'    => true,
            'show_dob'            => true,
            'show_address'        => false,
            'show_parent_phone'   => true,
            'show_emergency_phone'=> true,
            'show_signature'      => true,
            'show_qr'             => true,
            'show_barcode'        => false,
            'card_validity_years' => 1,
            'terms_text'          => '1. This identity card is the property of Erode Public School.
2. Must be displayed on campus at all times.
3. If lost or found, please return immediately to the School Office or contact 93677 54654.',
        ];

        if ($cardType === 'staff') {
            $base['terms_text'] = '1. This card confirms official employment with Erode Public School.
2. Non-transferable and must be surrendered upon separation.
3. In case of emergency or loss, contact the HR Office at 93677 54654.';
            $base['show_designation'] = true;
            $base['show_department'] = true;
            $base['show_employee_code'] = true;
        } elseif ($cardType === 'visitor') {
            $base['primary_color'] = '#B91C1C'; // Security Red / Amber
            $base['secondary_color'] = '#1E293B';
            $base['badge_title'] = 'OFFICIAL VISITOR PASS';
            $base['validity_rule'] = 'same_day'; // same_day, visit_duration
            $base['terms_text'] = '1. Temporary visitor badge valid only for date and time indicated.
2. Must be returned to security gate upon exiting campus.
3. Visitors must be escorted in student areas at all times.';
        }

        return $base;
    }
}
