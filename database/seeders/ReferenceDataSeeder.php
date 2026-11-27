<?php

namespace Database\Seeders;

use App\Models\Filiere;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudyLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'FIN-COMP', 'name' => 'Finance et comptabilité'],
            ['code' => 'GEST-ENT', 'name' => 'Gestion des entreprises'],
            ['code' => 'MARK-COM', 'name' => 'Marketing et commerce'],
            ['code' => 'INFO-GEST', 'name' => 'Informatique de gestion'],
            ['code' => 'ECO-APP', 'name' => 'Économie appliquée'],
        ] as $filiere) {
            $legacyFiliere = Filiere::query()
                ->where('name', $filiere['name'])
                ->where('code', 'like', 'legacy-%')
                ->first();
            $canonicalFiliereExists = Filiere::query()->where('code', $filiere['code'])->exists();

            if ($legacyFiliere && ! $canonicalFiliereExists) {
                $legacyFiliere->update(['code' => $filiere['code']]);
            } elseif (! $canonicalFiliereExists && ! $legacyFiliere) {
                Filiere::firstOrCreate(
                    ['code' => $filiere['code']],
                    ['name' => $filiere['name'], 'is_active' => true],
                );
            }
        }

        foreach ([
            ['code' => 'L1', 'name' => 'Licence 1'],
            ['code' => 'L2', 'name' => 'Licence 2'],
            ['code' => 'L3', 'name' => 'Licence 3'],
        ] as $level) {
            StudyLevel::firstOrCreate(
                ['code' => $level['code']],
                ['name' => $level['name'], 'is_active' => true],
            );
        }

        $roleDefinitions = [
            'external_reader' => ['Lecteur externe', 'Compte externe de consultation payante.'],
            'student' => ['Étudiant ENEAM', 'Étudiant inscrit et vérifié par l’établissement.'],
            'validator' => ['Responsable de filière', 'Responsable du contrôle et des décisions.'],
            'library_manager' => ['Responsable de bibliothèque', 'Responsable du contrôle et de l’import des archives.'],
            'administrator' => ['Administrateur', 'Administration des comptes, référentiels et paramètres.'],
        ];

        $permissionDefinitions = [
            'catalog.view' => ['Consulter le catalogue', 'Accéder aux fiches publiques du catalogue.'],
            'thesis.preview' => ['Consulter un aperçu', 'Consulter les pages d’aperçu publiques.'],
            'thesis.published.read' => ['Lire les mémoires publiés', 'Accéder aux mémoires publiés en tant qu’étudiant vérifié.'],
            'thesis.purchased.read' => ['Lire un mémoire acheté', 'Accéder aux mémoires couverts par un paiement confirmé.'],
            'payment.create' => ['Initier un paiement', 'Acheter l’accès à un mémoire précis.'],
            'deposit.create' => ['Initier un dépôt', 'Créer un dépôt si les conditions institutionnelles sont réunies.'],
            'thesis.review' => ['Examiner un dépôt', 'Examiner les dossiers et versions déposés.'],
            'similarity_reports.read' => ['Consulter les rapports de similitude', 'Consulter les rapports réservés aux responsables habilités.'],
            'physical_receipts.manage' => ['Gérer les réceptions physiques', 'Enregistrer la réception et la comparaison du mémoire physique.'],
            'thesis.publish' => ['Publier un mémoire', 'Enregistrer une décision de publication.'],
            'thesis.reject' => ['Rejeter un dépôt', 'Enregistrer une décision de rejet motivée.'],
            'archives.import' => ['Importer une archive', 'Enregistrer un mémoire historique.'],
            'metadata.manage' => ['Gérer les métadonnées', 'Administrer les métadonnées documentaires selon habilitation.'],
            'users.manage' => ['Gérer les comptes', 'Administrer les comptes et leur statut.'],
            'roles.manage' => ['Gérer les rôles', 'Attribuer ou révoquer des rôles et permissions.'],
            'deposit_authorizations.manage' => ['Gérer les autorisations de dépôt', 'Accorder ou révoquer les autorisations de dépôt étudiantes.'],
            'references.manage' => ['Gérer les référentiels', 'Gérer les filières, niveaux et années de soutenance.'],
            'platform_settings.manage' => ['Gérer les paramètres', 'Gérer les paramètres persistés de la plateforme.'],
            'activity_logs.read' => ['Lire les journaux', 'Consulter les journaux d’activité habilités.'],
        ];

        $permissions = [];
        foreach ($permissionDefinitions as $slug => [$name, $description]) {
            $permissions[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description],
            );
        }

        $rolePermissions = [
            'external_reader' => ['catalog.view', 'thesis.preview', 'thesis.purchased.read', 'payment.create'],
            'student' => ['catalog.view', 'thesis.preview', 'thesis.published.read', 'deposit.create'],
            'validator' => ['thesis.review', 'similarity_reports.read', 'physical_receipts.manage', 'thesis.publish', 'thesis.reject'],
            'library_manager' => ['thesis.review', 'similarity_reports.read', 'physical_receipts.manage', 'thesis.publish', 'thesis.reject', 'archives.import', 'metadata.manage'],
            'administrator' => ['users.manage', 'roles.manage', 'deposit_authorizations.manage', 'references.manage', 'platform_settings.manage', 'activity_logs.read'],
        ];

        $roles = [];
        foreach ($roleDefinitions as $slug => [$name, $description]) {
            $roles[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description],
            );
            if ($roles[$slug]->description === null) {
                $roles[$slug]->update(['description' => $description]);
            }
            $roles[$slug]->permissions()->syncWithoutDetaching(
                array_map(fn (string $permissionSlug): int => $permissions[$permissionSlug]->id, $rolePermissions[$slug]),
            );
        }

        User::query()->whereNotNull('role')->orderBy('id')->chunkById(100, function ($users) use ($roles): void {
            foreach ($users as $user) {
                if (isset($roles[$user->role])) {
                    $user->roles()->syncWithoutDetaching([$roles[$user->role]->id]);
                }
            }
        });
    }
}
