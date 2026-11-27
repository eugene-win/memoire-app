<?php

use App\Models\DepositAuthorization;
use App\Models\Filiere;
use App\Models\Student;
use App\Models\StudyLevel;
use App\Models\User;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function prepareStudentForDeposit(User $user, bool $authorized = true): void
{
    foreach ([
        ['code' => 'FIN-COMP', 'name' => 'Finance et comptabilité'],
        ['code' => 'GEST-ENT', 'name' => 'Gestion des entreprises'],
        ['code' => 'MARK-COM', 'name' => 'Marketing et commerce'],
        ['code' => 'INFO-GEST', 'name' => 'Informatique de gestion'],
        ['code' => 'ECO-APP', 'name' => 'Économie appliquée'],
    ] as $referenceFiliere) {
        Filiere::query()->firstOrCreate(
            ['code' => $referenceFiliere['code']],
            ['name' => $referenceFiliere['name'], 'is_active' => true],
        );
    }

    $level = StudyLevel::query()->firstOrCreate(
        ['code' => 'L3'],
        ['name' => 'Licence 3', 'is_active' => true],
    );
    $filiere = Filiere::query()->where('code', 'INFO-GEST')->firstOrFail();
    Student::query()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'matricule' => $user->matricule,
            'filiere_id' => $filiere->id,
            'study_level_id' => $level->id,
            'enrollment_status' => 'enrolled',
            'institutionally_verified_at' => now(),
            'defended_at' => now(),
        ],
    );
    $user->update(['deposit_authorized' => $authorized]);
    DepositAuthorization::query()->updateOrCreate(
        ['student_id' => $user->id],
        [
            'status' => $authorized ? 'active' : 'inactive',
            'authorized_at' => $authorized ? now() : null,
        ],
    );
}
