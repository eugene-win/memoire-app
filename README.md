<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Base de données

Le projet utilise des migrations Laravel et prend en charge SQLite pour le développement ainsi que MySQL pour le déploiement. Pour SQLite, créez `database/database.sqlite` puis appliquez les migrations avec `php artisan migrate`. Pour MySQL, configurez les variables `DB_*` dans l’environnement avant d’appliquer les migrations.

Le fichier SQLite est ignoré par Git. Après les migrations, lancez `php artisan db:seed` pour installer les rôles, permissions, filières, niveaux et paramètres initiaux. Le schéma, les relations, les données de référence et les consignes de migration sont décrits dans [la documentation de la base](docs/database-schema.md).

En environnement `local` uniquement, `php artisan db:seed` crée un registre fictif de 31 étudiants (L1 à L3) et les comptes de démonstration. Accès de démonstration :

| Profil | Identifiant de connexion | Accès |
| --- | --- | --- |
| Lecteur externe | `lecteur.demo@example.test` | `LecteurDemo!2026` |
| Étudiant ENEAM | `DEMO-ETU-001` | Code à usage unique envoyé par e-mail |
| Responsable de filière | `DEMO-VAL-001` | `FiliereDemo!2026` |
| Responsable de bibliothèque | `DEMO-BIB-001` | `BibliothequeDemo!2026` |
| Administrateur | `administrateur.demo@example.test` | `AdminDemo!2026` |

### Registre fictif des 31 étudiants

Voici l’instantané local du 27 novembre 2026 du registre généré par [StudentRegistrySeeder](database/seeders/StudentRegistrySeeder.php). Les données sont fictives; la partie `2026` des matricules `ENEAM-TEST` suit l’année du seeding et les dates calculées depuis la date courante sont recalculées à chaque seeding.

| Matricule | Étudiant | Niveau | Classe | Naissance (date, lieu) | Inscription | Statut | Soutenu | E-mail |
| --- | --- | --- | --- | --- | ---: | --- | --- | --- |
| `DEMO-ETU-001` | Étudiant Démo | L3 | IG3 | 2002-05-17, Cotonou | 2023 | Inscrit | Oui | `marcos134michel@gmail.com` |
| `ENEAM-TEST-2026-001` | Étudiant Démo 001 | L1 | IG1 | 2007-11-16, Cotonou | 2026 | Inscrit | Non | `marcos135michel@gmail.com` |
| `ENEAM-TEST-2026-002` | Étudiant Démo 002 | L1 | GCO1 | 2006-11-05, Porto-Novo | 2026 | Inscrit | Non | `michel136michel@gmail.com` |
| `ENEAM-TEST-2026-003` | Étudiant Démo 003 | L1 | GFC1 | 2005-10-25, Parakou | 2026 | Inscrit | Non | `eugeneakoho@gmail.com` |
| `ENEAM-TEST-2026-004` | Étudiant Démo 004 | L1 | GTL1 | 2004-10-14, Abomey | 2026 | Inscrit | Non | `nazifathmango@gmail.com` |
| `ENEAM-TEST-2026-005` | Étudiant Démo 005 | L1 | PLAN1 | 2003-10-03, Bohicon | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-006` | Étudiant Démo 006 | L1 | IG1 | 2002-09-22, Cotonou | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-007` | Étudiant Démo 007 | L1 | GCO1 | 2001-09-11, Porto-Novo | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-008` | Étudiant Démo 008 | L1 | GFC1 | 2008-08-31, Parakou | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-009` | Étudiant Démo 009 | L1 | GTL1 | 2007-08-20, Abomey | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-010` | Étudiant Démo 010 | L1 | PLAN1 | 2006-08-09, Bohicon | 2026 | Inscrit | Non | — |
| `ENEAM-TEST-2026-011` | Étudiant Démo 011 | L2 | IG2 | 2005-07-29, Cotonou | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-012` | Étudiant Démo 012 | L2 | GCO2 | 2004-07-18, Porto-Novo | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-013` | Étudiant Démo 013 | L2 | GFC2 | 2003-07-07, Parakou | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-014` | Étudiant Démo 014 | L2 | GTL2 | 2002-06-26, Abomey | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-015` | Étudiant Démo 015 | L2 | PLAN2 | 2001-06-15, Bohicon | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-016` | Étudiant Démo 016 | L2 | IG2 | 2008-06-04, Cotonou | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-017` | Étudiant Démo 017 | L2 | GCO2 | 2007-05-24, Porto-Novo | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-018` | Étudiant Démo 018 | L2 | GFC2 | 2006-05-13, Parakou | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-019` | Étudiant Démo 019 | L2 | GTL2 | 2005-05-02, Abomey | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-020` | Étudiant Démo 020 | L2 | PLAN2 | 2004-04-21, Bohicon | 2025 | Inscrit | Non | — |
| `ENEAM-TEST-2026-021` | Étudiant Démo 021 | L3 | IG3 | 2003-04-10, Cotonou | 2024 | Inscrit | Non | — |
| `ENEAM-TEST-2026-022` | Étudiant Démo 022 | L3 | GCO3 | 2002-03-30, Porto-Novo | 2024 | Inscrit | Oui | — |
| `ENEAM-TEST-2026-023` | Étudiant Démo 023 | L3 | GFC3 | 2001-03-19, Parakou | 2024 | Inscrit | Non | — |
| `ENEAM-TEST-2026-024` | Étudiant Démo 024 | L3 | GTL3 | 2008-03-08, Abomey | 2024 | Inscrit | Oui | — |
| `ENEAM-TEST-2026-025` | Étudiant Démo 025 | L3 | PLAN3 | 2007-02-25, Bohicon | 2024 | Inscrit | Non | — |
| `ENEAM-TEST-2026-026` | Étudiant Démo 026 | L3 | IG3 | 2006-02-14, Cotonou | 2024 | Inscrit | Oui | — |
| `ENEAM-TEST-2026-027` | Étudiant Démo 027 | L3 | GCO3 | 2005-02-03, Porto-Novo | 2024 | Inscrit | Non | — |
| `ENEAM-TEST-2026-028` | Étudiant Démo 028 | L3 | GFC3 | 2004-01-24, Parakou | 2024 | Inscrit | Oui | — |
| `ENEAM-TEST-2026-029` | Étudiant Démo 029 | L3 | GTL3 | 2003-01-12, Abomey | 2024 | Inscrit | Non | — |
| `ENEAM-TEST-2026-030` | Étudiant Démo 030 | L3 | PLAN3 | 2002-01-01, Bohicon | 2024 | Diplômé | Oui | — |

Les cinq adresses fournies sont affectées chacune à un dossier distinct. Les 26 autres profils n’ont pas d’adresse et ne peuvent pas recevoir de code avant l’import d’une adresse unique valide.

La connexion utilise un seul champ : les visiteurs et administrateurs utilisent leur adresse e-mail, les étudiants leur matricule et le personnel son matricule; les étudiants reçoivent un code à l’adresse de leur dossier. Le rôle enregistré détermine l’espace ouvert. Ces comptes et données fictives ne sont jamais créés en préproduction ou en production. Les mots de passe des comptes visiteurs et du personnel ne sont pas réinitialisés lors d’un nouvel appel à `db:seed`.

### Réception des codes par e-mail

La configuration locale actuelle utilise `MAIL_MAILER=log` : elle **n’envoie aucun e-mail**; le message est écrit dans `storage/logs/laravel.log`. Pour recevoir les codes dans Gmail, configurez l’expéditeur SMTP dans votre `.env` (sans partager ni versionner le mot de passe) :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse-expediteur@gmail.com
MAIL_PASSWORD=mot-de-passe-d-application-google
MAIL_FROM_ADDRESS=votre-adresse-expediteur@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail nécessite généralement la validation en deux étapes et un mot de passe d’application; le mot de passe normal du compte ne convient pas. Utilisez les 16 caractères du mot de passe d’application sans espaces dans `MAIL_PASSWORD`. Après modification, exécutez `php artisan config:clear` et redémarrez le serveur Laravel. L’adresse expéditrice peut être différente de l’adresse étudiante qui reçoit le code.

## Workflow des mémoires

Pour que la limite applicative de 5 Mio soit effective, le `php.ini` utilisé par le serveur doit définir `upload_max_filesize=5M` et `post_max_size=10M`. Redémarrez le serveur PHP après cette modification.

Un dépôt est accepté uniquement si le compte actif correspond à un étudiant ENEAM inscrit en L3, vérifié par l’établissement, ayant soutenu et disposant d’une autorisation de dépôt active. Le PDF (5 Mio maximum par défaut) est enregistré dans le stockage privé; les versions, métadonnées historiques, consentements, empreintes et décisions sont conservés en base.

Les responsables de filière (`validator`) et de bibliothèque (`library_manager`) peuvent enregistrer le résultat reçu du fournisseur anti-plagiat, ses exclusions et, s’il est disponible, joindre son rapport PDF privé. Le rapport est accessible uniquement aux responsables habilités. Un taux supérieur à 20 % rejette automatiquement le dépôt; un résultat acceptable requiert ensuite la confirmation de réception physique et une décision humaine motivée. Une erreur d’analyse laisse le dossier en attente. L’analyse automatique nécessite encore le choix et la configuration du fournisseur de l’ENEAM.

Les PDF sont consultés dans une visionneuse protégée : le propriétaire du dépôt et les responsables habilités peuvent accéder à leurs dossiers; les étudiants ENEAM vérifiés peuvent lire les mémoires publics. Les documents restent dans le stockage privé et sont servis sans cache. Le catalogue est filtré et paginé côté serveur. Les visiteurs peuvent voir un aperçu du nombre de pages configuré par l’administration; les lecteurs externes obtiennent l’accès intégral après confirmation serveur du paiement.

Les notifications internes sont enregistrées pour les dépôts, les analyses, les rejets, les publications et les paiements; elles apparaissent dans les espaces concernés et peuvent être marquées comme lues. Chaque accès autorisé au PDF est journalisé avec le compte, le mémoire, la version, le type d’accès et l’heure; l’historique est disponible dans les espaces lecteur et étudiant.

## Import des archives

Les responsables peuvent importer les archives depuis leur espace commun de validation. L’import exige les métadonnées, la source, un PDF non dupliqué, le contrôle du document physique et la confirmation des droits de diffusion. La publication, l’auteur de l’import et le résultat du contrôle sont historisés; aucun compte étudiant n’est créé pour un auteur d’archive.

## Paiement KKiaPay

L’accès intégral d’un lecteur externe coûte 1 000 F CFA (XOF). Pour activer KKiaPay, renseignez localement `KKIAPAY_PUBLIC_KEY`, `KKIAPAY_PRIVATE_KEY`, `KKIAPAY_SECRET` et `KKIAPAY_WEBHOOK_SECRET` à partir du tableau de bord marchand. Gardez ces valeurs confidentielles et ne les ajoutez jamais au dépôt. `KKIAPAY_SANDBOX=true` active le mode test; passez-le à `false` pour la production.

Configurez dans le tableau de bord KKiaPay le webhook `https://<votre-domaine>/api/webhooks/kkiapay` et utilisez le même secret hash que `KKIAPAY_WEBHOOK_SECRET`. Le webhook et le retour du widget déclenchent tous deux une vérification serveur de la transaction; seul un statut `SUCCESS` avec le bon montant, la bonne devise, la bonne référence et le bon identifiant active le droit d’accès.

L’inscription publique est réservée aux visiteurs. Un étudiant saisit son matricule et reçoit un code à usage unique à l’adresse enregistrée dans `student_registry`; le code expire après 10 minutes et cinq essais au maximum sont autorisés. Le compte et son profil sont créés ou associés après vérification réussie. Les adresses de registre doivent être valides et uniques; les cinq adresses fournies pour la démonstration sont affectées à cinq dossiers distincts, les autres dossiers synthétiques restent sans adresse jusqu’à l’import d’une adresse institutionnelle vérifiée. Le niveau et la classe proviennent du registre; le module de dépôt n’est affiché qu’aux profils L3 et reste soumis à la soutenance et à une autorisation active. Les 31 lignes synthétiques ne sont seedées qu’en local. Configurez `MAIL_*` pour livrer les codes; `MAIL_MAILER=log` les écrit dans le journal local. Les comptes responsables et administrateurs sont provisionnés par l’administration, jamais par inscription publique.

## Administration backend

L’administration expose des routes JSON protégées par les permissions administratives pour les comptes, rôles, profils étudiants, référentiels, paramètres, journaux d’activité et statistiques. Le premier compte administrateur doit être attribué depuis la console après `php artisan db:seed`, par exemple : `php artisan platform:assign-role responsable@example.com administrator --reason="Provisionnement initial"`. Les actions sensibles et les changements de rôles sont historisés.

Le formulaire unique détecte le compte à partir d’une adresse e-mail ou d’un matricule : les étudiants utilisent un code e-mail, les visiteurs et le personnel leur mot de passe. L’inscription publique ne concerne que les visiteurs. La liste des endpoints et leurs permissions est décrite dans [la documentation du backend](docs/backend-api.md).

## Réinitialisation du mot de passe

Le formulaire envoie un lien à durée limitée via le mailer Laravel; la réponse reste identique qu’un compte existe ou non. Configurez `MAIL_*` et `APP_URL` pour l’environnement utilisé. En développement, avec `MAIL_MAILER=log`, le lien est écrit dans `storage/logs/laravel.log`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
