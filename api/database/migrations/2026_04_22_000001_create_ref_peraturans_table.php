<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_peraturan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 50);
            $table->string('nomor', 50);
            $table->year('tahun');
            $table->string('nama');
            $table->timestamps();

            $table->index(['jenis', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_peraturan');
    }
};
