<?php

namespace Database\Seeders;

use App\Models\BankingDetail;
use App\Models\BusinessAmendment;
use App\Models\BusinessRegistration;
use App\Models\Employer;
use App\Models\MobileMoneyDetail;
use App\Models\PhoneDetail;
use App\Models\RegistrationFile;
use App\Models\RegistrationOperationEvent;
use App\Models\RiitReturn;
use App\Models\RiitReturnAttachment;
use App\Models\Role;
use App\Models\TinRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->demoUser('demo.admin@rsl.org.ls', 'Demo Admin', 'demo.admin', ['admin', 'digital_services']);
        $dsOne = $this->demoUser('demo.ds1@rsl.org.ls', 'Demo DS Officer', 'demo.ds1', ['digital_services']);
        $dsTwo = $this->demoUser('demo.ds2@rsl.org.ls', 'Demo Senior DS Officer', 'demo.ds2', ['digital_services']);
        $auditor = $this->demoUser('demo.audit@rsl.org.ls', 'Demo Audit User', 'demo.audit', ['audit']);

        $individuals = $this->seedIndividuals($dsOne, $dsTwo, $admin);
        $businesses = $this->seedBusinesses($dsOne, $dsTwo, $admin);
        $this->seedBusinessAmendments($businesses, $dsOne, $admin);
        $this->seedRiitReturns();

        $this->command?->info('Demo data created: '
            . count($individuals) . ' individual records, '
            . count($businesses) . ' business records, RIIT returns, users, and operation events.'
        );
    }

    private function demoUser(string $email, string $name, string $username, array $roles): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'department' => str_contains($username, 'ds') ? 'Digital Services' : 'Demo',
                'role' => $roles[0] ?? 'user',
                'password' => Hash::make('password'),
                'wso2_id' => $username,
                'wso2_username' => $username,
                'wso2_attributes' => ['source' => 'demo-seeder'],
                'is_active' => true,
                'last_login_at' => now()->subDays(rand(1, 8)),
            ]
        );

        $roleIds = Role::whereIn('name', $roles)->pluck('id')->all();
        if ($roleIds) {
            $user->roles()->sync($roleIds);
        }

        return $user;
    }

    private function seedIndividuals(User $dsOne, User $dsTwo, User $admin): array
    {
        $rows = [
            ['DEMO-IND-0001', 'NEW', 'PENDING', null, 'Neo', 'Mokoena', null, 5],
            ['DEMO-IND-0002', 'NEW', 'UNDER_REVIEW', $dsOne, 'Lerato', 'Theko', null, 3],
            ['DEMO-IND-0003', 'NEW', 'APPROVED', $dsTwo, 'Thabo', 'Rantso', 'IND202605240001', 9],
            ['DEMO-IND-0004', 'NEW', 'REJECTED', $dsOne, 'Palesa', 'Molefe', null, 12],
            ['DEMO-IND-0005', 'AMND', 'PENDING', null, 'Mpho', 'Sekoati', 'IND202402010014', 2],
            ['DEMO-IND-0006', 'AMND', 'UNDER_REVIEW', $dsTwo, 'Karabo', 'Makara', 'IND202303110022', 1],
        ];

        return collect($rows)->map(function ($row, $index) use ($admin) {
            [$ref, $type, $status, $assignee, $forenames, $surname, $tin, $age] = $row;
            $createdAt = now()->subDays($age);

            $registration = TinRegistration::updateOrCreate(
                ['ref' => $ref],
                [
                    'document_locator' => 'DEMOIND' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'receive_date' => $createdAt->toDateString(),
                    'effective_date' => $createdAt->toDateString(),
                    'registration_type' => $type,
                    'legacy_tin' => $type === 'AMND' ? $tin : null,
                    'tin' => $tin,
                    'title' => $index % 2 === 0 ? 'MR' : 'MS',
                    'surname' => $surname,
                    'forenames' => $forenames,
                    'date_of_birth' => now()->subYears(24 + $index)->toDateString(),
                    'country_of_birth' => 'LS',
                    'country_of_citizenship' => 'LS',
                    'country_of_residence' => 'LS',
                    'lesotho_id_number' => 'DEMOID' . str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                    'lesotho_id_expiry' => now()->addYears(4)->toDateString(),
                    'country_of_issue' => 'LS',
                    'post_country' => 'LS',
                    'post_type' => 'PBOX',
                    'post_number' => '10' . $index,
                    'post_code' => '100',
                    'post_address1' => 'Maseru',
                    'post_district' => 'Maseru',
                    'physical_country' => 'LS',
                    'street_name' => 'Demo Street ' . ($index + 1),
                    'village' => 'Thetsane',
                    'town' => 'Maseru',
                    'physical_district' => 'Maseru',
                    'phone_type' => 'CEL1',
                    'phone_code' => '+266',
                    'phone_number' => '58' . str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                    'email' => strtolower($forenames . '.' . $surname) . '@example.test',
                    'email_verified' => true,
                    'marital_status' => $index % 3 === 0 ? 'MARR' : 'SING',
                    'mobile_money_type' => 'MPESA',
                    'mobile_money_number' => '58' . str_pad((string) (200000 + $index), 6, '0', STR_PAD_LEFT),
                    'bank_name' => 'FNB',
                    'bank_country' => 'LS',
                    'printed_name' => $forenames . ' ' . $surname,
                    'declaration_accepted' => true,
                    'status' => $status,
                    'assigned_to' => $assignee?->id,
                    'remarks' => $status === 'REJECTED' ? 'Demo rejection: supporting document was unclear.' : null,
                    'created_at' => $createdAt,
                    'updated_at' => now()->subDays(max($age - 1, 0)),
                ]
            );

            PhoneDetail::updateOrCreate(['tin_registration_id' => $registration->id], [
                'phone_type' => 'CEL1',
                'phone_code' => '+266',
                'phone_number' => $registration->phone_number,
            ]);

            Employer::updateOrCreate(['tin_registration_id' => $registration->id], [
                'employer_name' => $index % 2 === 0 ? 'RSL Demo Employer' : 'Maseru Demo Trading',
                'file_path' => null,
            ]);

            BankingDetail::updateOrCreate(['tin_registration_id' => $registration->id], [
                'bank_code' => 'FNB',
                'bank_country' => 'LS',
                'account_holder_name' => $registration->printed_name,
                'branch' => 'Maseru',
                'account_number' => '99' . str_pad((string) (700000 + $index), 8, '0', STR_PAD_LEFT),
                'account_type' => 'CURR',
                'swift_code' => 'FIRNLSMX',
                'branch_code' => '280061',
            ]);

            MobileMoneyDetail::updateOrCreate(['tin_registration_id' => $registration->id], [
                'mobile_money_type' => 'MPESA',
                'mobile_money_number' => $registration->mobile_money_number,
            ]);

            RegistrationFile::updateOrCreate(['tin_registration_id' => $registration->id, 'file_type' => 'identity'], [
                'file_path' => 'demo/individual/' . $registration->ref . '-id.pdf',
                'file_name' => 'Demo ID Document',
            ]);

            $this->seedEvents($registration, $registration->ref, $status, $assignee ?: $admin);

            return $registration;
        })->all();
    }

    private function seedBusinesses(User $dsOne, User $dsTwo, User $admin): array
    {
        $rows = [
            ['DEMO-BUS-0001', 'submitted', null, 'Maluti Demo Traders (Pty) Ltd', 'COMP', null, 6],
            ['DEMO-BUS-0002', 'under_review', $dsOne, 'Blue Crane Logistics', 'COMP', null, 4],
            ['DEMO-BUS-0003', 'approved', $dsTwo, 'Maseru Textile Works', 'COMP', 'BUS202605240001', 11],
            ['DEMO-BUS-0004', 'rejected', $dsOne, 'Highlands Food Market', 'SOLE', null, 7],
            ['DEMO-BUS-0005', 'submitted', null, 'Senqu Digital Services', 'PART', null, 1],
        ];

        return collect($rows)->map(function ($row, $index) use ($admin) {
            [$ref, $status, $assignee, $name, $businessType, $tin, $age] = $row;
            $createdAt = now()->subDays($age);

            $business = BusinessRegistration::updateOrCreate(
                ['reference_number' => $ref],
                [
                    'application_type' => 'New',
                    'registration_type' => 'NEW',
                    'document_locator' => 'DEMOBUS' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'receive_date' => $createdAt->toDateString(),
                    'old_tin' => null,
                    'new_tin' => $tin,
                    'legal_name' => $name,
                    'business_type' => $businessType,
                    'business_type_display' => match ($businessType) {
                        'SOLE' => 'Sole Trader',
                        'PART' => 'Partnership',
                        default => 'Company',
                    },
                    'registration_number' => 'REG-DEMO-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'is_sole_trader' => $businessType === 'SOLE',
                    'name_structure' => ['forename' => 'Demo', 'surname' => 'Owner ' . ($index + 1)],
                    'structured_postal_address' => ['country' => 'LS', 'city' => 'Maseru', 'district' => 'Maseru'],
                    'structured_physical_address' => ['street' => 'Industrial Road ' . ($index + 1), 'town' => 'Maseru'],
                    'phone_details' => [['phoneType' => 'CEL1', 'phoneCode' => '266', 'phoneNumber' => '57' . (300000 + $index), 'verified' => true]],
                    'email' => 'business.demo' . ($index + 1) . '@example.test',
                    'primary_phone' => '26657' . (300000 + $index),
                    'trade_details' => ['sector' => 'Retail', 'start_date' => $createdAt->copy()->subMonths(8)->toDateString()],
                    'principal_details' => ['principal' => 'Demo Principal ' . ($index + 1)],
                    'directors_partners' => [['name' => 'Demo Director ' . ($index + 1), 'id' => 'DEMODIR' . $index]],
                    'accountant_details' => ['name' => 'Demo Accountant'],
                    'nominated_officer_details' => ['name' => 'Demo Officer'],
                    'personal_identification' => ['id_type' => 'national_id'],
                    'file_attachments' => ['proof_of_trading' => 1],
                    'proof_of_trading_files_count' => 1,
                    'declaration_accepted' => true,
                    'declaration_name' => 'Demo Declarant',
                    'declaration_capacity' => 'Director',
                    'declaration_signature' => 'Demo Declarant',
                    'declaration_date' => $createdAt->toDateString(),
                    'status' => $status,
                    'review_notes' => $status === 'approved' ? 'Demo approval completed.' : null,
                    'reviewed_at' => in_array($status, ['approved', 'rejected'], true) ? $createdAt->copy()->addDay() : null,
                    'reviewed_by' => in_array($status, ['approved', 'rejected'], true) ? ($assignee?->id ?: $admin->id) : null,
                    'approved_at' => $status === 'approved' ? $createdAt->copy()->addDay() : null,
                    'approved_by' => $status === 'approved' ? ($assignee?->id ?: $admin->id) : null,
                    'rejected_at' => $status === 'rejected' ? $createdAt->copy()->addDay() : null,
                    'rejected_by' => $status === 'rejected' ? ($assignee?->id ?: $admin->id) : null,
                    'rejection_reason' => $status === 'rejected' ? 'Demo rejection: trading proof was missing.' : null,
                    'assigned_to' => $assignee?->id,
                    'assigned_at' => $assignee ? $createdAt->copy()->addHours(3) : null,
                    'created_at' => $createdAt,
                    'updated_at' => now()->subDays(max($age - 1, 0)),
                ]
            );

            $business->files()->updateOrCreate(['file_type' => 'proof_of_trading'], [
                'original_filename' => 'demo-proof-of-trading.pdf',
                'file_path' => 'demo/business/' . $business->reference_number . '-proof.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 320000,
                'disk' => 'public',
                'metadata' => ['demo' => true],
            ]);

            $this->seedEvents($business, $business->reference_number, strtoupper($status), $assignee ?: $admin);

            return $business;
        })->all();
    }

    private function seedBusinessAmendments(array $businesses, User $dsOne, User $admin): void
    {
        foreach (array_slice($businesses, 0, 3) as $index => $business) {
            $status = ['submitted', 'under_review', 'approved'][$index];
            $createdAt = now()->subDays(3 - $index);

            $amendment = BusinessAmendment::updateOrCreate(
                ['reference_number' => 'DEMO-AMEND-000' . ($index + 1)],
                [
                    'business_registration_id' => $business->id,
                    'tin' => $business->new_tin ?: 'BUS20240' . $index,
                    'amendment_tin' => $business->new_tin ?: 'BUS20240' . $index,
                    'document_locator' => 'DEMOAMEND' . ($index + 1),
                    'receive_date' => $createdAt->toDateString(),
                    'application_type' => 'Amendment',
                    'amendment_type' => 'UPDATE',
                    'amended_sections' => ['contact_info', 'trade_details'],
                    'amendment_data' => [
                        'contact_info' => ['email' => 'updated.demo' . ($index + 1) . '@example.test'],
                        'trade_details' => ['sector' => 'Updated Retail'],
                    ],
                    'status' => $status,
                    'review_notes' => $status === 'approved' ? 'Demo amendment approved.' : null,
                    'reviewed_at' => $status === 'approved' ? $createdAt->copy()->addDay() : null,
                    'reviewed_by' => $status === 'approved' ? $dsOne->id : null,
                    'approved_at' => $status === 'approved' ? $createdAt->copy()->addDay() : null,
                    'approved_by' => $status === 'approved' ? $dsOne->id : null,
                    'assigned_to' => $status === 'under_review' ? $dsOne->id : null,
                    'assigned_at' => $status === 'under_review' ? $createdAt->copy()->addHours(2) : null,
                    'created_at' => $createdAt,
                    'updated_at' => now()->subDays(max(2 - $index, 0)),
                ]
            );

            $this->seedEvents($amendment, $amendment->reference_number, strtoupper($status), $status === 'under_review' ? $dsOne : $admin);
        }
    }

    private function seedRiitReturns(): void
    {
        foreach (range(1, 6) as $index) {
            $createdAt = now()->subDays($index);
            $status = $index % 3 === 0 ? 'soap_failed' : 'submitted_to_database';
            $soapStatus = $index % 3 === 0 ? 'failed' : 'success';

            $return = RiitReturn::updateOrCreate(
                ['reference_number' => 'DEMO-RIIT-000' . $index],
                [
                    'person_id' => 'DEMO-PER-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'tin' => 'IND2024' . str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                    'return_type' => $index % 2 === 0 ? 'nil' : 'normal',
                    'is_amendment' => $index === 5,
                    'tax_year_end' => '2025',
                    'period_start_date' => '2025-04-01',
                    'period_end_date' => '2026-03-31',
                    'tax_type' => 'RIIT',
                    'document_locator' => 'DEMORIIT' . $index,
                    'receive_date' => $createdAt->toDateString(),
                    'form_data' => ['demo' => true, 'employment_income' => 240000 + ($index * 10000)],
                    'submission_payload' => ['source' => 'demo-seeder'],
                    'total_chargeable_income' => $index % 2 === 0 ? 0 : 240000 + ($index * 10000),
                    'tax_due' => $index % 2 === 0 ? 0 : 12000 + ($index * 600),
                    'tax_overpaid' => 0,
                    'claim_repayment' => false,
                    'declaration_accepted' => true,
                    'declarant_name' => 'Demo Taxpayer ' . $index,
                    'status' => $status,
                    'soap_status' => $soapStatus,
                    'soap_message' => $soapStatus === 'success' ? 'Demo SOAP accepted.' : 'Demo SOAP validation failed.',
                    'soap_http_status' => $soapStatus === 'success' ? 200 : 500,
                    'soap_submitted_at' => $createdAt->copy()->addMinutes(4),
                    'nil_reason' => $index % 2 === 0 ? 'No taxable income for the period.' : null,
                    'created_at' => $createdAt,
                    'updated_at' => now()->subDays(max($index - 1, 0)),
                ]
            );

            RiitReturnAttachment::updateOrCreate(['riit_return_id' => $return->id, 'attachment_type' => 'supporting_document'], [
                'original_filename' => 'demo-riit-support.pdf',
                'file_path' => 'demo/returns/' . $return->reference_number . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 180000,
                'disk' => 'public',
                'metadata' => ['demo' => true],
            ]);
        }
    }

    private function seedEvents($subject, string $label, string $status, User $user): void
    {
        RegistrationOperationEvent::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->where('metadata->demo', true)
            ->delete();

        RegistrationOperationEvent::create([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'subject_label' => $label,
            'event_type' => 'action',
            'channel' => 'workflow',
            'status' => 'success',
            'title' => 'Demo submission received',
            'message' => 'Demo record was submitted and is available in the queue.',
            'user_id' => null,
            'metadata' => ['demo' => true],
            'created_at' => $subject->created_at,
            'updated_at' => $subject->created_at,
        ]);

        if (in_array($status, ['UNDER_REVIEW', 'APPROVED', 'REJECTED'], true)) {
            RegistrationOperationEvent::create([
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'subject_label' => $label,
                'event_type' => 'assignment',
                'channel' => 'workflow',
                'status' => 'success',
                'title' => 'Demo assigned to officer',
                'message' => 'Assigned for review.',
                'user_id' => $user->id,
                'metadata' => ['demo' => true],
                'created_at' => $subject->created_at->copy()->addHours(2),
                'updated_at' => $subject->created_at->copy()->addHours(2),
            ]);
        }

        if (str_contains($label, '0002')) {
            RegistrationOperationEvent::create([
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'subject_label' => $label,
                'event_type' => 'soap',
                'channel' => 'soap',
                'status' => 'failed',
                'title' => 'Demo SOAP submission failed',
                'message' => 'Demo backend timeout while validating taxpayer data.',
                'user_id' => $user->id,
                'metadata' => ['demo' => true, 'message' => 'Timeout connecting to SOAP backend'],
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ]);
        }

        if (in_array($status, ['APPROVED', 'REJECTED'], true)) {
            RegistrationOperationEvent::create([
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'subject_label' => $label,
                'event_type' => 'action',
                'channel' => 'workflow',
                'status' => strtolower($status) === 'approved' ? 'success' : 'failed',
                'title' => strtolower($status) === 'approved' ? 'Demo approved' : 'Demo rejected',
                'message' => strtolower($status) === 'approved' ? 'Demo record approved successfully.' : 'Demo record rejected with sample reason.',
                'user_id' => $user->id,
                'metadata' => ['demo' => true],
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ]);
        }
    }
}
