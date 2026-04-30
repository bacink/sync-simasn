<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fileables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('fileable_type');
            $table->unsignedBigInteger('fileable_id');
            $table->string('kategori', 50)->nullable(); // sk, lampiran, dokumen_pendukung, dll.
            $table->timestamps();

            $table->unique(['file_id', 'fileable_type', 'fileable_id', 'kategori'], 'fileables_unique');
            $table->index(['fileable_type', 'fileable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fileables');
    }
};
