<?php

namespace Tests\Feature;

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use App\Models\KgbApproval;
use App\Models\KgbSnapshot;
use App\Models\RefGajiAsn;
use App\Models\RefPeraturan;
use App\Models\RiwayatKgb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KgbWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected RefPeraturan $peraturan;
    protected RefGajiAsn $gajiPnsIIIaMk0;
    protected RefGajiAsn $gajiPnsIIIaMk2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->peraturan = RefPeraturan::factory()->create();

        // PNS III/a with masa kerja 0 years
        $this->gajiPnsIIIaMk0 = RefGajiAsn::factory()->create([
            'jenis_asn' => 'pns',
            'peraturan_id' => $this->peraturan->id,
            'golongan' => 'III',
            'sub_golongan' => 'a',
            'masa_kerja' => 0,
            'gaji' => 3500000,
        ]);

        // PNS III/a with masa kerja 2 years
        $this->gajiPnsIIIaMk2 = RefGajiAsn::factory()->create([
            'jenis_asn' => 'pns',
            'peraturan_id' => $this->peraturan->id,
            'golongan' => 'III',
            'sub_golongan' => 'a',
            'masa_kerja' => 2,
            'gaji' => 3800000,
        ]);
    }

    public function test_kgb_list_returns_empty_for_no_records(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/kgb');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_kgb_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/kgb');

        $response->assertStatus(401);
    }

    public function test_kgb_show_returns_record_with_relations(): void
    {
        $kgb = RiwayatKgb::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/kgb/{$kgb->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'pegawai_id',
                    'nip',
                    'nama',
                    'status',
                ],
            ]);
    }

    public function test_kgb_show_returns_404_for_nonexistent_record(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/kgb/99999');

        $response->assertStatus(404);
    }

    public function test_kgb_submit_transitions_from_draft_to_diajukan(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/submit", [
            'nomor_sk' => '123/SK/KGB/2026',
            'tanggal_sk' => '2026-04-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'diajukan');

        $this->assertDatabaseHas('riwayat_kgb', [
            'id' => $kgb->id,
            'status' => 'diajukan',
            'nomor_sk' => '123/SK/KGB/2026',
        ]);
    }

    public function test_kgb_submit_rejects_non_draft_status(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Disetujui,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/submit", [
            'nomor_sk' => '123/SK/KGB/2026',
            'tanggal_sk' => '2026-04-01',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'KGB hanya bisa diajukan dari status draft');
    }

    public function test_kgb_verify_transitions_from_diajukan_to_diverifikasi(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Diajukan,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/verify", [
            'catatan' => 'Data sudah lengkap dan valid',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'diverifikasi');

        $this->assertDatabaseHas('riwayat_kgb', [
            'id' => $kgb->id,
            'status' => 'diverifikasi',
        ]);
    }

    public function test_kgb_verify_rejects_invalid_transition(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/verify", [
            'catatan' => 'Verifikasi',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'KGB hanya bisa diverifikasi dari status diajukan');
    }

    public function test_kgb_approve_transitions_from_diverifikasi_to_disetujui(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Diverifikasi,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertDatabaseHas('riwayat_kgb', [
            'id' => $kgb->id,
            'status' => 'disetujui',
        ]);
    }

    public function test_kgb_approve_rejects_invalid_transition(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/approve");

        $response->assertStatus(500)
            ->assertJsonPath('message', 'KGB hanya bisa disetujui dari status diverifikasi');
    }

    public function test_kgb_reject_transitions_from_diajukan_to_ditolak(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Diajukan,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/reject", [
            'catatan' => 'Data tidak sesuai',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ditolak');

        $this->assertDatabaseHas('riwayat_kgb', [
            'id' => $kgb->id,
            'status' => 'ditolak',
        ]);
    }

    public function test_kgb_reject_transitions_from_diverifikasi_to_ditolak(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Diverifikasi,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/reject", [
            'catatan' => 'Revisi diperlukan',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ditolak');
    }

    public function test_kgb_reject_fails_from_draft_status(): void
    {
        $kgb = RiwayatKgb::factory()->create([
            'status' => KgbStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/reject", [
            'catatan' => 'Tolak',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'KGB tidak bisa ditolak dari status saat ini');
    }

    public function test_kgb_workflow_full_happy_path(): void
    {
        // Draft -> Diajukan
        $kgb = RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);

        $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/submit", [
            'nomor_sk' => '001/SK/KGB/2026',
            'tanggal_sk' => '2026-04-01',
        ])->assertStatus(200);

        $kgb->refresh();
        $this->assertEquals(KgbStatus::Diajukan, $kgb->status);

        // Diajukan -> Diverifikasi
        $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/verify", [
            'catatan' => 'OK',
        ])->assertStatus(200);

        $kgb->refresh();
        $this->assertEquals(KgbStatus::Diverifikasi, $kgb->status);

        // Diverifikasi -> Disetujui
        $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/approve")
            ->assertStatus(200);

        $kgb->refresh();
        $this->assertEquals(KgbStatus::Disetujui, $kgb->status);

        // Approval records created
        $this->assertDatabaseCount('kgb_approvals', 3);
    }

    public function test_kgb_submit_creates_approval_record(): void
    {
        $kgb = RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);

        $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/submit", [
            'nomor_sk' => '001/SK/KGB/2026',
            'tanggal_sk' => '2026-04-01',
        ]);

        $this->assertDatabaseHas('kgb_approvals', [
            'riwayat_kgb_id' => $kgb->id,
            'user_id' => $this->user->id,
            'role' => 'operator',
        ]);
    }

    public function test_kgb_workflow_audit_log_created_on_each_transition(): void
    {
        $kgb = RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);

        $this->actingAs($this->user)->postJson("/api/kgb/{$kgb->id}/submit", [
            'nomor_sk' => '001/SK/KGB/2026',
            'tanggal_sk' => '2026-04-01',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'table_name' => 'riwayat_kgb',
            'record_id' => $kgb->id,
            'action' => 'submit',
        ]);
    }

    public function test_kgb_list_filters_by_status(): void
    {
        RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);
        RiwayatKgb::factory()->create(['status' => KgbStatus::Disetujui]);
        RiwayatKgb::factory()->create(['status' => KgbStatus::Disetujui]);

        $response = $this->actingAs($this->user)->getJson('/api/kgb?status=disetujui');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_kgb_list_filters_by_nip(): void
    {
        $targetNip = '12345678901234567890';
        $kgb1 = RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);
        $kgb2 = RiwayatKgb::factory()->create(['status' => KgbStatus::Draft]);

        KgbSnapshot::factory()->create(['riwayat_kgb_id' => $kgb1, 'gaji_pokok' => 5000000, 'data_json' => ['nip' => $targetNip]]);
        KgbSnapshot::factory()->create(['riwayat_kgb_id' => $kgb2, 'gaji_pokok' => 6000000, 'data_json' => ['nip' => '99999999999999999999']]);

        $response = $this->actingAs($this->user)->getJson("/api/kgb?nip={$targetNip}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}