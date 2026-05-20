<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create opds table
        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->nullable();
            $table->timestamps();
        });

        // 2. Add opd_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
        Schema::dropIfExists('opds');
    }
};