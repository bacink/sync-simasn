<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_asn_download_histories', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('sim_asn_download_histories', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
