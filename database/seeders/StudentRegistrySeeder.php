<?php

namespace Database\Seeders;

use App\Models\DefenseYear;
use App\Models\Filiere;
use App\Models\StudentRegistry;
use App\Models\StudyLevel;
use Illuminate\Database\Seeder;
use RuntimeException;

class StudentRegistrySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Les données étudiantes de démonstration sont réservées aux environnements local et testing.');
        }

        $filieres = Filiere::query()->where('is_active', true)->orderBy('id')->get()->values();
        $levels = StudyLevel::query()->where('is_active', true)->get()->keyBy('code');
        $currentDefenseYear = DefenseYear::query()->firstOrCreate(
            ['label' => (string) now()->year],
            ['is_active' => true],
        );
        $previousDefenseYear = DefenseYear::query()->firstOrCreate(
            ['label' => (string) now()->subYear()->year],
            ['is_active' => true],
        );
        $demoFiliere = Filiere::query()->where('code', 'INFO-GEST')->firstOrFail();

        if ($filieres->isEmpty() || ! $levels->has('L1') || ! $levels->has('L2') || ! $levels->has('L3')) {
            throw new RuntimeException('Exécutez les données de référence avant les données étudiantes de démonstration.');
        }

        StudentRegistry::query()->updateOrCreate(
            ['matricule' => 'DEMO-ETU-001'],
            [
                'email' => 'marcos134michel@gmail.com',
                'first_name' => 'Étudiant',
                'last_name' => 'Démo',
                'date_of_birth' => '2002-05-17',
                'place_of_birth' => 'Cotonou',
                'filiere_id' => $demoFiliere->id,
                'study_level_id' => $levels['L3']->id,
                'class_code' => 'IG3',
                'registration_year' => now()->year - 3,
                'academic_status' => 'enrolled',
                'defense_year_id' => $currentDefenseYear->id,
                'defended_at' => now()->subMonths(3),
            ],
        );

        $demoEmails = [
            1 => 'marcos135michel@gmail.com',
            2 => 'michel136michel@gmail.com',
            3 => 'eugeneakoho@gmail.com',
            4 => 'nazifathmango@gmail.com',
        ];
        $classPrefixes = ['IG', 'GCO', 'GFC', 'GTL', 'PLAN'];

        for ($number = 1; $number <= 30; $number++) {
            $levelCode = $number <= 10 ? 'L1' : ($number <= 20 ? 'L2' : 'L3');
            $isGraduated = $number === 30;
            $hasDefended = $levelCode === 'L3' && ($number % 2 === 0 || $isGraduated);
            $defenseYear = $number % 4 === 0 ? $previousDefenseYear : $currentDefenseYear;
            $filiere = $filieres[($number - 1) % $filieres->count()];

            StudentRegistry::query()->updateOrCreate(
                ['matricule' => sprintf('ENEAM-TEST-%d-%03d', now()->year, $number)],
                [
                    'email' => $demoEmails[$number] ?? null,
                    'first_name' => 'Étudiant',
                    'last_name' => sprintf('Démo %03d', $number),
                    'date_of_birth' => now()->subYears(18 + ($number % 8))->subDays($number * 11)->toDateString(),
                    'place_of_birth' => ['Cotonou', 'Porto-Novo', 'Parakou', 'Abomey', 'Bohicon'][($number - 1) % 5],
                    'filiere_id' => $filiere->id,
                    'study_level_id' => $levels[$levelCode]->id,
                    'class_code' => $classPrefixes[($number - 1) % count($classPrefixes)].substr($levelCode, 1),
                    'registration_year' => now()->year - ($levelCode === 'L1' ? 0 : ($levelCode === 'L2' ? 1 : 2)),
                    'academic_status' => $isGraduated ? 'graduated' : 'enrolled',
                    'defense_year_id' => $hasDefended ? $defenseYear->id : null,
                    'defended_at' => $hasDefended ? now()->subMonths(($number % 10) + 1) : null,
                ],
            );
        }
    }
}
