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
        Schema::create('riwayat_pmk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('pegawai_id');
            $table->string('pegawai_nama');
            $table->string('pegawai_nip', 18);
            $table->foreignId('opd_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'aktif', 'nonaktif'])->default('draft');
            $table->string('no_sk', 100);
            $table->date('tanggal_sk');
            $table->unsignedTinyInteger('masa_kerja_lama_tahun');
            $table->unsignedTinyInteger('masa_kerja_lama_bulan');
            $table->unsignedTinyInteger('masa_kerja_baru_tahun');
            $table->unsignedTinyInteger('masa_kerja_baru_bulan');
            $table->unsignedBigInteger('file_sk_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pegawai_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pmk');
    }
};