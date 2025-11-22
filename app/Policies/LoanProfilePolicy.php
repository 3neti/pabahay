<?php

namespace App\Policies;

use LBHurtado\Mortgage\Models\LoanProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LoanProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view loan profiles
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LoanProfile $loanProfile): bool
    {
        // All authenticated users can view individual loan profiles
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Loan profiles are created via API, not admin panel
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LoanProfile $loanProfile): bool
    {
        // Only admins can update loan profiles (borrower info)
        return $user->is_admin ?? false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LoanProfile $loanProfile): bool
    {
        // Only admins can delete loan profiles
        return $user->is_admin ?? false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LoanProfile $loanProfile): bool
    {
        return $user->is_admin ?? false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LoanProfile $loanProfile): bool
    {
        // Only super admins can force delete
        return $user->is_super_admin ?? false;
    }
}
