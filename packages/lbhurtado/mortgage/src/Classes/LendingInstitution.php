<?php

namespace LBHurtado\Mortgage\Classes;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use LBHurtado\Mortgage\Services\AgeService;
use LBHurtado\Mortgage\ValueObjects\Percent;
use Spatie\LaravelSettings\Settings;

class LendingInstitution
{
    protected string $key;

    protected int $offset;
    
    protected ?Settings $settings = null;

    public function __construct(?string $key = null)
    {
        $key ??= config('mortgage.default_lending_institution', 'hdmf');

        if (! in_array($key, self::keys())) {
            throw new InvalidArgumentException("Invalid lending institution key: {$key}");
        }

        $this->key = $key;
        $this->loadSettings();
    }

    public static function keys(): array
    {
        return ['hdmf', 'rcbc', 'cbc'];
    }

    public function key(): string
    {
        return $this->key;
    }
    
    public function get(string $path, mixed $default = null): mixed
    {
        // Support dot notation for nested config values
        return $this->getSettingsValue($path, $default);
    }
    
    protected function loadSettings(): void
    {
        $this->settings = match($this->key) {
            'hdmf' => app(\App\Settings\HdmfSettings::class),
            'rcbc' => app(\App\Settings\RcbcSettings::class),
            'cbc' => app(\App\Settings\CbcSettings::class),
            default => null,
        };
    }
    
    protected function getSettingsValue(string $property, mixed $default = null): mixed
    {
        if ($this->settings === null) {
            // Fallback to config if settings not loaded
            return config("mortgage.lending_institutions.{$this->key}.{$property}", $default);
        }
        
        return $this->settings->{$property} ?? $default;
    }
    
    public function name(): string
    {
        return $this->getSettingsValue('name');
    }

    public function alias(): string
    {
        return $this->getSettingsValue('alias');
    }

    public function type(): string
    {
        return $this->getSettingsValue('type');
    }

    public function minimumAge(): int
    {
        return $this->getSettingsValue('borrowing_age_minimum');
    }

    public function maximumAge(): int
    {
        return $this->getSettingsValue('borrowing_age_maximum');
    }

    public function offset(): int
    {
        return $this->offset ?? $this->getSettingsValue('borrowing_age_offset', 0);
    }

    public function newOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function maximumTerm(): int
    {
        return $this->getSettingsValue('maximum_term');
    }

    public function maximumPayingAge(): int
    {
        return $this->getSettingsValue('maximum_paying_age');
    }

    public function maxAllowedTerm(Carbon $birthdate, ?int $overridePayingAge = null): int
    {
        $age = app(AgeService::class)->getAgeInFloat($birthdate);
        $limit = ($overridePayingAge ?? $this->maximumPayingAge()) + $this->offset();

        return min((int) floor($limit - $age), $this->maximumTerm());
    }

    public function getRequiredBufferMargin(): ?float
    {
        return $this->getSettingsValue('buffer_margin');
    }

    public function getIncomeRequirementMultiplier(): ?Percent
    {
        return Percent::ofFraction($this->getSettingsValue('income_requirement_multiplier'));
    }

    public function getInterestRate(): ?Percent
    {
        return Percent::ofFraction($this->getSettingsValue('interest_rate'));
    }

    public function getPercentDownPayment(): Percent
    {
        $default = $this->getSettingsValue('percent_dp') ?? 0.0;

        return Percent::ofFraction($default);
    }

    public function getLoanableValueMultiplier(): ?float
    {
        return $this->getSettingsValue('loanable_value_multiplier');
    }

    public function getPercentMiscellaneousFees(): Percent
    {
        $default = $this->getSettingsValue('percent_mf') ?? 0.0;

        return Percent::ofFraction($default);
    }

    public function getBufferMargin(): Percent
    {
        $value = $this->getSettingsValue('buffer_margin') ?? 0.0;

        return Percent::ofFraction($value);
    }
}
