<?php

namespace LBHurtado\Mortgage\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use LBHurtado\Mortgage\Models\LoanProfile;
use LBHurtado\Mortgage\Repositories\Contracts\LoanProfileRepositoryInterface;

class EloquentLoanProfileRepository implements LoanProfileRepositoryInterface
{
    public function find(string $id): ?LoanProfile
    {
        return LoanProfile::find($id);
    }

    public function findByReferenceCode(string $referenceCode): ?LoanProfile
    {
        return LoanProfile::where('reference_code', $referenceCode)->first();
    }

    public function create(array $data): LoanProfile
    {
        return LoanProfile::create($data);
    }

    public function update(string $id, array $data): LoanProfile
    {
        $profile = $this->find($id);
        $profile->update($data);

        return $profile->fresh();
    }

    public function delete(string $id): bool
    {
        return LoanProfile::destroy($id) > 0;
    }

    public function getQualified(int $limit = 10): Collection
    {
        return LoanProfile::where('qualified', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByInstitution(string $institution, int $limit = 10): Collection
    {
        return LoanProfile::where('lending_institution', $institution)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getReserved(): Collection
    {
        return LoanProfile::whereNotNull('reserved_at')->get();
    }

    public function getExpiredReservations(): Collection
    {
        return LoanProfile::whereNotNull('reserved_at')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->get();
    }
}
