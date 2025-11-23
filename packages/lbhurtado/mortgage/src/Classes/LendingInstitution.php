<?php

namespace LBHurtado\Mortgage\Classes;

use App\Models\LendingInstitution as LendingInstitutionModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use LBHurtado\Mortgage\Services\AgeService;
use LBHurtado\Mortgage\ValueObjects\Percent;

class LendingInstitution
{
    protected string $key;

    protected int $offset;

    protected ?LendingInstitutionModel $model = null;

    public function __construct(?string $key = null)
    {
        $key ??= config('mortgage.default_lending_institution', 'hdmf');

        if (! in_array($key, self::keys())) {
            throw new InvalidArgumentException("Invalid lending institution key: {$key}");
        }

        $this->key = $key;
        $this->loadModel();
    }

    public static function keys(): array
    {
        // Get active institution codes from database, with config fallback
        return Cache::remember('lending_institution_keys', 3600, function () {
            $codes = LendingInstitutionModel::where('is_active', true)
                ->pluck('code')
                ->toArray();

            return ! empty($codes) ? $codes : ['hdmf', 'rcbc', 'cbc'];
        });
    }

    public function key(): string
    {
        return $this->key;
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return $this->getValue($path, $default);
    }

    protected function loadModel(): void
    {
        // Cache the model for 1 hour
        $this->model = Cache::remember(
            "lending_institution_{$this->key}",
            3600,
            fn () => LendingInstitutionModel::where('code', $this->key)
                ->where('is_active', true)
                ->first()
        );

        if (! $this->model) {
            throw new InvalidArgumentException("Lending institution not found or inactive: {$this->key}");
        }
    }

    protected function getValue(string $property, mixed $default = null): mixed
    {
        if ($this->model === null) {
            // Fallback to config if model not loaded
            return config("mortgage.lending_institutions.{$this->key}.{$property}", $default);
        }

        return $this->model->{$property} ?? $default;
    }

    public function name(): string
    {
        return $this->getValue('name');
    }

    public function alias(): string
    {
        return $this->getValue('alias');
    }

    public function type(): string
    {
        return $this->getValue('type');
    }

    public function minimumAge(): int
    {
        return $this->getValue('borrowing_age_minimum');
    }

    public function maximumAge(): int
    {
        return $this->getValue('borrowing_age_maximum');
    }

    public function offset(): int
    {
        return $this->offset ?? $this->getValue('borrowing_age_offset', 0);
    }

    public function newOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function maximumTerm(): int
    {
        return $this->getValue('maximum_term');
    }

    public function maximumPayingAge(): int
    {
        return $this->getValue('maximum_paying_age');
    }

    public function maxAllowedTerm(Carbon $birthdate, ?int $overridePayingAge = null): int
    {
        $age = app(AgeService::class)->getAgeInFloat($birthdate);
        $limit = ($overridePayingAge ?? $this->maximumPayingAge()) + $this->offset();

        return min((int) floor($limit - $age), $this->maximumTerm());
    }

    public function getRequiredBufferMargin(): ?float
    {
        return $this->getValue('buffer_margin');
    }

    public function getIncomeRequirementMultiplier(): ?Percent
    {
        return Percent::ofFraction($this->getValue('income_requirement_multiplier'));
    }

    public function getInterestRate(): ?Percent
    {
        return Percent::ofFraction($this->getValue('interest_rate'));
    }

    public function getPercentDownPayment(): Percent
    {
        $default = $this->getValue('percent_dp') ?? 0.0;

        return Percent::ofFraction($default);
    }

    public function getLoanableValueMultiplier(): ?float
    {
        return $this->getValue('loanable_value_multiplier');
    }

    public function getPercentMiscellaneousFees(): Percent
    {
        $default = $this->getValue('percent_mf') ?? 0.0;

        return Percent::ofFraction($default);
    }

    public function getBufferMargin(): Percent
    {
        $value = $this->getValue('buffer_margin') ?? 0.0;

        return Percent::ofFraction($value);
    }
}
