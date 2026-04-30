<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Rename record_id + table_name to polymorphic columns
            $table->string('auditable_type')->nullable()->after('id');
            $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');

            // Keep table_name + record_id for backwards compat — mark nullable
            // (in production: migrate data first, then make nullable in a separate step)
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['auditable_type', 'auditable_id']);
            $table->dropColumn(['auditable_type', 'auditable_id']);
        });
    }
};