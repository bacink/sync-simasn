<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kgb_calculations', function (Blueprint $table) {
            // Keep formula string for human-readable reference
            // Add structured JSON for programmatic use
            $table->json('formula_json')->nullable()->after('formula');

            // Index on gaji_ref_id (already FK)
            $table->index('gaji_ref_id');
        });
    }

    public function down(): void
    {
        Schema::table('kgb_calculations', function (Blueprint $table) {
            $table->dropColumn('formula_json');
        });
    }
};