<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LendingInstitution extends Model
{
    protected $fillable = [
        'code',
        'name',
        'alias',
        'type',
        'is_active',
        'interest_rate',
        'percent_dp',
        'percent_mf',
        'processing_fee',
        'default_add_mri',
        'default_add_fi',
        'borrowing_age_minimum',
        'borrowing_age_maximum',
        'borrowing_age_offset',
        'maximum_term',
        'maximum_paying_age',
        'buffer_margin',
        'income_requirement_multiplier',
        'loanable_value_multiplier',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'interest_rate' => 'decimal:6',
        'percent_dp' => 'decimal:6',
        'percent_mf' => 'decimal:6',
        'processing_fee' => 'decimal:2',
        'default_add_mri' => 'boolean',
        'default_add_fi' => 'boolean',
        'buffer_margin' => 'decimal:6',
        'income_requirement_multiplier' => 'decimal:6',
        'loanable_value_multiplier' => 'decimal:6',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(\LBHurtado\Mortgage\Models\Product::class, 'lending_institution', 'code');
    }
}
