<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Rule Engine Service - JSON-based product selection rules
 * 
 * Loads rules from JSON file and applies them to product ranking.
 */
class RuleEngineService
{
    public function __construct(
        protected RuleEvaluator $evaluator
    ) {}

    /**
     * Apply rules to product collection and return sorted products.
     *
     * @param Collection $products Scored products
     * @param array $buyerProfile Buyer data (age, income, etc.)
     * @return Collection Sorted products based on matching rules
     */
    public function applyRules(Collection $products, array $buyerProfile): Collection
    {
        Log::info('RuleEngineService: Starting rule evaluation', [
            'buyer_profile' => $buyerProfile,
            'products_count' => $products->count(),
        ]);

        $rules = $this->loadRules();

        Log::info('RuleEngineService: Loaded rules', [
            'rules_count' => $rules->count(),
            'rules' => $rules->toArray(),
        ]);

        if ($rules->isEmpty()) {
            Log::warning('RuleEngineService: No rules found, falling back to simple sort');
            return $this->fallbackSort($products);
        }

        // Find first matching rule
        $matchedRule = $this->findMatchingRule($rules, $buyerProfile);

        Log::info('RuleEngineService: Rule matching result', [
            'matched_rule' => $matchedRule,
        ]);

        if (!$matchedRule) {
            Log::warning('RuleEngineService: No rule matched, falling back');
            return $this->fallbackSort($products);
        }

        Log::info('RuleEngineService: Applying rule action', [
            'rule_name' => $matchedRule['name'] ?? 'unknown',
            'action' => $matchedRule['action'] ?? [],
        ]);

        // Apply the matched rule's action
        return $this->applyAction($products, $matchedRule['action'] ?? [], $buyerProfile);
    }

    /**
     * Load rules from JSON file.
     */
    protected function loadRules(): Collection
    {
        $rulesFile = config('mortgage.product_selection.rules_file');
        $cacheEnabled = config('mortgage.product_selection.cache_rules', true);

        if ($cacheEnabled) {
            return Cache::remember('product_selection_rules', 3600, function () use ($rulesFile) {
                return $this->parseRulesFile($rulesFile);
            });
        }

        return $this->parseRulesFile($rulesFile);
    }

    /**
     * Parse rules from JSON file.
     */
    protected function parseRulesFile(?string $rulesFile): Collection
    {
        if (!$rulesFile || !File::exists($rulesFile)) {
            Log::warning('Product selection rules file not found', ['file' => $rulesFile]);
            return collect();
        }

        try {
            $json = File::get($rulesFile);
            $rules = json_decode($json, true);

            if (!is_array($rules)) {
                Log::error('Invalid rules file format', ['file' => $rulesFile]);
                return collect();
            }

            // Filter active rules and sort by priority (highest first)
            return collect($rules)
                ->where('active', true)
                ->sortByDesc('priority')
                ->values();

        } catch (\Exception $e) {
            Log::error('Failed to load product selection rules', [
                'file' => $rulesFile,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Find the first matching rule for buyer profile.
     */
    protected function findMatchingRule(Collection $rules, array $buyerProfile): ?array
    {
        foreach ($rules as $rule) {
            $conditions = $rule['condition'] ?? [];

            if ($this->evaluator->evaluate($conditions, $buyerProfile)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Apply rule action to products.
     */
    protected function applyAction(Collection $products, array $action, array $buyerProfile): Collection
    {
        // Prefer institution (filter first)
        if (isset($action['prefer_institution'])) {
            $preferred = $products->where('lending_institution', $action['prefer_institution']);
            if ($preferred->isNotEmpty()) {
                $products = $preferred;
            }
        }

        // Apply boost multiplier to scores
        if (isset($action['boost_multiplier'])) {
            $multiplier = $action['boost_multiplier'];
            $products = $products->map(function ($product) use ($multiplier) {
                $product['affordability_score'] *= $multiplier;
                return $product;
            });
        }

        // Sort by specified field and direction
        $sortBy = $action['sort_by'] ?? 'monthly_payment';
        $direction = $action['direction'] ?? 'asc';

        return $products->sortBy(function ($product) use ($sortBy) {
            return match ($sortBy) {
                'price' => $product['price'],
                'monthly_payment' => $product['monthly_payment'],
                'affordability_score' => $product['affordability_score'],
                'loan_term' => $product['loan_term_years'],
                default => $product['monthly_payment'],
            };
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Fallback sorting when no rules match.
     */
    protected function fallbackSort(Collection $products): Collection
    {
        $preference = config('mortgage.product_selection.preference', 'cheapest');

        return $products->sortBy([
            fn($a, $b) => $b['qualifies'] <=> $a['qualifies'], // Qualified first
            fn($a, $b) => $preference === 'most_expensive'
                ? $b['monthly_payment'] <=> $a['monthly_payment']
                : $a['monthly_payment'] <=> $b['monthly_payment'],
        ])->values();
    }
}
