<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });

        Schema::create('student_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth', 128)->nullable();
            $table->foreignId('filiere_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_level_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('registration_year');
            $table->string('academic_status', 32)->default('enrolled')->index();
            $table->foreignId('defense_year_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('defended_at')->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['study_level_id', 'academic_status']);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Cannot make users.email required while accounts without email exist.');
        }

        Schema::dropIfExists('student_registry');
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }
};
