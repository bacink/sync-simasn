<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_status_kgb', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique(); // draft, diajukan, diverifikasi, disetujui, ditolak
            $table->string('nama'); // Display name
            $table->text('deskripsi')->nullable();
            $table->tinyInteger('urutan')->default(0); // For ordering workflow
            $table->boolean('is_final')->default(false); // Terminal state (approved/rejected)
            $table->boolean('allow_edit')->default(true); // Can still edit?
            $table->timestamps();
        });

        // Insert default statuses
        \DB::table('ref_status_kgb')->insert([
            ['kode' => 'draft', 'nama' => 'Draft', 'deskripsi' => 'Belum diajukan', 'urutan' => 1, 'is_final' => false, 'allow_edit' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'diajukan', 'nama' => 'Diajukan', 'deskripsi' => 'Sudah diajukan ke verifikator', 'urutan' => 2, 'is_final' => false, 'allow_edit' => false, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'diverifikasi', 'nama' => 'Diverifikasi', 'deskripsi' => 'Telah diverifikasi oleh verifikator', 'urutan' => 3, 'is_final' => false, 'allow_edit' => false, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'disetujui', 'nama' => 'Disetujui', 'deskripsi' => 'Final disetujui admin', 'urutan' => 4, 'is_final' => true, 'allow_edit' => false, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'ditolak', 'nama' => 'Ditolak', 'deskripsi' => 'Ditolak pada tahap verifikasi atau persetujuan', 'urutan' => 5, 'is_final' => true, 'allow_edit' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_status_kgb');
    }
};