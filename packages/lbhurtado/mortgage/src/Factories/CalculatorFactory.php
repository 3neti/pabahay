<?php

namespace LBHurtado\Mortgage\Factories;

use LBHurtado\Mortgage\Attributes\CalculatorFor;
use LBHurtado\Mortgage\Calculators\BalancePaymentTermCalculator;
use LBHurtado\Mortgage\Calculators\BaseCalculator;
use LBHurtado\Mortgage\Calculators\CashOutCalculator;
use LBHurtado\Mortgage\Calculators\EquityRequirementCalculator;
use LBHurtado\Mortgage\Calculators\FeesCalculator;
use LBHurtado\Mortgage\Calculators\IncomeGapCalculator;
use LBHurtado\Mortgage\Calculators\IncomeRequirementCalculator;
use LBHurtado\Mortgage\Calculators\LoanAmountCalculator;
use LBHurtado\Mortgage\Calculators\LoanQualificationCalculator;
use LBHurtado\Mortgage\Calculators\MiscellaneousFeeCalculator;
use LBHurtado\Mortgage\Calculators\MonthlyAmortizationCalculator;
use LBHurtado\Mortgage\Calculators\MonthlyDisposableIncomeCalculator;
use LBHurtado\Mortgage\Calculators\PresentValueCalculator;
use LBHurtado\Mortgage\Calculators\RequiredIncomeCalculator;
use LBHurtado\Mortgage\Calculators\RequiredPercentDownPaymentCalculator;
use LBHurtado\Mortgage\Data\Inputs\MortgageParticulars;
use LBHurtado\Mortgage\Enums\CalculatorType;
use ReflectionClass;
use RuntimeException;

final class CalculatorFactory
{
    /**
     * A cached map of CalculatorType string values to their calculator class strings.
     *
     * @var array<string, class-string<BaseCalculator>>
     */
    protected static array $map = [];

    /**
     * Build and return the calculator based on type.
     *
     * @throws \ReflectionException
     */
    public static function make(CalculatorType $type, MortgageParticulars $inputs): BaseCalculator
    {
        self::discoverCalculators();

        if (! array_key_exists($type->value, self::$map)) {
            throw new RuntimeException("No calculator found for type: {$type->value}");
        }

        $class = self::$map[$type->value];

        return $class::fromInputs($inputs);
    }

    /**
     * Discovers calculator classes and caches their mappings.
     *
     * @throws \ReflectionException
     *
     * @todo auto-discovery via filesystem scan
     */
    protected static function discoverCalculators(): void
    {
        if (! empty(self::$map)) {
            return; // Already discovered
        }

        $classes = [
            MonthlyAmortizationCalculator::class,
            MonthlyDisposableIncomeCalculator::class,
            PresentValueCalculator::class,
            EquityRequirementCalculator::class,
            CashOutCalculator::class,
            LoanAmountCalculator::class,
            FeesCalculator::class,
            RequiredIncomeCalculator::class,
            IncomeGapCalculator::class,
            LoanQualificationCalculator::class,
            RequiredPercentDownPaymentCalculator::class,
            BalancePaymentTermCalculator::class,
            MiscellaneousFeeCalculator::class,
            IncomeRequirementCalculator::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(CalculatorFor::class);

            foreach ($attributes as $attribute) {
                /** @var CalculatorFor $instance */
                $instance = $attribute->newInstance();

                self::$map[$instance->type->value] = $class;
            }
        }
    }
}
