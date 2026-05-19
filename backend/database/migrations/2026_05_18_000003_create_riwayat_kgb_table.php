<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_kgb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('pegawai_id');
            $table->string('pegawai_nama');
            $table->string('pegawai_nip', 18);
            $table->foreignId('opd_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'diajukan', 'diverifikasi', 'disetujui', 'ditolak'])->default('draft');
            $table->string('golongan', 10);
            $table->unsignedTinyInteger('masa_kerja_tahun');
            $table->unsignedTinyInteger('masa_kerja_bulan');
            $table->decimal('gaji_lama', 15, 0)->default(0);
            $table->decimal('gaji_baru', 15, 0)->default(0);
            $table->date('tmt_kgb_lama')->nullable();
            $table->date('tmt_kgb_baru');
            $table->foreignId('pmk_id')->nullable()->constrained('riwayat_pmk')->nullOnDelete();
            $table->foreignId('ref_gaji_id')->nullable()->constrained('ref_gaji_asn')->nullOnDelete();
            $table->unsignedBigInteger('file_sk_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'opd_id']);
            $table->index(['pegawai_id', 'tmt_kgb_baru']);
            $table->index('pegawai_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_kgb');
    }
};