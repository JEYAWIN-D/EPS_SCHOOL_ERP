<?php

namespace Database\Seeders;

use App\Models\Notice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CircularOrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $samples = [
            [
                'title' => 'CBSE Secondary & Senior Secondary Term-1 Practical Guidelines 2026-27',
                'reference_no' => 'EPS/CIR/2026-27/001',
                'notice_type' => 'circular',
                'order_category' => 'academic',
                'issuing_authority' => 'Office of the Principal & Academic Council',
                'signed_by_name' => 'Dr. K. S. Ramanathan, M.Sc., M.Ed., Ph.D.',
                'signatory_designation' => 'Principal & Regional Center Coordinator',
                'target_audience' => 'all',
                'priority' => 'high',
                'is_published' => true,
                'requires_acknowledgement' => true,
                'is_pinned' => true,
                'publish_date' => Carbon::now()->subDays(2)->toDateString(),
                'content' => "All department heads, faculty members, and students of Classes IX through XII are hereby notified regarding the CBSE Term-1 practical evaluation schedule and internal assessment protocols.\n\n1. Laboratories must be audited for safety norms and equipment calibration by October 5, 2026.\n2. External viva panels will be hosted in conjunction with regional center guidelines.\n3. Student attendance is mandatory; no re-examination will be entertained without prior approval.",
            ],
            [
                'title' => 'Administrative Order: Anti-Bullying and Student Safety Oversight Committee',
                'reference_no' => 'EPS/ORD/2026-27/004',
                'notice_type' => 'order',
                'order_category' => 'statutory',
                'issuing_authority' => 'Board of Trustees & Correspondent Secretariat',
                'signed_by_name' => 'Er. S. Chandrasekaran, B.E., M.I.E.',
                'signatory_designation' => 'Correspondent & Managing Trustee',
                'target_audience' => 'staff',
                'priority' => 'urgent',
                'is_published' => true,
                'requires_acknowledgement' => true,
                'is_pinned' => true,
                'publish_date' => Carbon::now()->subDay()->toDateString(),
                'content' => "In accordance with Supreme Court directives and CBSE Safety Bye-laws, the School Safety and Anti-Bullying Committee is reconstituted with immediate effect for the academic year 2026-27.\n\nCommittee Composition:\n- Chairperson: Dr. K. S. Ramanathan (Principal)\n- Convener: Smt. R. Lakshmi (Vice-Principal, Academics)\n- Member Secretary: Mr. V. Anand (Head of Physical Education)\n- Medical Officer: Dr. M. Karthik, MBBS\n\nThe committee shall convene on the first Monday of every month and submit an audit log directly to the Correspondent's office.",
            ],
            [
                'title' => 'Government Order (G.O.): Declaration of Gandhi Jayanti Holiday & Swachhata Pakhwada',
                'reference_no' => 'EPS/ORD/2026-27/005',
                'notice_type' => 'order',
                'order_category' => 'government',
                'issuing_authority' => 'District Educational Office & Management',
                'signed_by_name' => 'Thiru. P. Muruganandam, M.A., B.Ed.',
                'signatory_designation' => 'Administrative Officer',
                'target_audience' => 'all',
                'priority' => 'normal',
                'is_published' => true,
                'requires_acknowledgement' => false,
                'is_pinned' => false,
                'publish_date' => Carbon::now()->toDateString(),
                'content' => "Pursuant to the State Government Gazette notification, the school will observe a general holiday on October 2nd, 2026 on account of Gandhi Jayanti.\n\nAll staff and students are encouraged to participate in the 'Swachhata Hi Seva' community cleanliness drive scheduled for October 1st from 08:30 AM to 10:30 AM at the school campus.",
            ],
            [
                'title' => 'Staff Administrative Circular: Digital Attendance & Biometric Synchronization',
                'reference_no' => 'EPS/CIR/2026-27/008',
                'notice_type' => 'circular',
                'order_category' => 'administrative',
                'issuing_authority' => 'Human Resources & Campus Administration',
                'signed_by_name' => 'Mr. R. Parthiban, M.B.A.',
                'signatory_designation' => 'HR & Operations Manager',
                'target_audience' => 'staff',
                'priority' => 'high',
                'is_published' => true,
                'requires_acknowledgement' => true,
                'is_pinned' => false,
                'publish_date' => Carbon::now()->toDateString(),
                'content' => "Effective October 1st, 2026, all teaching and non-teaching personnel must register their biometric check-in by 08:15 AM. Three grace instances of up to 10 minutes are permitted per calendar month.\n\nLeave applications must be submitted via the ERP Portal at least 24 hours in advance.",
            ],
        ];

        foreach ($samples as $item) {
            $item['created_by'] = $adminId;
            Notice::updateOrCreate(
                ['reference_no' => $item['reference_no']],
                $item
            );
        }
    }
}
