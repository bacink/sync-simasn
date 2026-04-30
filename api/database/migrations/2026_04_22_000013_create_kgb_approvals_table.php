<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kgb_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kgb_id')->constrained('riwayat_kgb')->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->string('role', 50);
            $table->string('status', 20);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('riwayat_kgb_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kgb_approvals');
    }
};
