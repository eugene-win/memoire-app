<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Base de données locale

Lors d’une première installation avec SQLite, créez le fichier de base puis appliquez les migrations Laravel :

```bash
touch database/database.sqlite
php artisan migrate
```

Le fichier SQLite est ignoré par Git. Les migrations créent les tables des comptes et du workflow de mémoires.

## Workflow des mémoires

Un étudiant ENEAM doit avoir le rôle `student` et l’autorisation institutionnelle `deposit_authorized` active pour soumettre un mémoire. Le PDF (50 Mo maximum) est enregistré dans le stockage privé; les versions, métadonnées historiques, consentements, empreintes et décisions sont conservés en base.

Les responsables de filière (`validator`) et de bibliothèque (`library_manager`) peuvent enregistrer le résultat reçu du fournisseur anti-plagiat, ses exclusions et, s’il est disponible, joindre son rapport PDF privé. Le rapport est accessible uniquement aux responsables habilités. Un taux supérieur à 20 % rejette automatiquement le dépôt; un résultat acceptable requiert ensuite la confirmation de réception physique et une décision humaine motivée. Une erreur d’analyse laisse le dossier en attente. L’analyse automatique nécessite encore le choix et la configuration du fournisseur de l’ENEAM.

Les PDF sont consultés dans une visionneuse protégée : le propriétaire du dépôt et les responsables habilités peuvent accéder à leurs dossiers; les étudiants connectés peuvent lire les mémoires publiés. Les documents restent dans le stockage privé et sont servis sans cache. Les lecteurs externes obtiennent l’accès après confirmation serveur du paiement.

Les notifications internes sont enregistrées pour les dépôts, les analyses, les rejets, les publications et les paiements; elles apparaissent dans les espaces concernés et peuvent être marquées comme lues. Chaque accès autorisé au PDF est journalisé avec le compte, le mémoire, la version, le type d’accès et l’heure; l’historique est disponible dans les espaces lecteur et étudiant.

## Import des archives

Les responsables peuvent importer les archives depuis leur espace commun de validation. L’import exige les métadonnées, la source, un PDF non dupliqué, le contrôle du document physique et la confirmation des droits de diffusion. La publication, l’auteur de l’import et le résultat du contrôle sont historisés; aucun compte étudiant n’est créé pour un auteur d’archive.

## Paiement KKiaPay

L’accès intégral d’un lecteur externe coûte 1 000 F CFA (XOF). Pour activer KKiaPay, renseignez localement `KKIAPAY_PUBLIC_KEY`, `KKIAPAY_PRIVATE_KEY`, `KKIAPAY_SECRET` et `KKIAPAY_WEBHOOK_SECRET` à partir du tableau de bord marchand. Gardez ces valeurs confidentielles et ne les ajoutez jamais au dépôt. `KKIAPAY_SANDBOX=true` active le mode test; passez-le à `false` pour la production.

Configurez dans le tableau de bord KKiaPay le webhook `https://<votre-domaine>/api/webhooks/kkiapay` et utilisez le même secret hash que `KKIAPAY_WEBHOOK_SECRET`. Le webhook et le retour du widget déclenchent tous deux une vérification serveur de la transaction; seul un statut `SUCCESS` avec le bon montant, la bonne référence et le bon identifiant active le droit d’accès.

Les comptes étudiants et responsables doivent être provisionnés par l’établissement; aucun identifiant de démonstration n’est créé.

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
