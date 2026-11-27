<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\StudentLoginCodeMail;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentLoginChallenge;
use App\Models\StudentRegistry;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class StudentLoginCodeController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('login', [
            'matricule' => ['required', 'string', 'max:255'],
        ]);
        $matricule = Str::upper(trim($validated['matricule']));
        $registry = StudentRegistry::query()
            ->where('matricule', $matricule)
            ->first();

        $request->session()->forget([
            'student_login_challenge',
            'student_login_matricule',
            'student_login_attempts',
            'student_login_expires_at',
        ]);
        $token = Str::random(64);
        $rateLimitKey = 'student-login-code:'.hash('sha256', $matricule);
        $canSend = RateLimiter::attempt($rateLimitKey, 5, fn (): bool => true, 60);
        $expiresAt = now()->addMinutes(self::CODE_LIFETIME_MINUTES);

        $email = $registry?->email ? trim($registry->email) : null;

        if ($canSend && $registry && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = (string) random_int(100000, 999999);

            DB::transaction(function () use ($registry, $token, $code, $email, $expiresAt): void {
                StudentLoginChallenge::query()
                    ->where('student_registry_id', $registry->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);

                StudentLoginChallenge::query()->create([
                    'student_registry_id' => $registry->id,
                    'challenge_token_hash' => hash('sha256', $token),
                    'code_hash' => Hash::make($code),
                    'email_snapshot_hash' => hash('sha256', Str::lower($email)),
                    'expires_at' => $expiresAt,
                ]);
            });

            Mail::to($email)->send(new StudentLoginCodeMail(
                $code,
                $expiresAt->format('d/m/Y à H:i'),
            ));

        }

        $request->session()->put([
            'student_login_challenge' => $token,
            'student_login_matricule' => $matricule,
            'student_login_attempts' => 0,
            'student_login_expires_at' => $expiresAt->timestamp,
        ]);

        $status = 'Si ce matricule est enregistré et associé à une adresse e-mail, un code a été envoyé.';
        if (config('mail.default') === 'log') {
            $status = 'Le transport e-mail est en mode journalisation : aucun message ne sera livré. Si le matricule est reconnu, le message figure dans storage/logs/laravel.log. Configurez SMTP pour recevoir le code.';
        }

        return back()
            ->with('student-login-code-status', $status)
            ->withInput(['identifier' => $matricule]);
    }

    public function verify(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validateWithBag('login', [
            'verification_code' => ['required', 'digits:6'],
        ]);
        $token = $request->session()->get('student_login_challenge');
        $sessionExpiresAt = $request->session()->get('student_login_expires_at');

        if (! is_numeric($sessionExpiresAt) || now()->timestamp >= (int) $sessionExpiresAt) {
            $request->session()->forget([
                'student_login_challenge',
                'student_login_matricule',
                'student_login_attempts',
                'student_login_expires_at',
            ]);

            return back()->withErrors([
                'verification_code' => 'Ce code est invalide ou a expiré. Demandez un nouveau code.',
            ], 'login');
        }

        if (! is_string($token)) {
            return back()->withErrors([
                'verification_code' => 'Ce code est invalide ou a expiré. Demandez un nouveau code.',
            ], 'login');
        }

        $result = DB::transaction(function () use ($token, $validated, $activityLogger, $request): array {
            $challenge = StudentLoginChallenge::query()
                ->where('challenge_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (
                ! $challenge
            ) {
                $attempts = (int) $request->session()->get('student_login_attempts', 0) + 1;
                $request->session()->put('student_login_attempts', $attempts);

                return ['user' => null, 'retryable' => $attempts < self::MAX_ATTEMPTS];
            }

            if (
                $challenge->consumed_at !== null
                || $challenge->expires_at->isPast()
                || $challenge->attempts >= self::MAX_ATTEMPTS
            ) {
                return ['user' => null, 'retryable' => false];
            }

            if (! Hash::check($validated['verification_code'], $challenge->code_hash)) {
                $challenge->attempts++;
                if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                    $challenge->consumed_at = now();
                }
                $challenge->save();

                return ['user' => null, 'retryable' => $challenge->consumed_at === null];
            }

            $registry = StudentRegistry::query()
                ->lockForUpdate()
                ->find($challenge->student_registry_id);

            if (
                ! $registry
                || ! $registry->email
                || hash('sha256', Str::lower(trim($registry->email))) !== $challenge->email_snapshot_hash
            ) {
                $challenge->update(['consumed_at' => now()]);

                return ['user' => null, 'retryable' => false];
            }

            $user = $registry->user_id
                ? User::query()->lockForUpdate()->find($registry->user_id)
                : User::query()->where('matricule', $registry->matricule)->lockForUpdate()->first();

            if ($user && (
                $user->role !== 'student'
                || $user->account_status !== 'active'
                || $user->matricule !== $registry->matricule
            )) {
                $challenge->update(['consumed_at' => now()]);

                return ['user' => null, 'retryable' => false];
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => trim($registry->first_name.' '.$registry->last_name),
                    'first_name' => $registry->first_name,
                    'last_name' => $registry->last_name,
                    'email' => null,
                    'matricule' => $registry->matricule,
                    'role' => 'student',
                    'account_status' => 'active',
                    'password' => Str::random(64),
                ]);
            } else {
                $user->update([
                    'name' => trim($registry->first_name.' '.$registry->last_name),
                    'first_name' => $registry->first_name,
                    'last_name' => $registry->last_name,
                ]);
            }

            if ($roleId = Role::query()->where('slug', 'student')->value('id')) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }

            $student = Student::query()->where('user_id', $user->id)->first();
            Student::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matricule' => $registry->matricule,
                    'filiere_id' => $registry->filiere_id,
                    'study_level_id' => $registry->study_level_id,
                    'defense_year_id' => $registry->defense_year_id,
                    'enrollment_status' => $registry->academic_status,
                    'institutionally_verified_at' => $student?->institutionally_verified_at ?? now(),
                    'defended_at' => $registry->defended_at,
                ],
            );
            $registry->update(['user_id' => $user->id]);
            $challenge->update(['consumed_at' => now()]);
            $activityLogger->record($user, 'auth.student_otp_login', $user, [
                'method' => 'email_code',
            ]);

            return ['user' => $user, 'retryable' => false];
        });

        if (! $result['user']) {
            if (! $result['retryable']) {
                $request->session()->forget([
                    'student_login_challenge',
                    'student_login_matricule',
                    'student_login_attempts',
                    'student_login_expires_at',
                ]);
            }

            return back()->withErrors([
                'verification_code' => 'Ce code est invalide ou a expiré. Demandez un nouveau code.',
            ], 'login');
        }

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->forget([
            'student_login_challenge',
            'student_login_matricule',
            'student_login_attempts',
            'student_login_expires_at',
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
