<?php

namespace Database\Seeders;

use App\Models\DepositAuthorization;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Les comptes de démonstration peuvent être créés uniquement en environnement local.');
        }

        $validator = $this->makeUser(
            'responsable.filiere.demo@example.test',
            'DEMO-VAL-001',
            'Responsable',
            'Filière',
            'validator',
            'FiliereDemo!2026',
        );
        $this->makeUser(
            'lecteur.demo@example.test',
            null,
            'Lecteur',
            'Démo',
            'external_reader',
            'LecteurDemo!2026',
        );
        $this->makeUser(
            'responsable.bibliotheque.demo@example.test',
            'DEMO-BIB-001',
            'Responsable',
            'Bibliothèque',
            'library_manager',
            'BibliothequeDemo!2026',
        );
        $this->makeUser(
            'administrateur.demo@example.test',
            null,
            'Administrateur',
            'Démo',
            'administrator',
            'AdminDemo!2026',
        );
        $studentRecord = StudentRegistry::query()
            ->where('matricule', 'DEMO-ETU-001')
            ->firstOrFail();
        $studentUser = $this->makeUser(
            null,
            $studentRecord->matricule,
            $studentRecord->first_name,
            $studentRecord->last_name,
            'student',
            Str::random(64),
            true,
        );
        $studentRecord->update(['user_id' => $studentUser->id]);
        Student::query()->updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'matricule' => $studentUser->matricule,
                'filiere_id' => $studentRecord->filiere_id,
                'study_level_id' => $studentRecord->study_level_id,
                'defense_year_id' => $studentRecord->defense_year_id,
                'enrollment_status' => $studentRecord->academic_status,
                'institutionally_verified_at' => now(),
                'defended_at' => $studentRecord->defended_at,
            ],
        );
        DepositAuthorization::query()->updateOrCreate(
            ['student_id' => $studentUser->id],
            [
                'authorized_by_id' => $validator->id,
                'status' => 'active',
                'reason' => 'Autorisation destinée aux essais locaux.',
                'authorized_at' => now(),
                'expires_at' => null,
            ],
        );
    }

    private function makeUser(
        ?string $email,
        ?string $matricule,
        string $firstName,
        string $lastName,
        string $roleSlug,
        string $password,
        bool $depositAuthorized = false,
    ): User {
        $role = Role::query()->where('slug', $roleSlug)->first();
        if (! $role) {
            throw new RuntimeException('Le rôle '.$roleSlug.' est absent. Exécutez les seeders de référence avant les comptes de démonstration.');
        }

        $identityColumn = $email === null ? 'matricule' : 'email';
        $user = User::query()->firstOrCreate(
            [$identityColumn => $email ?? $matricule],
            [
                'name' => $firstName.' '.$lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'matricule' => $matricule,
                'role' => $roleSlug,
                'account_status' => 'active',
                'deposit_authorized' => $depositAuthorized,
                'password' => $password,
            ],
        );
        $user->update([
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'matricule' => $matricule,
            'role' => $roleSlug,
            'requested_role' => null,
            'account_status' => 'active',
            'deposit_authorized' => $depositAuthorized,
        ]);
        if ($roleSlug === 'student') {
            $user->update(['password' => Str::random(64)]);
        }
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
