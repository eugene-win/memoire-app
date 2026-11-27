# Schéma de la base de données

## Moteur et migrations

Le projet utilise les migrations Laravel pour SQLite en développement et dispose d’une connexion MySQL configurée. Le moteur réellement choisi dépend de `DB_CONNECTION`; aucune valeur de connexion ni aucun secret n’est inclus dans le dépôt.

Pour une base MySQL vide, créer au préalable la base et un compte dédié, renseigner `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD` dans l’environnement, puis exécuter :

```shell
php artisan config:clear
php artisan migrate --pretend
php artisan migrate
php artisan db:seed
```

Ne pas utiliser `migrate:fresh` ou `migrate:reset` sur une base contenant des données. Les migrations récentes étendent le schéma par ajout de tables et de colonnes; elles conservent les champs historiques employés par l’application.

Les tests de structure et de contraintes se lancent avec :

```shell
php artisan test --filter=DatabaseSchemaTest
```

`DatabaseSeeder` crée les rôles/permissions, les niveaux connus L1 à L3, les filières reprises du formulaire existant et les paramètres initiaux. En environnement `local` uniquement, il charge aussi 31 dossiers synthétiques dans le registre étudiant et les comptes de démonstration. Les années de soutenance sont créées au premier usage ou gérées par l’administration.

## Tables et responsabilités

### Identités, rôles et référentiels

| Tables | Rôle |
| --- | --- |
| `users` | Comptes, identifiants, statut et compatibilité avec les champs historiques `role`, `matricule`, `deposit_authorized`. `email` peut être nul pour les étudiants authentifiés par le code e-mail conservé dans le registre. |
| `students` | Profil institutionnel distinct : matricule, filière, niveau, statut d’inscription, vérification et soutenance. Un utilisateur peut avoir au plus un profil étudiant. |
| `student_registry` | Registre académique source indexé par matricule : identité, e-mail institutionnel unique, classe, date et lieu de naissance, filière, niveau, année d’inscription, statut académique et éventuelle soutenance. `user_id` relie le dossier à un compte après vérification du code e-mail. |
| `student_login_challenges` | Défis de connexion temporaires : empreintes du jeton, du code et de l’e-mail destinataire, expiration, compteur d’essais et date de consommation. Le code brut n’est pas stocké. |
| `filieres` | Référentiel des filières ENEAM, code unique et activation. |
| `study_levels` | Référentiel des niveaux. Le seeder fournit L1, L2 et L3. |
| `defense_years` | Référentiel des années de soutenance. |
| `roles`, `permissions`, `role_user`, `permission_role` | Rôles multiples par compte et permissions granulaires. Les tables d’association interdisent les doublons. |
| `role_assignment_events` | Historique attribuable des ajouts/retraits de rôles. |
| `deposit_authorizations`, `deposit_authorization_events` | État courant d’autorisation et historique des décisions. |

### Mémoires et workflow

| Tables | Rôle |
| --- | --- |
| `theses` | Mémoire et métadonnées descriptives; les textes historiques `department` et `defended_year` sont conservés à côté des nouvelles clés de référentiel. |
| `thesis_versions` | Fichiers et snapshots immuables par numéro de version, métadonnées de soumission et état courant. Le numéro est unique par mémoire et l’empreinte reste unique. |
| `similarity_reports` | Historique de plusieurs analyses par version, avec rapport privé, résultat, références et exclusions. |
| `thesis_physical_receipts` | Réceptions et résultats de comparaison physique historisés par version. |
| `thesis_decisions` | Décisions historisées; le champ de source distingue les décisions humaines, système ou encore non classées. |
| `thesis_publications` | Historique de publication/restriction et instantané des contrôles connus au moment de la publication. |
| `thesis_archive_imports` | Source, responsable et contrôles associés à l’import d’un mémoire d’archive. |

### Accès, paiements et exploitation

| Tables | Rôle |
| --- | --- |
| `thesis_consultations` | Historique des consultations; le compte est nullable pour enregistrer un aperçu public anonyme. |
| `thesis_payments` | Transactions liées à un compte et à un mémoire. |
| `payment_webhook_receipts` | Identifiant unique par fournisseur/événement pour rendre possible le traitement idempotent sans persister le payload complet. |
| `thesis_access_grants` | Droit associé à un compte et un mémoire; unicité par couple compte/mémoire. |
| `notifications` | Notifications Laravel pour tout type de destinataire. |
| `activity_logs` | Journal générique des opérations, acteur, cible, résultat et métadonnées minimisées. |
| `platform_settings` | Paramètres typés en JSON, classés et privés par défaut. |

Les autres tables Laravel de cache, session et file d’attente restent gérées par leurs migrations framework.

## Relations et contraintes principales

- `students.user_id` est unique et pointe vers `users`; un étudiant est rattaché à un seul profil. `students.matricule` est unique lorsqu’il est renseigné. Les profils référencent `filieres`, `study_levels` et `defense_years`.
- `student_registry.matricule` et les e-mails non nuls sont uniques; `student_registry.user_id` est nullable et unique, ce qui empêche qu’un dossier institutionnel soit lié à plusieurs comptes. Après vérification du code, les données du registre sont copiées vers `users` et `students` dans une transaction.
- `student_login_challenges.challenge_token_hash` est unique; chaque défi référence un dossier étudiant et conserve un code haché, une expiration et un compteur limité à cinq tentatives. Une consommation invalide tout rejeu.
- `role_user` et `permission_role` ont des clés primaires composites pour empêcher les associations dupliquées. `role_assignment_events` garde l’historique indépendamment de l’état courant.
- `theses.filiere_id` et `theses.defense_year_id` référencent les référentiels, tandis que `department` et `defended_year` sont conservés comme valeurs historiques.
- `thesis_versions` référence un mémoire; le couple `(thesis_id, version_number)` est unique. `thesis_decisions` conserve la version examinée. Les rapports et réceptions ont une relation vers la version concernée; leurs historiques ne sont pas limités à une ligne par version.
- `thesis_publications` garde les actions de publication/restriction et l’état connu des contrôles. Les suppressions de mémoires et versions liées à des historiques sont restreintes.
- `thesis_payments` et `thesis_access_grants` conservent leurs clés étrangères et leur unicité par compte/mémoire. `payment_webhook_receipts` impose l’unicité `(provider, provider_event_id)`.
- `thesis_consultations.user_id` est nullable afin de journaliser les aperçus anonymes. Les index couvrent l’acteur/date, la cible et les statuts utilisés pour les historiques.
- `activity_logs.user_id` est nullable pour préserver les traces après désactivation/suppression d’un compte; les métadonnées sont JSON et doivent rester minimisées.

## Règles de données et compatibilité

- L’inscription publique est réservée aux visiteurs. L’étudiant saisit son matricule; un code à usage unique est envoyé à l’adresse valide et unique du registre, puis nom, prénom, classe et informations académiques sont copiés vers le compte après vérification. Les réponses de demande de code restent génériques pour limiter l’énumération des matricules. Le module de dépôt est visible uniquement si le profil associé au même matricule est au niveau L3 actif.
- L’éligibilité au dépôt est contrôlée par le backend : compte actif, profil étudiant relié à une filière active et au niveau L3 actif, inscription `enrolled`, vérification institutionnelle, date de soutenance et autorisation active non expirée. Les accès complets des étudiants publiés exigent aussi un profil institutionnel vérifié.
- Les données existantes de rôle et d’autorisation restent en place afin de conserver la compatibilité. Les rôles relationnels sont synchronisés par le seeder et la commande de provisionnement; les contrôleurs maintiennent le champ historique `users.role`. L’autorisation durable est stockée dans `deposit_authorizations`, tandis que `users.deposit_authorized` est conservé comme état de compatibilité. Les dossiers de `student_registry` doivent être importés depuis une source ENEAM officielle avec une adresse valide et unique par étudiant; les lignes synthétiques seedées en local/testing ne conviennent pas à la production.
- Les anciens départements et années des mémoires sont gardés comme snapshots, et migrés vers les référentiels sans supprimer les chaînes d’origine.
- Les décisions déjà présentes lors de la migration restent `unclassified`; les décisions de workflow créées depuis l’intégration backend renseignent leur source.
- Les champs de rapport anti-plagiat et de réception historiquement stockés sur la version sont conservés; les migrations les copient dans les tables d’historique lorsqu’ils contiennent des données.
- Les relations historiques utilisent des suppressions restreintes ou une mise à `NULL` afin de préserver les traces. Les fichiers PDF et rapports restent des chemins de stockage; leur confidentialité dépend aussi de la configuration de stockage et du backend, pas uniquement du schéma.
- Les paramètres seedés sont le tarif externe de 1 000 XOF, la limite PDF de 5 Mio et le seuil de similitude de 20 %. L’aperçu public produit une seule première page; aucun PDF intégral n’est déplacé vers un stockage public.
- Les cinq libellés initiaux de filière proviennent des choix déjà présents dans le projet et doivent être validés par l’ENEAM avant la mise en production. Les codes et années officiels restent administrables.

Les endpoints d’administration, le provisionnement du premier administrateur et les contraintes d’accès sont décrits dans [la documentation backend](./backend-api.md).

## Compatibilité MySQL

Les migrations utilisent les abstractions Laravel et ont été exécutées sur SQLite. Avant le déploiement, vérifier la génération SQL avec `migrate --pretend`, puis appliquer les migrations sur une base MySQL de test sauvegardée. Aucun serveur MySQL actif n’était disponible dans l’environnement de cette modification; la validation MySQL en direct reste donc à réaliser.
