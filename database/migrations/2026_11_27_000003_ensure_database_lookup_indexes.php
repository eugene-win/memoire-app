<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'users' => [
            ['role', 'deposit_authorized'],
        ],
        'students' => [
            ['study_level_id', 'enrollment_status'],
        ],
        'theses' => [
            ['title'],
            ['author_name'],
            ['matricule'],
            ['department'],
            ['defended_year'],
            ['filiere_id'],
            ['defense_year_id'],
            ['published_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            foreach ($indexes as $columns) {
                if (! Schema::hasIndex($tableName, $columns)) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                        $table->index($columns);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            foreach ($indexes as $columns) {
                if (Schema::hasIndex($tableName, $columns)) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                        $table->dropIndex($columns);
                    });
                }
            }
        }
    }
};
