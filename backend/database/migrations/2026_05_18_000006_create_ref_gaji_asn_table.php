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
        Schema::create('ref_gaji_asn', function (Blueprint $table) {
            $table->id();
            $table->string('golongan', 10);
            $table->unsignedTinyInteger('masa_kerja_tahun');
            $table->decimal('gaji', 15, 0);
            $table->string('peraturan', 255)->default('PP No. 5 Tahun 2024');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['golongan', 'masa_kerja_tahun']);
            $table->index(['golongan', 'masa_kerja_tahun', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_gaji_asn');
    }
};