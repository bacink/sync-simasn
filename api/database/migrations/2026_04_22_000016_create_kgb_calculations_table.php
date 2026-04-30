<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kgb_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kgb_id')->constrained('riwayat_kgb')->onDelete('cascade');
            $table->string('golongan', 10);
            $table->tinyInteger('masa_kerja');
            $table->foreignId('gaji_ref_id')->constrained('ref_gaji_asn');
            $table->decimal('gaji_hasil', 15, 2);
            $table->string('formula', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('riwayat_kgb_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kgb_calculations');
    }
};
