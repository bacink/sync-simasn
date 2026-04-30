<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kgb_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kgb_id')->constrained('riwayat_kgb')->onDelete('cascade');
            $table->string('jabatan_nama')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('nama_skpd')->nullable();
            $table->string('golongan', 10);
            $table->tinyInteger('masa_kerja_tahun');
            $table->tinyInteger('masa_kerja_bulan');
            $table->json('data_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('riwayat_kgb_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kgb_snapshots');
    }
};
