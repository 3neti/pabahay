<?php

namespace LBHurtado\Mortgage\Factories;

use LBHurtado\Mortgage\Attributes\ExtractorFor;
use LBHurtado\Mortgage\Data\Inputs\MortgageParticulars;
use LBHurtado\Mortgage\Enums\ExtractorType;
use LBHurtado\Mortgage\Extractors\BaseExtractor;
use LBHurtado\Mortgage\Extractors\IncomeRequirementMultiplierExtractor;
use LBHurtado\Mortgage\Extractors\InterestRateExtractor;
use LBHurtado\Mortgage\Extractors\LendingInstitutionExtractor;
use LBHurtado\Mortgage\Extractors\PercentDownPaymentExtractor;
use LBHurtado\Mortgage\Extractors\PercentMiscellaneousFeesExtractor;
use LBHurtado\Mortgage\Extractors\ProcessingFeeExtractor;
use LBHurtado\Mortgage\Extractors\TotalContractPriceExtractor;
use ReflectionClass;
use RuntimeException;

final class ExtractorFactory
{
    /**
     * A cached map of ExtractorType string values to their extractor class strings.
     *
     * @var array<string, class-string<BaseExtractor>>
     */
    protected static array $map = [];

    /**
     * Build and return the extractor based on type.
     *
     * @throws \ReflectionException
     */
    public static function make(ExtractorType $type, MortgageParticulars $inputs): BaseExtractor
    {
        self::discoverExtractors();

        if (! array_key_exists($type->value, self::$map)) {
            throw new RuntimeException("No extractor found for type: {$type->value}");
        }

        $class = self::$map[$type->value];

        return $class::fromInputs($inputs);
    }

    /**
     * Discovers extractor classes and caches their mappings.
     *
     * @throws \ReflectionException
     *
     * @todo auto-discovery via filesystem scan
     */
    protected static function discoverExtractors(): void
    {
        if (! empty(self::$map)) {
            return; // Already discovered
        }

        $classes = [
            IncomeRequirementMultiplierExtractor::class,
            LendingInstitutionExtractor::class,
            InterestRateExtractor::class,
            TotalContractPriceExtractor::class,
            PercentDownPaymentExtractor::class,
            PercentMiscellaneousFeesExtractor::class,
            ProcessingFeeExtractor::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(ExtractorFor::class);

            foreach ($attributes as $attribute) {
                /** @var ExtractorFor $instance */
                $instance = $attribute->newInstance();

                self::$map[$instance->type->value] = $class;
            }
        }
    }
}
