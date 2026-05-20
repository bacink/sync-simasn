<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_asn_download_histories', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 50)->index();
            $table->string('nama', 255)->nullable();
            $table->string('jenis_dokumen', 100)->index(); // e.g. ijazah, sk_golongan
            $table->string('file_name', 255);
            $table->string('file_path', 500)->nullable();  // relative path on disk: arsip/{jenis}/{nip}/{file}
            $table->string('status', 20)->default('pending')->index(); // pending|success|failed
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->timestamps();

            // Prevent duplicate downloads of the same document per employee
            $table->unique(['nip', 'jenis_dokumen', 'file_name'], 'sim_asn_download_histories_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_asn_download_histories');
    }
};
