<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_archive_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('responsible_id')->constrained('users')->restrictOnDelete();
            $table->string('source');
            $table->boolean('physical_checked');
            $table->boolean('diffusion_rights_confirmed');
            $table->text('control_result');
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->index(['responsible_id', 'imported_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('thesis_archive_imports')->exists() || DB::table('theses')->whereNull('student_id')->exists()) {
            throw new RuntimeException('Archive data must be migrated or retained before rolling back archive support.');
        }

        Schema::dropIfExists('thesis_archive_imports');
    }
};
