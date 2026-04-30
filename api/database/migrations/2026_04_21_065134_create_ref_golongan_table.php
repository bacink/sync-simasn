<?php

use App\Enums\JenisAsn;
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
        Schema::create('ref_golongan', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_asn', array_column(JenisAsn::cases(), 'value'))
                ->default(JenisAsn::PNS->value);
            $table->string('golongan', 10); // e.g., 'IV', 'III', 'XI'
            $table->string('sub_golongan', 5)->nullable(); // e.g., 'a', 'b', 'c'
            $table->string('pangkat', 100)->nullable(); // e.g., 'Pembina', 'Penata'
            $table->integer('urutan')->default(0); // for sorting
            $table->timestamps();

            $table->unique(['jenis_asn', 'golongan', 'sub_golongan'], 'ref_golongan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_golongan');
    }
};
