<?php

use App\Enums\KgbStatus;
use App\Enums\KgbType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kgb', function (Blueprint $table) {
            $table->id();
            $table->string('pegawai_id', 50);
            $table->string('nip', 20);
            $table->string('nama');
            $table->string('golongan', 10);
            $table->tinyInteger('masa_kerja_tahun');
            $table->tinyInteger('masa_kerja_bulan');
            $table->decimal('gaji_lama', 15, 2);
            $table->decimal('gaji_baru', 15, 2);
            $table->date('tmt_kgb');
            $table->string('nomor_sk', 100)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->enum('jenis_kgb', array_column(KgbType::cases(), 'value'))->default(KgbType::Reguler->value);
            $table->enum('status', array_column(KgbStatus::cases(), 'value'))->default(KgbStatus::Draft->value);
            $table->foreignId('file_sk_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedBigInteger('pmk_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pegawai_id');
            $table->index('nip');
            $table->index('tmt_kgb');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kgb');
    }
};
