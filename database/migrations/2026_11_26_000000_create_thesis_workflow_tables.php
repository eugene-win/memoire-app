<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('author_name');
            $table->string('matricule');
            $table->string('department');
            $table->unsignedSmallInteger('defended_year');
            $table->string('supervisor')->nullable();
            $table->string('status')->default('analysis_pending')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('thesis_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64)->unique();
            $table->string('analysis_status')->default('pending');
            $table->string('analysis_provider')->nullable();
            $table->string('analysis_reference')->nullable();
            $table->decimal('similarity_percent', 5, 2)->nullable();
            $table->text('analysis_note')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamp('physical_received_at')->nullable();
            $table->foreignId('physical_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['thesis_id', 'version_number']);
        });

        Schema::create('thesis_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_version_id')->constrained('thesis_versions')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['thesis_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_decisions');
        Schema::dropIfExists('thesis_versions');
        Schema::dropIfExists('theses');
    }
};
