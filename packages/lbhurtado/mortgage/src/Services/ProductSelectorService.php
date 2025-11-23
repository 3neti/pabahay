<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Collection;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Factories\MortgageParticularsFactory;
use LBHurtado\Mortgage\Data\MortgageComputationData;

/**
 * Product Selection Service - MVP Version
 * 
 * Simple affordability-based product selection.
 * Selects the cheapest product the buyer can afford.
 */
class ProductSelectorService
{
    public function __construct(
        protected MortgageComputationService $computationService,
        protected RuleEngineService $ruleEngine
    ) {}

    /**
     * Select the best product for a buyer based on affordability.
     * 
     * @param int $age Buyer's age
     * @param float $monthlyGrossIncome Buyer's monthly gross income
     * @param Collection $products Available products
     * @return array|null Selected product with reasoning, or null if none affordable
     */
    public function selectBestProduct(
        int $age,
        float $monthlyGrossIncome,
        Collection $products
    ): ?array {
        $rankedProducts = $this->rankProducts($age, $monthlyGrossIncome, $products);
        
        return $rankedProducts->first();
    }

    /**
     * Rank all products by affordability and monthly payment.
     * 
     * @param int $age Buyer's age
     * @param float $monthlyGrossIncome Buyer's monthly gross income
     * @param Collection $products Available products
     * @return Collection Ranked products with scores and reasoning
     */
    public function rankProducts(
        int $age,
        float $monthlyGrossIncome,
        Collection $products
    ): Collection {
        $scoredProducts = $products->map(function ($product) use ($age, $monthlyGrossIncome) {
            return $this->scoreProduct($product, $age, $monthlyGrossIncome);
        })->filter(); // Remove null values (unaffordable products)

        // Filter to qualified products only
        $qualifiedProducts = $scoredProducts->where('qualifies', true);
        
        if ($qualifiedProducts->isEmpty()) {
            // No qualified products - return all sorted by affordability
            return $scoredProducts->sortByDesc('affordability_score')->values();
        }

        // Check if rules engine is enabled
        $engine = config('mortgage.product_selection.engine', 'rules');
        
        \Log::info('ProductSelectorService: Engine selection', [
            'engine' => $engine,
            'qualified_products_count' => $qualifiedProducts->count(),
        ]);
        
        if ($engine === 'rules') {
            // Use JSON rules engine
            $buyerProfile = [
                'age' => $age,
                'monthly_gross_income' => $monthlyGrossIncome,
            ];
            
            \Log::info('ProductSelectorService: Calling rules engine', [
                'buyer_profile' => $buyerProfile,
            ]);
            
            return $this->ruleEngine->applyRules($qualifiedProducts, $buyerProfile);
        }
        
        // Fallback to simple preference-based sorting
        $preference = config('mortgage.product_selection.preference', 'cheapest');
        
        return $qualifiedProducts->sortBy(
            fn($a, $b) => $preference === 'most_expensive' 
                ? $b['monthly_payment'] <=> $a['monthly_payment']
                : $a['monthly_payment'] <=> $b['monthly_payment']
        )->values();
    }

    /**
     * Score a single product for a buyer.
     * 
     * @param object $product Product with id, name, lending_institution, price
     * @param int $age Buyer's age
     * @param float $monthlyGrossIncome Buyer's monthly gross income
     * @return array|null Product score details, or null if computation fails
     */
    protected function scoreProduct($product, int $age, float $monthlyGrossIncome): ?array
    {
        try {
            // Compute mortgage for this product
            $inputs = MortgageInputsData::from([
                'lending_institution' => $product->lending_institution,
                'total_contract_price' => $product->price,
                'age' => $age,
                'monthly_gross_income' => $monthlyGrossIncome,
                'co_borrower_age' => null,
                'co_borrower_income' => null,
                'additional_income' => null,
                'balance_payment_interest' => null, // Use default
                'percent_down_payment' => null, // Use default
                'percent_miscellaneous_fee' => null, // Use default
                'processing_fee' => null,
                'add_mri' => false,
                'add_fi' => false,
                'desired_loan_term' => null,
            ]);

            $computation = $this->computationService->compute($inputs);

            // Calculate affordability score (0-100)
            // Higher score = more affordable
            $disposableIncome = $computation->monthly_disposable_income->getAmount()->toFloat();
            $monthlyPayment = $computation->monthly_amortization->getAmount()->toFloat();
            
            if ($disposableIncome <= 0) {
                return null; // Cannot afford
            }

            $paymentRatio = $monthlyPayment / $disposableIncome;
            $affordabilityScore = max(0, 100 - ($paymentRatio * 100));

            // Determine reasoning
            $reasoning = $this->generateReasoning($computation, $affordabilityScore, $product);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'lending_institution' => $product->lending_institution,
                'price' => $product->price,
                'monthly_payment' => $monthlyPayment,
                'qualifies' => $computation->qualifies,
                'affordability_score' => round($affordabilityScore, 2),
                'required_equity' => $computation->required_equity->getAmount()->toFloat(),
                'down_payment' => $computation->total_contract_price->base()->getAmount()->toFloat() * $computation->percent_down_payment->value(),
                'loan_term_years' => $computation->balance_payment_term,
                'reasoning' => $reasoning,
            ];

        } catch (\Exception $e) {
            // Skip products that fail computation
            return null;
        }
    }

    /**
     * Generate human-readable reasoning for product selection.
     */
    protected function generateReasoning(MortgageComputationData $computation, float $score, $product): string
    {
        if (!$computation->qualifies) {
            $incomeGap = $computation->income_gap->getAmount()->toFloat();
            return "Not qualified - Income gap: ₱" . number_format($incomeGap, 2);
        }

        if ($score >= 80) {
            return "Excellent fit - Well within your budget";
        } elseif ($score >= 60) {
            return "Good fit - Affordable monthly payment";
        } elseif ($score >= 40) {
            return "Manageable - Within budget but tight";
        } else {
            return "Challenging - High payment relative to income";
        }
    }

    /**
     * Get the top N products.
     */
    public function getTopProducts(
        int $age,
        float $monthlyGrossIncome,
        Collection $products,
        int $limit = 3
    ): Collection {
        return $this->rankProducts($age, $monthlyGrossIncome, $products)->take($limit);
    }
}
