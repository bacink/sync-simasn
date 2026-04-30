<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pmk', function (Blueprint $table) {
            $table->id();
            $table->string('pegawai_id', 50);
            $table->string('nip', 20);
            $table->string('nama');
            $table->tinyInteger('masa_kerja_lama_tahun');
            $table->tinyInteger('masa_kerja_lama_bulan');
            $table->tinyInteger('masa_kerja_baru_tahun');
            $table->tinyInteger('masa_kerja_baru_bulan');
            $table->text('dasar_pmk')->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pegawai_id');
            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pmk');
    }
};
