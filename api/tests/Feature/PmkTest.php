<?php

namespace Tests\Feature;

use App\Models\RiwayatPmk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_pmk_list_returns_empty_for_no_records(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/pmk');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_pmk_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/pmk');

        $response->assertStatus(401);
    }

    public function test_pmk_list_returns_all_records(): void
    {
        RiwayatPmk::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/pmk');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_pmk_list_filters_by_pegawai_id(): void
    {
        $targetPegawaiId = '11111111-1111-1111-1111-111111111111';
        RiwayatPmk::factory()->create(['pegawai_id' => $targetPegawaiId]);
        RiwayatPmk::factory()->create(['pegawai_id' => '22222222-2222-2222-2222-222222222222']);

        $response = $this->actingAs($this->user)->getJson("/api/pmk?pegawai_id={$targetPegawaiId}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_pmk_list_filters_by_nip(): void
    {
        $targetNip = '12345678901234567890';
        RiwayatPmk::factory()->create(['nip' => $targetNip]);
        RiwayatPmk::factory()->create(['nip' => '99999999999999999999']);

        $response = $this->actingAs($this->user)->getJson("/api/pmk?nip={$targetNip}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($targetNip, $response->json('data.0.nip'));
    }

    public function test_pmk_store_creates_new_record(): void
    {
        $payload = [
            'pegawai_id' => 'aaaa1111-1111-1111-1111-111111111111',
            'nip' => '98765432109876543210',
            'nama' => 'Dr. Jane Smith, M.Kom.',
            'masa_kerja_lama_tahun' => 5,
            'masa_kerja_lama_bulan' => 3,
            'masa_kerja_baru_tahun' => 8,
            'masa_kerja_baru_bulan' => 0,
            'nomor_sk' => 'SK/PMK/2024/001',
            'tanggal_sk' => '2024-06-15',
            'dasar_pmk' => 'Peraturan Bersama BKN dan KemenPAN-RB',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/pmk', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.nip', '98765432109876543210')
            ->assertJsonPath('data.nama', 'Dr. Jane Smith, M.Kom.')
            ->assertJsonPath('data.masa_kerja_baru.tahun', 8);

        $this->assertDatabaseHas('riwayat_pmk', [
            'nip' => '98765432109876543210',
            'masa_kerja_baru_tahun' => 8,
        ]);
    }

    public function test_pmk_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/pmk', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'pegawai_id',
                'nip',
                'nama',
                'masa_kerja_lama_tahun',
                'masa_kerja_baru_tahun',
                'nomor_sk',
                'tanggal_sk',
            ]);
    }

    public function test_pmk_store_validates_masa_kerja_bulan_range(): void
    {
        $payload = [
            'pegawai_id' => 'aaaa1111-1111-1111-1111-111111111111',
            'nip' => '12345678901234567890',
            'nama' => 'Test User',
            'masa_kerja_lama_tahun' => 5,
            'masa_kerja_lama_bulan' => 15,
            'masa_kerja_baru_tahun' => 7,
            'masa_kerja_baru_bulan' => 0,
            'nomor_sk' => 'SK/001',
            'tanggal_sk' => '2024-01-01',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/pmk', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['masa_kerja_lama_bulan']);
    }

    public function test_pmk_show_returns_record(): void
    {
        $pmk = RiwayatPmk::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/pmk/{$pmk->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pmk->id);
    }

    public function test_pmk_show_returns_404_for_nonexistent_record(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/pmk/99999');

        $response->assertStatus(404);
    }

    public function test_pmk_delete_removes_record(): void
    {
        $pmk = RiwayatPmk::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/pmk/{$pmk->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('riwayat_pmk', ['id' => $pmk->id]);
    }

    public function test_pmk_delete_returns_404_for_nonexistent_record(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/pmk/99999');

        $response->assertStatus(404);
    }

    public function test_pmk_delete_requires_authentication(): void
    {
        $pmk = RiwayatPmk::factory()->create();

        $response = $this->deleteJson("/api/pmk/{$pmk->id}");

        $response->assertStatus(401);
    }
}