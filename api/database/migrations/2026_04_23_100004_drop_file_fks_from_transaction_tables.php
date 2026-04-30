<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old single-file FK from riwayat_kgb
        Schema::table('riwayat_kgb', function (Blueprint $table) {
            $table->dropForeign(['file_sk_id']);
            $table->dropColumn('file_sk_id');
        });

        // Drop the old single-file FK from riwayat_pmk
        Schema::table('riwayat_pmk', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropColumn('file_id');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_kgb', function (Blueprint $table) {
            $table->foreignId('file_sk_id')->nullable()->constrained('files')->nullOnDelete();
        });

        Schema::table('riwayat_pmk', function (Blueprint $table) {
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
        });
    }
};
