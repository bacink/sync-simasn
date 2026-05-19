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
        Schema::create('kgb_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kgb_id')->constrained('riwayat_kgb')->cascadeOnDelete();
            $table->json('data_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kgb_snapshots');
    }
};