<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_registry', function (Blueprint $table): void {
            $table->string('email')->nullable()->unique()->after('matricule');
            $table->string('class_code', 16)->nullable()->after('study_level_id');
        });

        Schema::create('student_login_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_registry_id')
                ->constrained('student_registry')
                ->cascadeOnDelete();
            $table->char('challenge_token_hash', 64)->unique();
            $table->string('code_hash');
            $table->char('email_snapshot_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['student_registry_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_login_challenges');
        Schema::table('student_registry', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'class_code']);
        });
    }
};
