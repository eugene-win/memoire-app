<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theses', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('theses')->whereNull('student_id')->exists()) {
            throw new RuntimeException('Archive records without a student account must be resolved before rolling back this migration.');
        }

        Schema::table('theses', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable(false)->change();
        });
    }
};
