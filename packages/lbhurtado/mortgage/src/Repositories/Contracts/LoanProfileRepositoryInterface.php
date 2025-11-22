<?php

namespace LBHurtado\Mortgage\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use LBHurtado\Mortgage\Models\LoanProfile;

interface LoanProfileRepositoryInterface
{
    /**
     * Find loan profile by ID.
     */
    public function find(string $id): ?LoanProfile;

    /**
     * Find loan profile by reference code.
     */
    public function findByReferenceCode(string $referenceCode): ?LoanProfile;

    /**
     * Create new loan profile.
     */
    public function create(array $data): LoanProfile;

    /**
     * Update loan profile.
     */
    public function update(string $id, array $data): LoanProfile;

    /**
     * Delete loan profile.
     */
    public function delete(string $id): bool;

    /**
     * Get qualified loan profiles.
     */
    public function getQualified(int $limit = 10): Collection;

    /**
     * Get profiles by lending institution.
     */
    public function getByInstitution(string $institution, int $limit = 10): Collection;

    /**
     * Get reserved profiles.
     */
    public function getReserved(): Collection;

    /**
     * Get expired reservations.
     */
    public function getExpiredReservations(): Collection;
}
