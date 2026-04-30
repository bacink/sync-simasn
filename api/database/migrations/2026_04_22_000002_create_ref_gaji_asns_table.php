<?php

use App\Enums\JenisAsn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_gaji_asn', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_asn', array_column(JenisAsn::cases(), 'value'))
                ->default(JenisAsn::PNS->value);
            $table->foreignId('peraturan_id')->constrained('ref_peraturan')->onDelete('cascade');
            $table->string('golongan', 10);
            $table->string('sub_golongan', 5)->nullable();
            $table->tinyInteger('masa_kerja');
            $table->decimal('gaji', 15, 2);
            $table->timestamps();

            $table->unique(['jenis_asn', 'golongan', 'sub_golongan', 'masa_kerja', 'peraturan_id'], 'ref_gaji_unique');
            $table->index(['golongan', 'masa_kerja']);
            $table->index(['jenis_asn', 'golongan', 'sub_golongan', 'masa_kerja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_gaji_asn');
    }
};
