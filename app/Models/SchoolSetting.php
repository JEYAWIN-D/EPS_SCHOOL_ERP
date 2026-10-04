<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SchoolSetting extends Model
{
    private static ?self $memoizedInstance = null;
    protected $fillable = [
        'school_name', 'school_code', 'affiliation_no', 'board',
        'address', 'city', 'state', 'pincode', 'phone', 'email',
        'website', 'logo', 'principal_name', 'principal_signature',
        'school_stamp', 'gstin', 'pan', 'primary_color',
        'font_size', 'font_family', 'theme_mode', 'ui_density', 'sidebar_preference',
        'currency_symbol', 'date_format', 'timezone',
        'academic_year_format', 'medium',
        'sms_enabled', 'whatsapp_enabled', 'online_payment_enabled',
        // ID card template
        'id_card_header_color', 'id_card_text_color', 'id_card_bg_color',
        'id_card_header_text', 'id_card_footer_text',
        'id_card_show_blood_group', 'id_card_show_dob',
        'id_card_show_qr', 'id_card_show_mobile', 'id_card_show_address',
        'id_card_show_photo',
        // Centralized School Logos & Seals
        'logo_2',
        'seal_1', 'seal_2', 'seal_3', 'seal_4', 'seal_5', 'seal_6',
        'seals_meta',
    ];

    protected $casts = [
        'sms_enabled'            => 'boolean',
        'whatsapp_enabled'       => 'boolean',
        'online_payment_enabled' => 'boolean',
        'id_card_show_blood_group' => 'boolean',
        'id_card_show_dob'       => 'boolean',
        'id_card_show_qr'        => 'boolean',
        'id_card_show_mobile'    => 'boolean',
        'id_card_show_address'   => 'boolean',
        'id_card_show_photo'     => 'boolean',
        'seals_meta'             => 'array',
    ];

    public static function colorPresets(): array
    {
        return [
            'maroon'   => ['label' => 'Maroon',   'hex' => '#8C2826', 'is_default' => true],
            'blue'     => ['label' => 'Blue',     'hex' => '#2563EB', 'is_default' => false],
            'dark_red' => ['label' => 'Dark Red', 'hex' => '#731E1C', 'is_default' => false],
        ];
    }

    public static function fontSizeOptions(): array
    {
        return [
            'small'   => ['label' => 'Small',       'size' => '12px',   'line_height' => '1.5',  'desc' => 'Compact 12px for high-density viewing'],
            'default' => ['label' => 'Default',     'size' => '13.5px', 'line_height' => '1.6',  'desc' => 'Standard 13.5px balanced for all screens'],
            'large'   => ['label' => 'Large',       'size' => '15px',   'line_height' => '1.65', 'desc' => 'Enhanced readability 15px'],
            'xlarge'  => ['label' => 'Extra Large', 'size' => '16.5px', 'line_height' => '1.7',  'desc' => 'Maximum visibility 16.5px'],
        ];
    }

    public static function fontFamilyOptions(): array
    {
        return [
            'default'      => ['label' => 'Inter (Default)',      'css' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif"],
            'poppins'      => ['label' => 'Poppins',              'css' => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif"],
            'plus-jakarta' => ['label' => 'Plus Jakarta Sans',    'css' => "'Plus Jakarta Sans', 'Inter', sans-serif"],
            'system'       => ['label' => 'System UI (Native)',   'css' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"],
        ];
    }

    public static function densityOptions(): array
    {
        return [
            'comfortable' => [
                'label'       => 'Comfortable (Default)',
                'desc'        => 'Generous touch-friendly padding and spacious layout',
                'card_pad'    => '1.25rem 1.5rem',
                'btn_height'  => '36px',
                'input_height'=> '36px',
                'td_pad'      => '11px 14px',
            ],
            'compact' => [
                'label'       => 'Compact',
                'desc'        => 'Optimized tighter padding for data-heavy administrative workflows',
                'card_pad'    => '0.85rem 1.15rem',
                'btn_height'  => '32px',
                'input_height'=> '32px',
                'td_pad'      => '7px 12px',
            ],
        ];
    }

    public static function sidebarOptions(): array
    {
        return [
            'expanded'  => ['label' => 'Expanded (Default)', 'desc' => 'Sidebar open with labels visible'],
            'collapsed' => ['label' => 'Collapsed',          'desc' => 'Sidebar compact with icons only'],
        ];
    }

    public static function instance(): ?self
    {
        if (self::$memoizedInstance !== null) {
            return self::$memoizedInstance;
        }

        self::$memoizedInstance = Cache::remember('school_setting_singleton', 86400, function () {
            try {
                return static::first();
            } catch (\Throwable $e) {
                return null;
            }
        });

        return self::$memoizedInstance;
    }

    public static function clearSettingCache(): void
    {
        self::$memoizedInstance = null;
        Cache::forget('school_setting_singleton');
        Cache::forget('school_theme_css_variables');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearSettingCache();
        });

        static::deleted(function () {
            static::clearSettingCache();
        });
    }

    /**
     * Generate dynamic global CSS variables based on active school settings.
     */
    public static function getThemeCssVariables(): string
    {
        return Cache::rememberForever('school_theme_css_variables', function () {
            $setting = static::instance();

        $hex = strtoupper((string)($setting?->primary_color ?: '#8C2826'));
        $validColors = ['#8C2826', '#2563EB', '#731E1C'];
        if (!in_array($hex, $validColors)) {
            $hex = '#8C2826';
        }

        // RGB decomposition
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        // Hover color (12% darker)
        $hr = max(0, min(255, (int) round($r * 0.88)));
        $hg = max(0, min(255, (int) round($g * 0.88)));
        $hb = max(0, min(255, (int) round($b * 0.88)));
        $hoverHex = sprintf("#%02x%02x%02x", $hr, $hg, $hb);

        // Dark color (28% darker for readable text on light backgrounds)
        $dr = max(0, min(255, (int) round($r * 0.72)));
        $dg = max(0, min(255, (int) round($g * 0.72)));
        $db = max(0, min(255, (int) round($b * 0.72)));
        $darkHex = sprintf("#%02x%02x%02x", $dr, $dg, $db);

        // Lighter tint for gradient and accent
        $lr = min(255, (int) round($r + (255 - $r) * 0.25));
        $lg = min(255, (int) round($g + (255 - $g) * 0.25));
        $lb = min(255, (int) round($b + (255 - $b) * 0.25));
        $lightHex = sprintf("#%02x%02x%02x", $lr, $lg, $lb);

        // Dark sidebar gradient top
        $sdr = max(10, min(60, (int) round($r * 0.35)));
        $sdg = max(10, min(60, (int) round($g * 0.35)));
        $sdb = max(15, min(90, (int) round($b * 0.45)));
        $sidebarTop = sprintf("#%02x%02x%02x", $sdr, $sdg, $sdb);

        // Font size resolution
        $fontSizeKey = $setting?->font_size ?: 'default';
        $fontSizes   = static::fontSizeOptions();
        $fontInfo    = $fontSizes[$fontSizeKey] ?? $fontSizes['default'];

        // Font family resolution
        $fontFamKey  = $setting?->font_family ?: 'default';
        $fontFams    = static::fontFamilyOptions();
        $famInfo     = $fontFams[$fontFamKey] ?? $fontFams['default'];

        // Density resolution
        $densityKey  = $setting?->ui_density ?: 'comfortable';
        $densities   = static::densityOptions();
        $denseInfo   = $densities[$densityKey] ?? $densities['comfortable'];

        $css = "
:root {
  /* Centralized Theme Tokens */
  --primary: {$hex};
  --primary-rgb: {$r}, {$g}, {$b};
  --primary-hover: {$hoverHex};
  --primary-dark: {$darkHex};
  --primary-light: rgba({$r}, {$g}, {$b}, 0.09);
  --primary-light-border: rgba({$r}, {$g}, {$b}, 0.25);
  --accent: {$lightHex};

  /* Aliases for Design System Compatibility */
  --color-primary: var(--primary);
  --color-primary-h: var(--primary-hover);
  --color-primary-l: var(--primary-light);
  --color-primary-d: var(--primary-dark);
  --color-primary-rgb: var(--primary-rgb);
  --color-primary-nav-active: rgba({$r}, {$g}, {$b}, 0.22);
  --color-primary-nav-border: var(--primary);
  --color-primary-nav-icon: {$lightHex};
  --gradient-primary: linear-gradient(135deg, {$hex} 0%, {$lightHex} 100%);
  --gradient-header: linear-gradient(135deg, {$darkHex} 0%, {$hex} 100%);
  --gradient-sidebar: linear-gradient(180deg, {$sidebarTop} 0%, #0F172A 100%);

  /* Typography & Density Tokens */
  --app-font-size: {$fontInfo['size']};
  --app-line-height: {$fontInfo['line_height']};
  --app-font-family: {$famInfo['css']};
  --app-density-card-pad: {$denseInfo['card_pad']};
  --app-density-btn-h: {$denseInfo['btn_height']};
  --app-density-input-h: {$denseInfo['input_height']};
  --app-density-td-pad: {$denseInfo['td_pad']};
}

/* ── Typography & Global Hierarchy ── */
body {
  font-family: var(--app-font-family) !important;
  font-size: var(--app-font-size) !important;
  line-height: var(--app-line-height) !important;
}

/* ── Primary Buttons & Action Triggers Across All Modules ── */
.btn-primary,
button.btn-primary,
a.btn-primary,
input[type=\"submit\"].btn-primary {
  background-color: var(--primary) !important;
  border-color: var(--primary) !important;
  color: #ffffff !important;
}
.btn-primary:hover,
button.btn-primary:hover,
a.btn-primary:hover,
input[type=\"submit\"].btn-primary:hover {
  background-color: var(--primary-hover) !important;
  border-color: var(--primary-hover) !important;
  color: #ffffff !important;
}
.btn-primary:focus {
  box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.3) !important;
}

/* ── Global Primary Classes (Mapping Tailwind Indigo & Action Blue to Central Theme) ── */
.bg-indigo-600,
.bg-indigo-700,
.bg-indigo-500,
.hover\\:bg-indigo-700:hover,
.hover\\:bg-indigo-600:hover {
  background-color: var(--primary) !important;
}
.bg-blue-600:not(.badge-blue):not(.stat-icon),
.bg-blue-700:not(.badge-blue):not(.stat-icon),
.hover\\:bg-blue-700:hover,
.hover\\:bg-blue-600:hover {
  background-color: var(--primary) !important;
}

.text-indigo-600,
.text-indigo-500,
.hover\\:text-indigo-600:hover,
.hover\\:text-indigo-700:hover,
.group-hover\\:text-indigo-600:hover,
.text-blue-600:not(.stat-icon):not(.badge-blue),
.hover\\:text-blue-600:hover {
  color: var(--primary) !important;
}
.text-indigo-700,
.text-indigo-800,
.text-indigo-900,
.text-blue-700:not(.stat-icon):not(.badge-blue),
.text-blue-800:not(.stat-icon):not(.badge-blue) {
  color: var(--primary-dark) !important;
}

.border-indigo-600,
.border-indigo-500,
.border-blue-600 {
  border-color: var(--primary) !important;
}
.border-indigo-100,
.border-indigo-200,
.border-indigo-300 {
  border-color: var(--primary-light-border) !important;
}

.bg-indigo-50,
.bg-indigo-100,
.bg-blue-50:not(.stat-icon):not(.badge-blue) {
  background-color: var(--primary-light) !important;
}

/* ── Focus Rings & States ── */
.ring-indigo-500,
.ring-indigo-400,
.ring-indigo-300,
.ring-indigo-200,
.ring-indigo-100 {
  --tw-ring-color: rgba(var(--primary-rgb), 0.35) !important;
}
.focus\\:ring-indigo-500:focus,
.focus\\:ring-indigo-300:focus {
  --tw-ring-color: rgba(var(--primary-rgb), 0.35) !important;
}
.focus\\:border-indigo-400:focus,
.focus\\:border-indigo-500:focus,
.focus\\:border-indigo-600:focus {
  border-color: var(--primary) !important;
}

/* ── Form Controls Active & Focus States ── */
.input:focus,
.select:focus,
textarea.input:focus,
input[type=\"text\"]:focus,
input[type=\"search\"]:focus,
input[type=\"email\"]:focus,
input[type=\"password\"]:focus,
input[type=\"number\"]:focus,
input[type=\"date\"]:focus,
select:focus,
textarea:focus {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15) !important;
  outline: none !important;
}
input[type=\"radio\"]:checked,
input[type=\"checkbox\"]:checked {
  background-color: var(--primary) !important;
  border-color: var(--primary) !important;
  color: var(--primary) !important;
}

/* ── Sidebar & Navigation Active State ── */
.nav-item.active {
  background: var(--color-primary-nav-active) !important;
  border-left: 3px solid var(--primary) !important;
  color: #ffffff !important;
}
.nav-item.active svg,
.nav-item:hover svg {
  color: var(--color-primary-nav-icon) !important;
}
.nav-item.active::after {
  background: var(--primary) !important;
}

/* ── Badges & Highlights ── */
.badge-indigo {
  background-color: var(--primary-light) !important;
  color: var(--primary-dark) !important;
  border-color: var(--primary-light-border) !important;
}

/* ── Pagination Active State ── */
.pagination .active,
[aria-current=\"page\"] {
  background-color: var(--primary) !important;
  border-color: var(--primary) !important;
  color: #ffffff !important;
}

/* ── Dashboard & Brand Gradient Accents ── */
.from-indigo-500.to-indigo-700,
.from-indigo-600.to-indigo-800,
.bg-gradient-primary {
  background: var(--gradient-primary) !important;
}

/* ── Density Sizing ── */
.card {
  padding: var(--app-density-card-pad) !important;
}
.btn:not(.btn-sm):not(.btn-lg):not(.btn-xs) {
  height: var(--app-density-btn-h) !important;
}
.input:not(.input-sm) {
  height: var(--app-density-input-h) !important;
}
.td {
  padding: var(--app-density-td-pad) !important;
}
";
        return "<style id=\"app-global-theme-vars\">{$css}</style>";
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::instance();
            if (!$setting) return $default;
            $val = $setting->getAttribute($key);
            return $val !== null ? $val : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        try {
            $setting = static::first();
            if ($setting && in_array($key, $setting->getFillable())) {
                $setting->update([$key => $value]);
                static::clearSettingCache();
            }
        } catch (\Throwable $e) {}
    }

    public static function getAll(): ?self
    {
        return static::instance();
    }

    public static function getSingleton(): ?self
    {
        return static::instance();
    }

    // ════════════════════════════════════════════════════════════════════════════
    // CENTRALIZED SCHOOL LOGOS & INSTITUTIONAL SEALS GLOBAL API
    // Reusable anywhere across ERP: Reports, Certificates, Receipts, PDFs, Print
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Default semantic labels for seal slots.
     */
    public static function defaultSealLabels(): array
    {
        return [
            1 => 'Primary School Seal (Institutional Stamp)',
            2 => 'Principal / Headmaster Office Seal',
            3 => 'Examination Cell & Controller Seal',
            4 => 'Accounts & Fee Collection Seal',
            5 => 'Sports & Cultural Activities Seal',
            6 => 'Administrative & Registrar Seal',
        ];
    }

    /**
     * Get web URL for School Logo (Slot 1 or 2).
     */
    public function getLogoUrl(int $slot = 1, ?string $fallback = null): ?string
    {
        $col = $slot === 2 ? 'logo_2' : 'logo';
        $file = $this->getAttribute($col);
        if ($file && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->url($file);
        }
        if ($fallback !== null) {
            return $fallback;
        }
        $defaultAsset = $slot === 2 ? 'images/school-crest.png' : 'images/school-logo.png';
        return file_exists(public_path($defaultAsset)) ? asset($defaultAsset) : null;
    }

    /**
     * Get absolute filesystem path for School Logo (optimal for DomPDF / local scripts).
     */
    public function getLogoPath(int $slot = 1): ?string
    {
        $col = $slot === 2 ? 'logo_2' : 'logo';
        $file = $this->getAttribute($col);
        if ($file && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->path($file);
        }
        $defaultAsset = $slot === 2 ? 'images/school-crest.png' : 'images/school-logo.png';
        $fallbackPath = public_path($defaultAsset);
        return file_exists($fallbackPath) ? $fallbackPath : null;
    }

    /**
     * Get base64 Data URI for School Logo (safe inline rendering for PDF engines).
     */
    public function getLogoBase64(int $slot = 1): ?string
    {
        $col = $slot === 2 ? 'logo_2' : 'logo';
        $file = $this->getAttribute($col);
        if ($file && Storage::disk('public')->exists($file)) {
            $content = Storage::disk('public')->get($file);
            return 'data:image/png;base64,' . base64_encode($content);
        }
        $defaultAsset = $slot === 2 ? 'images/school-crest.png' : 'images/school-logo.png';
        $fallbackPath = public_path($defaultAsset);
        if (file_exists($fallbackPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($fallbackPath));
        }
        return null;
    }

    /**
     * Check if a specific School Logo slot has an uploaded file.
     */
    public function hasLogo(int $slot = 1): bool
    {
        $col = $slot === 2 ? 'logo_2' : 'logo';
        $file = $this->getAttribute($col);
        return !empty($file) && Storage::disk('public')->exists($file);
    }

    /**
     * Get web URL for Institutional Seal (Slots 1 through 6).
     */
    public function getSealUrl(int $slot = 1, ?string $fallback = null): ?string
    {
        $col = "seal_{$slot}";
        $file = $this->getAttribute($col) ?: ($slot === 1 ? $this->school_stamp : null);
        if ($file && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->url($file);
        }
        return $fallback;
    }

    /**
     * Get absolute filesystem path for Institutional Seal.
     */
    public function getSealPath(int $slot = 1): ?string
    {
        $col = "seal_{$slot}";
        $file = $this->getAttribute($col) ?: ($slot === 1 ? $this->school_stamp : null);
        if ($file && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->path($file);
        }
        return null;
    }

    /**
     * Get base64 Data URI for Institutional Seal (inline rendering for DomPDF / printing).
     */
    public function getSealBase64(int $slot = 1): ?string
    {
        $col = "seal_{$slot}";
        $file = $this->getAttribute($col) ?: ($slot === 1 ? $this->school_stamp : null);
        if ($file && Storage::disk('public')->exists($file)) {
            $content = Storage::disk('public')->get($file);
            return 'data:image/png;base64,' . base64_encode($content);
        }
        return null;
    }

    /**
     * Check if a specific Institutional Seal slot has an uploaded file.
     */
    public function hasSeal(int $slot = 1): bool
    {
        $col = "seal_{$slot}";
        $file = $this->getAttribute($col) ?: ($slot === 1 ? $this->school_stamp : null);
        return !empty($file) && Storage::disk('public')->exists($file);
    }

    /**
     * Get human-readable label for Institutional Seal slot.
     */
    public function getSealLabel(int $slot = 1): string
    {
        $defaults = static::defaultSealLabels();
        $meta = $this->seals_meta ?? [];
        return !empty($meta[$slot]) ? $meta[$slot] : ($defaults[$slot] ?? "Institutional Seal Slot {$slot}");
    }

    /**
     * Get comprehensive array of all configured School Logos.
     */
    public function getLogosList(): array
    {
        return [
            1 => [
                'slot'        => 1,
                'key'         => 'logo',
                'title'       => 'Primary School Logo',
                'description' => 'Main institutional brand used for header navigation, report banners, and official stationery.',
                'url'         => $this->getLogoUrl(1),
                'path'        => $this->getLogoPath(1),
                'has_file'    => $this->hasLogo(1),
                'file_name'   => $this->logo,
            ],
            2 => [
                'slot'        => 2,
                'key'         => 'logo_2',
                'title'       => 'Secondary Logo / Crest & Emblem',
                'description' => 'Compact school crest/emblem used on student ID cards, hall tickets, and compact sidebar view.',
                'url'         => $this->getLogoUrl(2),
                'path'        => $this->getLogoPath(2),
                'has_file'    => $this->hasLogo(2),
                'file_name'   => $this->logo_2,
            ],
        ];
    }

    /**
     * Get comprehensive array of all 6 Institutional Seals.
     */
    public function getSealsList(): array
    {
        $list = [];
        $defaults = static::defaultSealLabels();
        for ($i = 1; $i <= 6; $i++) {
            $col = "seal_{$i}";
            $list[$i] = [
                'slot'          => $i,
                'key'           => $col,
                'label'         => $this->getSealLabel($i),
                'default_label' => $defaults[$i] ?? "Seal Slot {$i}",
                'url'           => $this->getSealUrl($i),
                'path'          => $this->getSealPath($i),
                'has_file'      => $this->hasSeal($i),
                'file_name'     => $this->getAttribute($col) ?: ($i === 1 ? $this->school_stamp : null),
            ];
        }
        return $list;
    }

    // ── Static Helper Methods (accessible anywhere without instance) ──────────

    public static function logoUrl(int $slot = 1, ?string $fallback = null): ?string
    {
        return static::instance()?->getLogoUrl($slot, $fallback);
    }

    public static function logoPath(int $slot = 1): ?string
    {
        return static::instance()?->getLogoPath($slot);
    }

    public static function logoBase64(int $slot = 1): ?string
    {
        return static::instance()?->getLogoBase64($slot);
    }

    public static function sealUrl(int $slot = 1, ?string $fallback = null): ?string
    {
        return static::instance()?->getSealUrl($slot, $fallback);
    }

    public static function sealPath(int $slot = 1): ?string
    {
        return static::instance()?->getSealPath($slot);
    }

    public static function sealBase64(int $slot = 1): ?string
    {
        return static::instance()?->getSealBase64($slot);
    }

    public static function sealLabel(int $slot = 1): string
    {
        return static::instance()?->getSealLabel($slot) ?? (static::defaultSealLabels()[$slot] ?? "Seal Slot {$slot}");
    }

    public static function allLogos(): array
    {
        $inst = static::instance();
        return $inst ? $inst->getLogosList() : [];
    }

    public static function allSeals(): array
    {
        $inst = static::instance();
        return $inst ? $inst->getSealsList() : [];
    }
}
