<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kgb_approvals', function (Blueprint $table) {
            $table->tinyInteger('step_order')->default(1)->after('role');
            $table->boolean('is_required')->default(true)->after('step_order');

            // Drop the fragile status string — derive from ref_status_kgb on riwayat_kgb
            $table->dropColumn('status');

            // Unique constraint: one approval per KGB per role per step
            $table->unique(['riwayat_kgb_id', 'user_id', 'step_order'], 'kgb_approvals_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kgb_approvals', function (Blueprint $table) {
            $table->dropUnique(['riwayat_kgb_id', 'user_id', 'step_order']);
            $table->dropColumn(['step_order', 'is_required']);
            $table->string('status', 20)->nullable();
        });
    }
};
