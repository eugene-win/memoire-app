<?php

namespace App\Services;

use App\Models\User;

class DepositEligibility
{
    public function ensureEligible(User $student): void
    {
        abort_unless(
            $student->hasRole('student')
                && $student->account_status === 'active',
            403
        );

        $profile = $student->student()->with(['filiere', 'studyLevel'])->first();
        abort_unless(
            $profile !== null
                && $profile->filiere?->is_active === true
                && $profile->studyLevel?->code === 'L3'
                && $profile->studyLevel->is_active === true
                && $profile->enrollment_status === 'enrolled'
                && $profile->institutionally_verified_at !== null
                && $profile->defended_at !== null,
            403
        );

        $authorization = $student->depositAuthorization;
        abort_unless(
            $student->deposit_authorized
                && ($authorization === null
                    || ($authorization->status === 'active'
                        && ($authorization->expires_at === null || $authorization->expires_at->isFuture()))),
            403
        );
    }
}
