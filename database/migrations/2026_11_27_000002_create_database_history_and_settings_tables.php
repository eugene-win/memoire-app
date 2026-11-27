<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('authorized_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('inactive')->index();
            $table->text('reason')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('similarity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_version_id')->constrained('thesis_versions')->restrictOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->decimal('similarity_percent', 5, 2)->nullable();
            $table->text('analysis_result')->nullable();
            $table->string('private_report_path')->nullable();
            $table->string('original_report_filename')->nullable();
            $table->json('references_found')->nullable();
            $table->text('exclusions')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['thesis_version_id', 'analyzed_at']);
        });

        Schema::create('thesis_physical_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_version_id')->constrained('thesis_versions')->restrictOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->boolean('is_compatible')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['thesis_version_id', 'received_at']);
            $table->index(['responsible_id', 'received_at']);
        });

        Schema::create('thesis_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_version_id')->nullable()->constrained('thesis_versions')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24)->default('published')->index();
            $table->string('visibility', 24)->default('public');
            $table->text('reason')->nullable();
            $table->decimal('similarity_percent', 5, 2)->nullable();
            $table->boolean('physical_compatible')->nullable();
            $table->boolean('rights_confirmed')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['thesis_id', 'occurred_at']);
        });

        Schema::create('payment_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 64);
            $table->string('provider_event_id', 191);
            $table->foreignId('thesis_payment_id')->nullable()->constrained('thesis_payments')->nullOnDelete();
            $table->char('payload_hash', 64)->nullable();
            $table->string('processing_status', 24)->default('received')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
            $table->index(['thesis_payment_id', 'received_at']);
        });

        Schema::create('role_assignment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24);
            $table->text('reason')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['role_id', 'occurred_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->nullableMorphs('subject');
            $table->string('result', 24)->default('success');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_group', 64)->default('general');
            $table->json('value');
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['setting_group', 'is_public']);
        });

        foreach (DB::table('role_user')->get(['user_id', 'role_id', 'created_at']) as $assignment) {
            DB::table('role_assignment_events')->insert([
                'user_id' => $assignment->user_id,
                'role_id' => $assignment->role_id,
                'action' => 'initial_assignment',
                'occurred_at' => $assignment->created_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->where('deposit_authorized', true)->get(['id', 'updated_at']) as $user) {
            DB::table('deposit_authorizations')->insert([
                'student_id' => $user->id,
                'status' => 'active',
                'authorized_at' => $user->updated_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $versions = DB::table('thesis_versions')
            ->where(function ($query) {
                $query->where('analysis_status', '!=', 'pending')
                    ->orWhereNotNull('analysis_provider')
                    ->orWhereNotNull('analysis_reference')
                    ->orWhereNotNull('similarity_percent')
                    ->orWhereNotNull('analysis_report_path')
                    ->orWhereNotNull('analysis_note');
            })
            ->get([
                'id',
                'analysis_provider',
                'analysis_reference',
                'analysis_status',
                'similarity_percent',
                'analysis_report_path',
                'analysis_report_filename',
                'analysis_exclusions',
                'analysis_note',
                'analyzed_at',
            ]);

        foreach ($versions as $version) {
            DB::table('similarity_reports')->insert([
                'thesis_version_id' => $version->id,
                'provider' => $version->analysis_provider,
                'provider_reference' => $version->analysis_reference,
                'status' => $version->analysis_status,
                'similarity_percent' => $version->similarity_percent,
                'analysis_result' => $version->analysis_note,
                'private_report_path' => $version->analysis_report_path,
                'original_report_filename' => $version->analysis_report_filename,
                'exclusions' => $version->analysis_exclusions,
                'analyzed_at' => $version->analyzed_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('thesis_versions')->whereNotNull('physical_received_at')->get([
            'id',
            'thesis_id',
            'physical_received_by',
            'physical_received_at',
        ]) as $version) {
            DB::table('thesis_physical_receipts')->insert([
                'thesis_id' => $version->thesis_id,
                'thesis_version_id' => $version->id,
                'responsible_id' => $version->physical_received_by,
                'received_at' => $version->physical_received_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('theses')->whereNotNull('published_at')->orWhere('status', 'published')->get([
            'id',
            'published_at',
        ]) as $thesis) {
            $version = DB::table('thesis_versions')
                ->where('thesis_id', $thesis->id)
                ->orderByDesc('version_number')
                ->first(['id']);

            DB::table('thesis_publications')->insert([
                'thesis_id' => $thesis->id,
                'thesis_version_id' => $version?->id,
                'action' => 'published',
                'visibility' => 'public',
                'similarity_percent' => $version?->id
                    ? DB::table('thesis_versions')->where('id', $version->id)->value('similarity_percent')
                    : null,
                'physical_compatible' => $version?->id
                    ? DB::table('thesis_physical_receipts')
                        ->where('thesis_version_id', $version->id)
                        ->orderByDesc('received_at')
                        ->value('is_compatible')
                    : null,
                'rights_confirmed' => $version?->id
                    ? DB::table('thesis_versions')->where('id', $version->id)->whereNotNull('rights_confirmed_at')->exists()
                    : null,
                'occurred_at' => $thesis->published_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('thesis_consultations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('thesis_consultations')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Anonymous consultation history must be retained before rolling back this migration.');
        }

        Schema::table('thesis_consultations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('role_assignment_events');
        Schema::dropIfExists('payment_webhook_receipts');
        Schema::dropIfExists('thesis_publications');
        Schema::dropIfExists('thesis_physical_receipts');
        Schema::dropIfExists('similarity_reports');
        Schema::dropIfExists('deposit_authorizations');
    }
};
