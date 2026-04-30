<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_kgb', function (Blueprint $table) {
            // SQLite: drop indexes on columns we're about to remove, BEFORE dropColumn
            $table->dropIndex(['nip']);

            // Add FK columns
            $table->foreignId('golongan_id')
                ->nullable()
                ->constrained('ref_golongan')
                ->nullOnDelete()
                ->after('pegawai_id');

            $table->foreignId('peraturan_id')
                ->nullable()
                ->constrained('ref_peraturan')
                ->nullOnDelete()
                ->after('golongan_id');

            // Drop denormalised string fields (snapshot is source of truth for employee data)
            $table->dropColumn(['nip', 'nama', 'golongan']);

            // Indexes on new FKs
            $table->index('golongan_id');
            $table->index('peraturan_id');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_kgb', function (Blueprint $table) {
            // Restore string columns first (before dropping FKs — FKs must not exist)
            $table->string('nip', 20)->nullable();
            $table->string('nama')->nullable();
            $table->string('golongan', 10)->nullable();

            $table->dropForeign(['golongan_id']);
            $table->dropForeign(['peraturan_id']);
            $table->dropColumn(['golongan_id', 'peraturan_id']);

            $table->index('nip');
        });
    }
};