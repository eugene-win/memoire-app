# Backend de la bibliothèque ENEAM

## Mise en service

1. Configurer la base dans `DB_*`, puis exécuter `php artisan migrate`.
2. Exécuter `php artisan db:seed` pour charger les rôles, permissions, filières, niveaux et paramètres.
3. Créer ou valider un compte institutionnel existant, puis attribuer le premier rôle administrateur depuis la console :

   ```text
   php artisan platform:assign-role responsable@example.com administrator --reason="Provisionnement initial"
   ```

La connexion utilise un seul champ `identifier` : les visiteurs et le personnel utilisent leur adresse e-mail ou matricule avec un mot de passe; les étudiants saisissent leur matricule pour recevoir un code e-mail à usage unique. L’inscription publique est réservée aux visiteurs; les comptes responsables et administrateurs doivent être provisionnés par l’administration.

Les étudiants se connectent en deux étapes : demande de code à `POST /login/student-code`, puis vérification à `POST /login/student-code/verify`. Le registre `student_registry` doit contenir une adresse valide et unique; les codes expirent après 10 minutes, sont stockés sous forme hachée et limités à cinq essais. Les réponses de demande sont génériques pour réduire l’énumération. Après vérification, le système associe ou crée le compte et copie les données académiques du registre; le niveau n’est jamais déduit du texte du matricule. La classe et le niveau sont lus dans ce registre. Le module de dépôt est affiché uniquement si le profil relié au même matricule possède le niveau actif `L3`. Le dépôt reste protégé par les contrôles d’inscription, de soutenance et d’autorisation active. Le code e-mail remplace la récupération par mot de passe pour les étudiants. `StudentRegistrySeeder` fournit 31 dossiers synthétiques uniquement en local/testing; cinq seulement reçoivent une adresse de démonstration unique, les autres attendent leur adresse officielle. En production, le registre officiel doit être importé avant d’autoriser les connexions étudiantes. Les comptes suspendus ou désactivés ne peuvent pas ouvrir de session ni utiliser une session déjà ouverte.

## Administration

L’espace web d’administration est accessible à `/admin` après connexion. Il fournit le tableau de bord, les comptes et profils étudiants, les autorisations de dépôt, les rôles et permissions, les référentiels, les paramètres et le journal d’activité. Les mêmes routes de consultation continuent de renvoyer du JSON aux clients qui demandent `Accept: application/json`; les opérations de modification restent des endpoints JSON consommables par l’interface et d’autres clients autorisés.

## Endpoints d’administration

Ces routes utilisent la session Laravel et nécessitent une session authentifiée active ainsi que le droit indiqué.

| Méthode | Route | Permission |
| --- | --- | --- |
| `GET` | `/admin` | `users.manage` |
| `GET` | `/admin/users` | `users.manage` |
| `GET` | `/admin/roles` | `roles.manage` |
| `PATCH` | `/admin/users/{user}` | `users.manage` |
| `PATCH` | `/admin/users/{user}/status` | `users.manage` |
| `PATCH` | `/admin/users/{user}/student-profile` | `users.manage` |
| `PUT` | `/admin/users/{user}/role` | `roles.manage` |
| `PUT` | `/admin/roles/{role}/permissions` | `roles.manage` |
| `POST` | `/admin/deposit-authorizations` | `deposit_authorizations.manage` |
| `POST` | `/admin/students/{student}/deposit-authorization` | `deposit_authorizations.manage` |
| `GET`, `POST` | `/admin/references`, `/admin/references/{type}` | `references.manage` |
| `PUT`, `PATCH` | `/admin/references/{type}/{id}` | `references.manage` |
| `GET`, `PATCH` | `/admin/settings`, `/admin/settings/{setting}` | `platform_settings.manage` |
| `GET` | `/admin/activity-logs` | `activity_logs.read` |

Les types de référentiels acceptés sont `filieres`, `study-levels` et `defense-years`. Ils sont désactivés plutôt que supprimés afin de préserver les liens historiques. Le rôle `administrator` ne peut pas perdre `users.manage`; les changements de rôle ne peuvent pas rétrograder le dernier administrateur actif.

Le compte initial est attribué par commande console, les changements ultérieurs sont disponibles via l’endpoint de rôles. Les valeurs configurables comprennent le tarif externe en XOF, la taille maximale des PDF et le nombre de pages d’aperçu (1 à 50; valeur initiale : 1). Le seuil anti-plagiat reste fixé à 20 % : tout résultat strictement supérieur est rejeté automatiquement et le seuil ne peut pas être modifié par l’administration.

Les anciens comptes responsables en attente peuvent encore apparaître dans `GET /admin/users` avec leur `requested_role`; leur traitement administratif reste nécessaire pour ces comptes historiques. Les formulaires d’administration envoient les modifications aux routes JSON existantes et affichent les erreurs de validation sans perdre les contrôles côté serveur.

## Dépôts et accès PDF

Le dépôt requiert un compte actif, un profil étudiant relié à une filière active et au niveau `L3`, un statut d’inscription `enrolled`, une vérification institutionnelle, une date de soutenance et une autorisation de dépôt active non expirée. Les dépôts, imports d’archives et rapports anti-plagiat sont contrôlés comme des PDF lisibles et non chiffrés avant stockage. Toute nouvelle soumission après rejet crée une version supplémentaire.

Les originaux et rapports anti-plagiat restent sur le disque Laravel `local`, sous `storage/app/private`. La route publique `/thesis-versions/{version}/preview` sert au plus le nombre de pages configuré d’une version actuellement publiée avec visibilité publique, sans dépasser le nombre de pages du document. L’accès intégral à un PDF privé reste soumis aux contrôles existants de propriétaire, responsable, étudiant vérifié ou paiement confirmé.

Les décisions, analyses, réceptions physiques (y compris le résultat de comparaison), publications, attributions de rôles, autorisations, paiements et opérations d’administration sont historisés. Les paiements confirment côté serveur la référence, le montant et la devise. Le service métier de confirmation dépend d’un contrat `PaymentGateway`; KKiaPay est l’adaptateur actuellement configuré. Ses webhooks sont dédupliqués par transaction et événement, puis vérifiés de nouveau côté serveur.

## Limites d’intégration

La saisie du résultat anti-plagiat est manuelle tant qu’un fournisseur institutionnel n’est pas choisi et configuré. Le paiement utilise actuellement le fournisseur KKiaPay configuré dans `config/services.php`; les clés et secrets restent dans l’environnement serveur et ne sont jamais affichés dans l’interface d’administration.
