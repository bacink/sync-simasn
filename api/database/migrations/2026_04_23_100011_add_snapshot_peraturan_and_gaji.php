<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kgb_snapshots', function (Blueprint $table) {
            $table->foreignId('peraturan_id')
                ->nullable()
                ->constrained('ref_peraturan')
                ->nullOnDelete()
                ->after('riwayat_kgb_id');

            $table->decimal('gaji_pokok', 15, 2)
                ->after('masa_kerja_bulan');

            $table->index('peraturan_id');
        });
    }

    public function down(): void
    {
        Schema::table('kgb_snapshots', function (Blueprint $table) {
            $table->dropForeign(['peraturan_id']);
            $table->dropColumn(['peraturan_id', 'gaji_pokok']);
        });
    }
};
