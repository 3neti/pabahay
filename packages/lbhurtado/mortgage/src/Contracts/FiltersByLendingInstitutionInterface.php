<?php

namespace LBHurtado\Mortgage\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FiltersByLendingInstitutionInterface
{
    /**
     * Define a query scope to explicitly filter by lending institution.
     */
    public function scopeForLendingInstitution(Builder $query, string|array|null $lendingInstitution): Builder;
}
