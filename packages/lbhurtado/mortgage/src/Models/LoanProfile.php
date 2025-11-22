<?php

namespace LBHurtado\Mortgage\Models;

// use FrittenKeeZ\Vouchers\Concerns\HasVouchers;
// use FrittenKeeZ\Vouchers\Facades\Vouchers;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LBHurtadp\Mortgage\Database\Factories\LoanProfileFactory;

class LoanProfile extends Model
{
    use HasFactory;
    use HasUuids;
    // use HasVouchers;

    protected $fillable = [
        'reference_code',
        'lending_institution',
        'total_contract_price',
        'inputs',
        'computation',
        'qualified',
        'required_equity',
        'income_gap',
        'suggested_down_payment_percent',
        'reason',
        'reserved_at',
        'borrower_name',
        'borrower_email',
    ];

    protected $casts = [
        'total_contract_price' => 'float',
        'inputs' => 'array',
        'computation' => 'array',
        'qualified' => 'boolean',
        'required_equity' => 'float',
        'income_gap' => 'float',
        'suggested_down_payment_percent' => 'float',
        'reason' => 'string',
        'reserved_at' => 'datetime',
    ];

    public static function newFactory(): LoanProfileFactory
    {
        return LoanProfileFactory::new();
    }

    public static function booted(): void
    {
        static::creating(function (LoanProfile $loanProfile) {
            // Generate simple reference code if not already set
            if (empty($loanProfile->reference_code)) {
                do {
                    $code = 'LP-'.strtoupper(Str::random(8));
                } while (self::where('reference_code', $code)->exists());
                
                $loanProfile->reference_code = $code;
            }
        });
    }

    // Accessors for email template convenience
    public function getQualificationAttribute()
    {
        return [
            'qualifies' => $this->qualified,
            'reason' => $this->reason,
        ];
    }

    public function getMonthlyAmortizationAttribute()
    {
        return $this->computation['monthly_amortization'] ?? 0;
    }

    public function getBalancePaymentTermAttribute()
    {
        return $this->computation['balance_payment_term'] ?? 0;
    }

    public function getLoanableAmountAttribute()
    {
        return $this->computation['loanable_amount'] ?? 0;
    }

    public function getInterestRateAttribute()
    {
        return $this->computation['interest_rate'] ?? 0;
    }

    public function getPercentDownPaymentAttribute()
    {
        return $this->computation['percent_down_payment'] ?? 0;
    }

    public function getMiscellaneousFeesAttribute()
    {
        return $this->computation['miscellaneous_fees'] ?? 0;
    }

    public function getCashOutAttribute()
    {
        return $this->computation['cash_out'] ?? 0;
    }

    public function getMonthlyDisposableIncomeAttribute()
    {
        return $this->computation['monthly_disposable_income'] ?? 0;
    }

    public function getPercentDownPaymentRemedyAttribute()
    {
        return $this->suggested_down_payment_percent;
    }

    public function getAgeAttribute()
    {
        return $this->inputs['age'] ?? null;
    }

    public function getMonthlyGrossIncomeAttribute()
    {
        return $this->inputs['monthly_gross_income'] ?? 0;
    }

    public function getCoBorrowerAgeAttribute()
    {
        return $this->inputs['co_borrower_age'] ?? null;
    }

    public function getCoBorrowerIncomeAttribute()
    {
        return $this->inputs['co_borrower_income'] ?? null;
    }
}
