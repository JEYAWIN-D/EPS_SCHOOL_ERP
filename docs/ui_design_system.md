# School ERP — UI Design System
**Style:** Light + Soft Gradients | Modern SaaS feel
**Library:** Tailwind CSS v3 + Custom Components
**Theme:** Blue-primary with multi-school CSS variable theming
**Vibe:** Clean, premium, "wow" — like Linear meets Google Workspace

---

## 1. COLOUR PALETTE

### Primary Brand (Blue)
```css
--color-primary-50:  #EFF6FF;   /* lightest tint — hover backgrounds */
--color-primary-100: #DBEAFE;   /* light tint — badge backgrounds */
--color-primary-200: #BFDBFE;   /* border accents */
--color-primary-300: #93C5FD;   /* disabled states */
--color-primary-400: #60A5FA;   /* icons, secondary actions */
--color-primary-500: #3B82F6;   /* main brand blue */
--color-primary-600: #2563EB;   /* primary buttons, links */
--color-primary-700: #1D4ED8;   /* button hover */
--color-primary-800: #1E40AF;   /* dark accents */
--color-primary-900: #1E3A8A;   /* dark text on light bg */
```

### Gradient Accent (Blue → Indigo)
```css
--gradient-primary:   linear-gradient(135deg, #3B82F6 0%, #6366F1 100%);
--gradient-header:    linear-gradient(135deg, #1D4ED8 0%, #4F46E5 100%);
--gradient-card:      linear-gradient(145deg, #EFF6FF 0%, #EEF2FF 100%);
--gradient-sidebar:   linear-gradient(180deg, #1E3A8A 0%, #1E1B4B 100%);
--gradient-success:   linear-gradient(135deg, #10B981 0%, #059669 100%);
--gradient-warning:   linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
--gradient-danger:    linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
```

### Semantic Colours
```css
--color-success:  #10B981;   /* attendance present, paid, active */
--color-warning:  #F59E0B;   /* pending, due soon */
--color-danger:   #EF4444;   /* absent, overdue, failed */
--color-info:     #06B6D4;   /* informational, in-progress */
--color-purple:   #8B5CF6;   /* premium features, Phase 3 */
--color-orange:   #F97316;   /* alerts, fire-priority */
```

### Neutral / Surface
```css
--color-bg:        #F8FAFC;   /* page background */
--color-surface:   #FFFFFF;   /* card / modal background */
--color-border:    #E2E8F0;   /* dividers, card borders */
--color-muted:     #F1F5F9;   /* table zebra, input bg */
--color-text-base: #0F172A;   /* primary text */
--color-text-muted:#64748B;   /* labels, placeholders */
--color-text-light:#94A3B8;   /* disabled, hints */
```

### Multi-School Theme Override
Each school can override only the primary palette via their settings:
```css
/* Applied dynamically via inline :root on <html> */
:root {
  --color-primary-500: /* school hex */;
  --color-primary-600: /* darkened */;
  --gradient-primary:  /* school gradient */;
}
```

---

## 2. TYPOGRAPHY

### Font Stack
```
Primary:  'Inter' (Google Fonts) — UI, body text, labels
Display:  'Plus Jakarta Sans' (Google Fonts) — headings, hero numbers
Mono:     'JetBrains Mono' — codes, IDs, amounts
```

### Scale (Tailwind extended)
| Token | Size | Weight | Use |
|-------|------|--------|-----|
| `text-display` | 36px / 2.25rem | 700 | Dashboard stat numbers |
| `text-h1` | 28px / 1.75rem | 700 | Page titles |
| `text-h2` | 22px / 1.375rem | 600 | Section headings |
| `text-h3` | 18px / 1.125rem | 600 | Card headings |
| `text-body-lg` | 16px / 1rem | 400 | Body copy |
| `text-body` | 14px / 0.875rem | 400 | Default UI text |
| `text-sm` | 13px / 0.8125rem | 400 | Labels, table cells |
| `text-xs` | 11px / 0.6875rem | 500 | Badges, tags, timestamps |

### Line Height & Tracking
```css
body { line-height: 1.6; letter-spacing: -0.01em; }
h1, h2 { line-height: 1.2; letter-spacing: -0.025em; }
.mono { font-variant-numeric: tabular-nums; }  /* for stats */
```

---

## 3. SPACING & LAYOUT

### Base Grid
- Layout: **Sidebar (260px fixed) + Main content area**
- Sidebar collapses to icon-only (64px) on toggle
- Content max-width: **1440px** (centred on ultra-wide)
- Page padding: `px-6 py-6` (desktop) → `px-4 py-4` (mobile)
- Card gap: `gap-6`

### Spacing Scale (key tokens)
```
4px  → xs  (tight badges, icon padding)
8px  → sm  (input padding, chip gap)
12px → md  (form field gap)
16px → lg  (card padding, section spacing)
24px → xl  (between cards)
32px → 2xl (section breaks)
48px → 3xl (page sections)
```

### Breakpoints (Tailwind defaults)
```
sm:  640px   (large phones landscape)
md:  768px   (tablets)
lg:  1024px  (small laptops)
xl:  1280px  (desktop)
2xl: 1536px  (wide desktop)
```

---

## 4. COMPONENTS

### 4.1 Cards
```
Base card:
  bg-white rounded-2xl border border-slate-200
  shadow-sm hover:shadow-md transition-shadow duration-200
  p-6

Gradient stat card (dashboard KPI):
  bg-gradient-to-br from-blue-500 to-indigo-600
  text-white rounded-2xl p-6
  relative overflow-hidden
  ::before decorative circle (opacity-10, scale-150, top-right)

Glass card (modals, overlays):
  bg-white/80 backdrop-blur-sm
  border border-white/60
  rounded-2xl shadow-xl
```

### 4.2 Buttons
```
Primary:
  bg-gradient-to-r from-blue-600 to-indigo-600
  hover:from-blue-700 hover:to-indigo-700
  text-white font-semibold px-5 py-2.5 rounded-xl
  shadow-sm hover:shadow-blue-200 hover:shadow-lg
  transition-all duration-200

Secondary:
  bg-white border border-slate-200 text-slate-700
  hover:bg-slate-50 hover:border-slate-300
  px-5 py-2.5 rounded-xl font-medium
  transition-all duration-150

Danger:
  bg-gradient-to-r from-red-500 to-rose-600
  text-white px-5 py-2.5 rounded-xl

Ghost:
  text-blue-600 hover:bg-blue-50
  px-4 py-2 rounded-lg font-medium

Icon button:
  w-9 h-9 flex items-center justify-center
  rounded-lg hover:bg-slate-100 text-slate-500
  hover:text-slate-700 transition

Sizes: sm (px-3 py-1.5 text-sm), md (default), lg (px-6 py-3 text-base)
```

### 4.3 Form Inputs
```
Text Input:
  bg-white border border-slate-200 rounded-xl
  px-4 py-2.5 text-sm text-slate-800
  placeholder:text-slate-400
  focus:outline-none focus:ring-2 focus:ring-blue-500/30
  focus:border-blue-500 transition-all duration-150

Select:
  Same as text input + custom chevron (no browser default arrow)

Floating Label Input:
  label floats up on focus / when value present
  Uses Alpine.js for state

Input with prefix icon:
  icon left-padded (pl-10), icon in absolute position inside wrapper

Error state:
  border-red-400 focus:ring-red-500/30
  error message: text-xs text-red-500 mt-1

Success state:
  border-green-400 focus:ring-green-500/30

File Upload:
  Drag-and-drop zone: border-2 border-dashed border-slate-300
  hover:border-blue-400 hover:bg-blue-50/50 rounded-xl
  transition-all duration-200
```

### 4.4 Tables
```
Wrapper: rounded-2xl border border-slate-200 overflow-hidden shadow-sm

Header row:
  bg-gradient-to-r from-slate-50 to-slate-100
  text-xs font-semibold text-slate-500 uppercase tracking-wide
  px-4 py-3

Body row:
  bg-white border-b border-slate-100 last:border-0
  hover:bg-blue-50/40 transition-colors duration-100

Zebra (optional):
  even:bg-slate-50/50

Cell padding: px-4 py-3

Sticky header on scroll: thead with sticky top-0 z-10

Responsive: horizontal scroll on mobile inside overflow-x-auto wrapper

Empty state:
  Full-width row with illustration + message
  "No records found. Try adjusting filters."
```

### 4.5 Badges / Status Chips
```
Base: px-2.5 py-0.5 rounded-full text-xs font-semibold

Present / Active / Paid:
  bg-green-100 text-green-700

Absent / Inactive / Overdue:
  bg-red-100 text-red-700

Pending / Due:
  bg-amber-100 text-amber-700

In Progress / Partial:
  bg-blue-100 text-blue-700

Cancelled / Rejected:
  bg-slate-100 text-slate-500

Premium / Phase 3:
  bg-gradient-to-r from-purple-500 to-indigo-500 text-white
```

### 4.6 Sidebar Navigation
```
Background: bg-gradient-to-b from-slate-900 to-slate-800
Width: 260px (expanded) / 64px (collapsed)
Transition: width 250ms cubic-bezier(0.4, 0, 0.2, 1)

School logo + name: top, px-5 py-4, logo 36px circle with ring

Nav item (default):
  flex items-center gap-3 px-4 py-2.5 rounded-xl mx-2
  text-slate-400 text-sm font-medium
  hover:bg-white/10 hover:text-white
  transition-all duration-150

Nav item (active):
  bg-gradient-to-r from-blue-500/20 to-indigo-500/20
  text-white border-l-2 border-blue-400
  shadow-sm

Nav group label:
  text-slate-500 text-xs font-semibold uppercase tracking-widest
  px-6 py-2 mt-4

Icon: 18px, flex-shrink-0

Collapsed mode: only icon visible, tooltip on hover

Bottom section: user avatar + name + logout
```

### 4.7 Page Header
```
Each page top:
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Page Title</h1>
      <p class="text-sm text-slate-500 mt-0.5">Breadcrumb or subtitle</p>
    </div>
    <div class="flex items-center gap-3">
      <!-- Action buttons -->
    </div>
  </div>

Breadcrumb: text-xs text-slate-400 with › separator
Active breadcrumb: text-blue-600 font-medium
```

### 4.8 Modals / Drawers
```
Overlay: bg-slate-900/50 backdrop-blur-sm fixed inset-0 z-40

Modal:
  bg-white rounded-2xl shadow-2xl
  max-w-lg w-full mx-4 z-50
  animate: scale-95 opacity-0 → scale-100 opacity-100
  duration-200 ease-out

Header: px-6 py-4 border-b border-slate-100
  Title: text-lg font-semibold text-slate-800
  Close button: top-right, X icon

Body: px-6 py-5

Footer: px-6 py-4 border-t border-slate-100
  flex justify-end gap-3

Side Drawer (for forms):
  fixed right-0 top-0 h-full w-[480px]
  bg-white shadow-2xl z-50
  translate-x-full → translate-x-0 transition duration-300
  Used for: Add Student, Add Staff, Quick Edit forms
```

### 4.9 Dashboard KPI Cards
```
Layout: 4-column grid on desktop, 2-col tablet, 1-col mobile

Stat card:
  ┌────────────────────────────┐
  │  Icon (gradient circle)    │
  │  2,547          ↑ 12.5%   │
  │  Total Students   vs last  │
  │                   month    │
  └────────────────────────────┘

Icon circle: w-12 h-12 rounded-xl bg-gradient-to-br
  Blue: from-blue-500 to-indigo-600
  Green: from-emerald-500 to-teal-600
  Orange: from-orange-400 to-amber-500
  Purple: from-purple-500 to-pink-500

Stat number: text-3xl font-bold font-display text-slate-800
Change indicator:
  ↑ positive: text-green-600 bg-green-50 rounded-full px-2 py-0.5 text-xs
  ↓ negative: text-red-500 bg-red-50
```

### 4.10 Charts (using Chart.js or ApexCharts via CDN)
```
Wrapper: bg-white rounded-2xl border border-slate-200 p-6 shadow-sm

Chart types used:
  - Line chart: attendance trends, fee collection trends
  - Bar chart: class-wise performance, monthly comparisons
  - Donut chart: fee collection split, seat fill ratio
  - Area chart: student strength over years
  - Heatmap: attendance heatmap

Colour palette for charts:
  Series 1: #3B82F6 (blue)
  Series 2: #8B5CF6 (violet)
  Series 3: #10B981 (emerald)
  Series 4: #F59E0B (amber)
  Series 5: #EF4444 (red)
  Series 6: #06B6D4 (cyan)

Grid: subtle #F1F5F9 lines, no border
Tooltip: white bg, rounded-xl, shadow-lg, Inter font
Legend: below chart, text-sm text-slate-600
```

### 4.11 Notifications & Alerts
```
Toast notification (top-right corner):
  fixed top-4 right-4 z-[9999] flex flex-col gap-2

  Success toast:
    bg-white border-l-4 border-green-500
    shadow-lg rounded-xl px-4 py-3
    flex items-center gap-3
    animate: slide-in from right

  Error toast: border-red-500
  Warning toast: border-amber-500
  Info toast: border-blue-500

Inline alert banners:
  rounded-xl px-4 py-3 flex items-start gap-3
  Success: bg-green-50 border border-green-200 text-green-800
  Warning: bg-amber-50 border border-amber-200 text-amber-800
  Error: bg-red-50 border border-red-200 text-red-800
  Info: bg-blue-50 border border-blue-200 text-blue-800
```

### 4.12 Loading & Empty States
```
Page skeleton loader:
  Animated pulse (Tailwind animate-pulse)
  bg-slate-200 rounded blocks matching layout shape
  No spinner — skeleton only

Button loading:
  Spinner SVG (20px) replaces button text
  Button disabled during load
  bg-opacity-80 cursor-not-allowed

Empty state (tables/lists):
  Centred in container, py-16
  Illustration: simple SVG (consistent style)
  Heading: text-slate-500 font-medium
  Sub: text-sm text-slate-400
  CTA button if applicable

404 / Error page:
  Full page, gradient bg, large error code in gradient text
  Friendly message + back button
```

---

## 5. ANIMATIONS & MICRO-INTERACTIONS

```css
/* All transitions follow this timing standard */
--transition-fast:   150ms ease;
--transition-base:   200ms ease;
--transition-slow:   300ms cubic-bezier(0.4, 0, 0.2, 1);

/* Page entry animation */
.page-enter {
  animation: fadeSlideUp 250ms ease forwards;
}
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Card hover */
.card { transition: box-shadow 200ms ease, transform 200ms ease; }
.card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-1px); }

/* Button press */
.btn:active { transform: scale(0.97); }

/* Stat number count-up on dashboard load */
/* Use Alpine.js countUp directive or simple JS */

/* Sidebar item active indicator */
.nav-item.active::before {
  content: '';
  position: absolute; left: 0;
  width: 3px; height: 60%;
  background: linear-gradient(to bottom, #3B82F6, #6366F1);
  border-radius: 0 4px 4px 0;
}

/* Modal open */
.modal-enter { animation: modalIn 200ms ease; }
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(-8px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Notification toast slide-in */
@keyframes slideInRight {
  from { opacity: 0; transform: translateX(100%); }
  to   { opacity: 1; transform: translateX(0); }
}
```

---

## 6. ICONS

**Library:** Heroicons v2 (MIT, SVG, Tailwind-native)
- Use outline variant for nav and actions
- Use solid variant for filled states and active indicators

**Sizes:**
```
xs icon: w-3.5 h-3.5   (badges, inline)
sm icon: w-4 h-4       (buttons, table actions)
md icon: w-5 h-5       (default, nav icons)
lg icon: w-6 h-6       (card headers)
xl icon: w-8 h-8       (section icons, empty states)
2xl icon: w-12 h-12    (KPI cards, feature icons)
```

---

## 7. SIDEBAR LAYOUT STRUCTURE

```
┌──────────────────────────────────────────────────────────────┐
│  SIDEBAR (260px)              │  MAIN CONTENT               │
│  ─────────────────────        │  ──────────────────────────  │
│  [Logo] School Name    ≡      │  [Page Header]               │
│                               │   Title + Breadcrumb + CTA   │
│  MAIN MENU                    │                              │
│  ○ Dashboard                  │  [KPI Cards Row]             │
│  ○ Admissions              ▸  │  ┌────┐ ┌────┐ ┌────┐ ┌────┐│
│  ○ Students                ▸  │  │    │ │    │ │    │ │    ││
│  ○ Academics               ▸  │  └────┘ └────┘ └────┘ └────┘│
│  ○ Attendance                 │                              │
│  ○ Examinations            ▸  │  [Charts / Tables]           │
│  ○ Fee Management          ▸  │  ┌─────────────┐ ┌────────┐ │
│  ○ HR & Payroll            ▸  │  │  Line Chart │ │ Donut  │ │
│  ○ Library                    │  └─────────────┘ └────────┘ │
│  ○ Transport               ▸  │                              │
│  ○ Hostel                  ▸  │  [Data Table]                │
│                               │  ┌──────────────────────────┐│
│  TOOLS                        │  │ search │ filters │ export ││
│  ○ Reports                    │  ├────────────────────────── ┤│
│  ○ Communication              │  │ rows...                   ││
│  ○ LMS                        │  └──────────────────────────┘│
│  ○ Events                     │                              │
│  ○ Visitors                   │                              │
│  ○ Inventory                  │                              │
│  ○ Alumni                     │                              │
│                               │                              │
│  SYSTEM                       │                              │
│  ○ Settings                   │                              │
│  ○ Users & Roles              │                              │
│                               │                              │
│  ─────────────────────        │                              │
│  [Avatar] John Doe      ⋮     │                              │
└───────────────────────────────┴──────────────────────────────┘
```

---

## 8. ROLE-SPECIFIC DASHBOARD FEEL

| Role | Dashboard Accent | Key Widgets |
|------|-----------------|-------------|
| Admin / Principal | Full blue gradient sidebar | All KPIs, all charts |
| Accountant | Green tones on fee widgets | Fee collection, defaulters |
| Teacher | Soft blue | Class attendance, homework, timetable |
| Librarian | Teal accents | Issued books, overdue, stock |
| Parent | Minimal, white | Child attendance, fee due, results |
| Student | Friendly, rounded | Timetable, homework, marks |

---

## 9. MOBILE / RESPONSIVE APPROACH

```
Sidebar: hidden off-canvas on mobile, toggle via hamburger
  Overlay drawer with backdrop on mobile

Tables: horizontal scroll on small screens
  Key action (Add button) pinned bottom-right as FAB on mobile

Cards: single column on mobile, 2-col on tablet, 4-col on desktop

Modals: full-screen bottom sheet on mobile
  (transform from center-modal to bottom-drawer at sm breakpoint)

Typography: scale down 1 step on mobile
  (h1: 28px desktop → 22px mobile)

Touch targets: min 44px height for all interactive elements
```

---

## 10. TAILWIND CONFIG ADDITIONS

```js
// tailwind.config.js additions
module.exports = {
  theme: {
    extend: {
      fontFamily: {
        sans:    ['Inter', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'sans-serif'],
        mono:    ['JetBrains Mono', 'monospace'],
      },
      colors: {
        primary: {
          50: 'var(--color-primary-50)',
          // ... 100 to 900
          500: 'var(--color-primary-500)',
          600: 'var(--color-primary-600)',
        },
        surface: 'var(--color-surface)',
        border:  'var(--color-border)',
        muted:   'var(--color-muted)',
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      boxShadow: {
        'card':     '0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06)',
        'card-md':  '0 4px 16px rgba(0,0,0,0.08)',
        'card-lg':  '0 8px 30px rgba(0,0,0,0.10)',
        'blue-glow':'0 4px 20px rgba(59,130,246,0.25)',
      },
      animation: {
        'fade-up':     'fadeSlideUp 250ms ease forwards',
        'slide-right': 'slideInRight 300ms ease forwards',
        'modal-in':    'modalIn 200ms ease forwards',
        'count-up':    'countUp 600ms ease forwards',
        'pulse-slow':  'pulse 3s ease-in-out infinite',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

---

## 11. PAGE-SPECIFIC WOW MOMENTS

| Page | Wow Element |
|------|-------------|
| Login | Full-page gradient bg (#1D4ED8 → #4F46E5), floating school logo, blur glass form card |
| Dashboard | Count-up animation on KPI numbers on load, subtle confetti on new admission |
| Fee Receipt | Clean A4 PDF with school watermark, gradient header strip |
| Report Card | Graphical chart embedded in PDF, school crest watermark |
| Attendance Mark | One-click bulk-mark with green flash confirmation |
| Student Profile | Full-width photo header, tab navigation, timeline for history |
| Timetable | Drag-and-drop period blocks with colour per subject |
| Admission Pipeline | Kanban-style board (Enquiry → Confirmed columns) |
| Notification | Toast slides in with sound pulse (optional) |
| 404 Page | Animated gradient background, friendly message |

---

*Design System maintained by DC Innovision Pvt Ltd | v1.0 | June 2026*
