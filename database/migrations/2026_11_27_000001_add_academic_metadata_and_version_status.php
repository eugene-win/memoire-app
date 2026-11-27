<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theses', function (Blueprint $table) {
            $table->foreignId('filiere_id')->nullable()->constrained('filieres')->nullOnDelete();
            $table->foreignId('defense_year_id')->nullable()->constrained('defense_years')->nullOnDelete();
            $table->text('abstract')->nullable();
            $table->json('keywords')->nullable();
            $table->string('visibility', 24)->default('public')->index();
        });

        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->string('mime_type', 127)->nullable();
            $table->string('version_status', 32)->default('submitted')->index();
            $table->text('rights_statement_snapshot')->nullable();
        });

        Schema::table('thesis_decisions', function (Blueprint $table) {
            $table->string('source', 16)->default('unclassified');
        });

        Schema::table('theses', function (Blueprint $table) {
            $table->index('title');
            $table->index('author_name');
            $table->index('matricule');
            $table->index('department');
            $table->index('defended_year');
            $table->index('filiere_id');
            $table->index('defense_year_id');
            $table->index('published_at');
        });

        foreach (DB::table('theses')->whereNotNull('department')->distinct()->pluck('department') as $department) {
            $code = 'legacy-'.substr(hash('sha256', strtolower(trim((string) $department))), 0, 20);
            DB::table('filieres')->insertOrIgnore([
                'code' => $code,
                'name' => $department,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $filiereId = DB::table('filieres')->where('code', $code)->value('id');
            DB::table('theses')->where('department', $department)->update(['filiere_id' => $filiereId]);
        }

        foreach (DB::table('theses')->whereNotNull('defended_year')->distinct()->pluck('defended_year') as $year) {
            $label = (string) $year;
            DB::table('defense_years')->insertOrIgnore([
                'label' => $label,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $defenseYearId = DB::table('defense_years')->where('label', $label)->value('id');
            DB::table('theses')->where('defended_year', $year)->update(['defense_year_id' => $defenseYearId]);
        }

        DB::table('thesis_versions')->whereNull('submitted_at')->update([
            'submitted_at' => DB::raw('created_at'),
        ]);

        DB::table('thesis_versions')->update(['version_status' => 'legacy']);
        foreach (DB::table('thesis_versions')
            ->join('theses', 'theses.id', '=', 'thesis_versions.thesis_id')
            ->whereNull('thesis_versions.submitted_by_id')
            ->get(['thesis_versions.id', 'theses.student_id']) as $version) {
            if ($version->student_id !== null) {
                DB::table('thesis_versions')->where('id', $version->id)->update([
                    'submitted_by_id' => $version->student_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('thesis_decisions', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->dropIndex(['version_status']);
            $table->dropForeign(['submitted_by_id']);
            $table->dropColumn([
                'submitted_by_id',
                'submitted_at',
                'mime_type',
                'version_status',
                'rights_statement_snapshot',
            ]);
        });

        Schema::table('theses', function (Blueprint $table) {
            foreach ([
                ['title'],
                ['author_name'],
                ['matricule'],
                ['department'],
                ['defended_year'],
                ['filiere_id'],
                ['defense_year_id'],
                ['published_at'],
            ] as $columns) {
                if (Schema::hasIndex('theses', $columns)) {
                    $table->dropIndex($columns);
                }
            }
            $table->dropIndex(['visibility']);
            $table->dropForeign(['filiere_id']);
            $table->dropForeign(['defense_year_id']);
            $table->dropColumn([
                'filiere_id',
                'defense_year_id',
                'abstract',
                'keywords',
                'visibility',
            ]);
        });
    }
};
