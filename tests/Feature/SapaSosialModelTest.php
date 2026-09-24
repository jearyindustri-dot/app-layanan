<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\ComplaintAttachmentType;
use App\Enums\ComplaintStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\HandlingType;
use App\Enums\InformationCategory;
use App\Enums\MinistryDecision;
use App\Enums\PbiReason;
use App\Enums\PublishStatus;
use App\Enums\ReferralStatus;
use App\Enums\RehabilitationCaseStatus;
use App\Enums\ServiceHandler;
use App\Enums\ServiceRequestStatus;
use App\Models\Approval;
use App\Models\Assessment;
use App\Models\Client;
use App\Models\ClientCategory;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintCategory;
use App\Models\Disposition;
use App\Models\District;
use App\Models\DownloadableForm;
use App\Models\DtsenCertificate;
use App\Models\DtsenPurpose;
use App\Models\Faq;
use App\Models\InformationPage;
use App\Models\MonitoringRecord;
use App\Models\NumberSequence;
use App\Models\PageVisit;
use App\Models\PbiReactivation;
use App\Models\Referral;
use App\Models\ReferralInstitution;
use App\Models\RehabilitationCase;
use App\Models\SearchLog;
use App\Models\ServiceRequirement;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Models\ServiceType;
use App\Models\StatusHistory;
use App\Models\User;
use App\Models\Village;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SapaSosialModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_sapa_sosial_models_and_relationships(): void
    {
        // 1. Master & Users
        $workUnit = WorkUnit::create(['name' => 'Bidang Perlindungan dan Jaminan Sosial']);
        $district = District::create(['code' => '35.05.01', 'name' => 'Kecamatan Wlingi']);
        $village = Village::create(['district_id' => $district->id, 'code' => '35.05.01.1001', 'name' => 'Babadan']);

        $user = User::create([
            'name' => 'Petugas Dinsos',
            'email' => 'petugas@dinsos.blitarkab.go.id',
            'password' => 'secret123',
            'phone' => '081234567890',
            'nik' => '3505011111110001',
            'work_unit_id' => $workUnit->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'is_active' => true,
        ]);

        $this->assertEquals($workUnit->id, $user->workUnit->id);
        $this->assertEquals($district->id, $user->district->id);
        $this->assertEquals($village->id, $user->village->id);

        // 2. Layanan 1 (DTSEN)
        $serviceTypeDtsen = ServiceType::create([
            'code' => 'DTSEN',
            'name' => 'Surat Keterangan DTSEN',
            'handler' => ServiceHandler::DTSEN,
            'sla_days' => 3,
        ]);
        $reqKtp = ServiceRequirement::create([
            'service_type_id' => $serviceTypeDtsen->id,
            'name' => 'KTP Pemohon',
            'is_mandatory' => true,
        ]);

        $reqNumberDtsen = NumberSequence::nextFormattedNumber('DTSEN');
        $serviceRequest = ServiceRequest::create([
            'request_number' => $reqNumberDtsen,
            'service_type_id' => $serviceTypeDtsen->id,
            'submitter_id' => $user->id,
            'applicant_name' => 'Supriyadi',
            'applicant_nik' => '3505012222220001',
            'family_card_number' => '3505012222220002',
            'address' => 'Jl. Merdeka No. 10',
            'village_id' => $village->id,
            'phone' => '081234567899',
            'submitted_at' => now(),
            'status' => ServiceRequestStatus::SUBMITTED,
        ]);

        $doc = ServiceRequestDocument::create([
            'service_request_id' => $serviceRequest->id,
            'service_requirement_id' => $reqKtp->id,
            'file_path' => 'documents/ktp_supriyadi.pdf',
            'original_name' => 'ktp_supriyadi.pdf',
            'verification_status' => DocumentVerificationStatus::PENDING,
        ]);

        $dtsenPurpose = DtsenPurpose::create([
            'code' => 'spmb',
            'name' => 'SPMB Jalur Afirmasi',
            'max_decile' => 5,
            'validity_days' => 90,
        ]);

        $dtsenCert = DtsenCertificate::create([
            'service_request_id' => $serviceRequest->id,
            'dtsen_purpose_id' => $dtsenPurpose->id,
            'subject_name' => 'Ahmad Santoso',
            'subject_nik' => '3505013333330001',
            'relationship_to_applicant' => 'Anak Kandung',
            'is_registered' => true,
            'decile' => 2,
            'verification_code' => 'DTSEN-2026-VERIF-001',
        ]);

        $approval = Approval::create([
            'approvable_type' => DtsenCertificate::class,
            'approvable_id' => $dtsenCert->id,
            'step' => 1,
            'approver_id' => $user->id,
            'decision' => ApprovalDecision::APPROVED,
        ]);

        $this->assertEquals($serviceRequest->id, $dtsenCert->serviceRequest->id);
        $this->assertCount(1, $dtsenCert->approvals);
        $this->assertEquals(ServiceRequestStatus::SUBMITTED, $serviceRequest->status);
        $this->assertEquals('Diajukan', $serviceRequest->status->label());

        // 3. Layanan 2 (PBI)
        $serviceTypePbi = ServiceType::create([
            'code' => 'PBI',
            'name' => 'Reaktivasi KIS/PBI-JK',
            'handler' => ServiceHandler::PBI,
        ]);
        $serviceRequestPbi = ServiceRequest::create([
            'request_number' => NumberSequence::nextFormattedNumber('PBI'),
            'service_type_id' => $serviceTypePbi->id,
            'applicant_name' => 'Siti',
            'applicant_nik' => '3505014444440001',
            'family_card_number' => '3505014444440002',
            'address' => 'Dusun Krajan',
            'village_id' => $village->id,
            'phone' => '081234567888',
            'submitted_at' => now(),
            'status' => ServiceRequestStatus::SUBMITTED,
        ]);

        $pbiReactivation = PbiReactivation::create([
            'service_request_id' => $serviceRequestPbi->id,
            'participant_name' => 'Siti',
            'participant_nik' => '3505014444440001',
            'bpjs_card_number' => '0001234567890',
            'reason' => PbiReason::CHRONIC,
            'health_facility_name' => 'RSUD Ngudi Waluyo',
            'health_letter_number' => '440/123/RSUD/2026',
            'ministry_decision' => MinistryDecision::PENDING,
        ]);

        $this->assertEquals(PbiReason::CHRONIC, $pbiReactivation->reason);

        // 4. Layanan 3 (Rehabilitasi Sosial)
        $clientCat = ClientCategory::create(['name' => 'Lanjut Usia Terlantar']);
        $client = Client::create([
            'name' => 'Mbah Rejo',
            'client_category_id' => $clientCat->id,
            'gender' => 'L',
            'address' => 'Desa Babadan',
            'village_id' => $village->id,
        ]);

        $case = RehabilitationCase::create([
            'case_number' => NumberSequence::nextFormattedNumber('RHS'),
            'client_id' => $client->id,
            'officer_id' => $user->id,
            'handling_type' => HandlingType::BOTH,
            'status' => RehabilitationCaseStatus::RECEIVED,
            'received_at' => now(),
        ]);

        $assessment = Assessment::create([
            'rehabilitation_case_id' => $case->id,
            'officer_id' => $user->id,
            'assessment_date' => now()->toDateString(),
            'result' => 'Lansia terlantar tanpa keluarga',
            'service_needs' => 'Perlindungan & permakanan',
            'recommendation' => 'Rujuk ke Panti Werdha',
            'needs_referral' => true,
        ]);

        $institution = ReferralInstitution::create([
            'name' => 'UPTD PSTW Blitar',
            'type' => 'panti',
        ]);

        $referral = Referral::create([
            'referral_number' => NumberSequence::nextFormattedNumber('RJK'),
            'rehabilitation_case_id' => $case->id,
            'assessment_id' => $assessment->id,
            'referral_institution_id' => $institution->id,
            'officer_id' => $user->id,
            'referral_date' => now()->toDateString(),
            'status' => ReferralStatus::DRAFT,
        ]);

        $monitoring = MonitoringRecord::create([
            'rehabilitation_case_id' => $case->id,
            'referral_id' => $referral->id,
            'officer_id' => $user->id,
            'monitoring_date' => now()->toDateString(),
            'progress' => 'Klien telah beradaptasi di panti',
        ]);

        $this->assertCount(1, $case->assessments);
        $this->assertCount(1, $case->referrals);
        $this->assertCount(1, $case->monitoringRecords);

        // 5. Layanan 5 (Pengaduan)
        $complaintCat = ComplaintCategory::create(['name' => 'Orang Terlantar']);
        $complaint = Complaint::create([
            'complaint_number' => NumberSequence::nextFormattedNumber('ADU'),
            'complaint_category_id' => $complaintCat->id,
            'reporter_name' => 'Warga Peduli',
            'reporter_phone' => '081234567777',
            'village_id' => $village->id,
            'description' => 'Ada lansia hidup sebatang kara',
            'reported_at' => now(),
            'status' => ComplaintStatus::RECEIVED,
        ]);

        $attachment = ComplaintAttachment::create([
            'complaint_id' => $complaint->id,
            'file_path' => 'complaints/foto1.jpg',
            'type' => ComplaintAttachmentType::PHOTO,
        ]);

        $this->assertCount(1, $complaint->attachments);

        // 6. Layanan 6 (Informasi)
        $infoPage = InformationPage::create([
            'title' => 'Panduan SK DTSEN',
            'slug' => 'panduan-sk-dtsen',
            'category' => InformationCategory::PROGRAM,
            'service_type_id' => $serviceTypeDtsen->id,
            'publish_status' => PublishStatus::PUBLISHED,
            'published_at' => now(),
            'manager_id' => $user->id,
        ]);

        $form = DownloadableForm::create([
            'information_page_id' => $infoPage->id,
            'name' => 'Formulir Permohonan DTSEN',
            'file_path' => 'forms/form_dtsen.pdf',
        ]);

        $faq = Faq::create([
            'information_page_id' => $infoPage->id,
            'question' => 'Berapa lama prosesnya?',
            'answer' => 'Maksimal 3 hari kerja',
        ]);

        $visit = PageVisit::create([
            'information_page_id' => $infoPage->id,
            'visit_date' => now()->toDateString(),
            'visit_count' => 10,
        ]);

        $searchLog = SearchLog::create([
            'keyword' => 'DTSEN',
            'result_count' => 1,
            'searched_at' => now(),
        ]);

        $this->assertCount(1, $infoPage->downloadableForms);
        $this->assertCount(1, $infoPage->faqs);
        $this->assertCount(1, $infoPage->pageVisits);

        // 7. Polimorfik: StatusHistory & Disposition
        $statusHistory = StatusHistory::create([
            'statusable_type' => ServiceRequest::class,
            'statusable_id' => $serviceRequest->id,
            'from_status' => null,
            'to_status' => ServiceRequestStatus::SUBMITTED->value,
            'notes' => 'Pengajuan diterima via portal',
            'user_id' => $user->id,
        ]);

        $disposition = Disposition::create([
            'dispositionable_type' => ServiceRequest::class,
            'dispositionable_id' => $serviceRequest->id,
            'from_user_id' => $user->id,
            'to_work_unit_id' => $workUnit->id,
            'instructions' => 'Mohon diverifikasi berkasnya',
            'disposed_at' => now(),
        ]);

        $this->assertCount(1, $serviceRequest->statusHistories);
        $this->assertCount(1, $serviceRequest->dispositions);
    }
}
