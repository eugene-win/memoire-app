<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->text('analysis_exclusions')->nullable();
            $table->string('analysis_report_path')->nullable();
            $table->string('analysis_report_filename')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->dropColumn([
                'analysis_exclusions',
                'analysis_report_path',
                'analysis_report_filename',
            ]);
        });
    }
};
