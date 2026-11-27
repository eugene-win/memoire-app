<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('study_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('defense_years', function (Blueprint $table) {
            $table->id();
            $table->string('label', 20)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
            $table->index(['role_id', 'user_id']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('matricule')->nullable()->unique();
            $table->foreignId('filiere_id')->nullable()->constrained('filieres')->nullOnDelete();
            $table->foreignId('study_level_id')->nullable()->constrained('study_levels')->nullOnDelete();
            $table->foreignId('defense_year_id')->nullable()->constrained('defense_years')->nullOnDelete();
            $table->string('enrollment_status', 32)->default('unknown')->index();
            $table->timestamp('institutionally_verified_at')->nullable();
            $table->timestamp('defended_at')->nullable();
            $table->timestamps();

            $table->index(['study_level_id', 'enrollment_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('account_status', 32)->default('active')->index();
            $table->index(['role', 'deposit_authorized']);
        });

        $now = now();
        $roles = [
            ['slug' => 'external_reader', 'name' => 'Lecteur externe'],
            ['slug' => 'student', 'name' => 'Étudiant ENEAM'],
            ['slug' => 'validator', 'name' => 'Responsable de filière'],
            ['slug' => 'library_manager', 'name' => 'Responsable de bibliothèque'],
            ['slug' => 'administrator', 'name' => 'Administrateur'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'slug' => $role['slug'],
                'name' => $role['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug');
        foreach (DB::table('users')->whereNotNull('role')->get(['id', 'role']) as $user) {
            if (isset($roleIds[$user->role])) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleIds[$user->role],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('users')->where('role', 'student')->get(['id', 'matricule']) as $user) {
            DB::table('students')->insert([
                'user_id' => $user->id,
                'matricule' => $user->matricule,
                'enrollment_status' => 'unknown',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('students');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('defense_years');
        Schema::dropIfExists('study_levels');
        Schema::dropIfExists('filieres');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['role', 'deposit_authorized'])) {
                $table->dropIndex(['role', 'deposit_authorized']);
            }
            $table->dropIndex(['account_status']);
            $table->dropColumn(['first_name', 'last_name', 'account_status']);
        });
    }
};
