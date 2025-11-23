<?php

namespace LBHurtado\Mortgage\Services;

/**
 * Rule Evaluator - Evaluates JSON conditions against buyer profile
 * 
 * Supports operators: =, !=, >, <, >=, <=, between, in
 */
class RuleEvaluator
{
    /**
     * Evaluate if a rule's conditions match the buyer profile.
     *
     * @param array $conditions Rule conditions from JSON
     * @param array $buyerProfile Buyer data (age, income, etc.)
     * @return bool True if all conditions match
     */
    public function evaluate(array $conditions, array $buyerProfile): bool
    {
        // Empty condition = always match (default rule)
        if (empty($conditions)) {
            return true;
        }

        // All conditions must match (implicit AND)
        foreach ($conditions as $field => $condition) {
            if (!$this->evaluateCondition($field, $condition, $buyerProfile)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition.
     */
    protected function evaluateCondition(string $field, mixed $condition, array $buyerProfile): bool
    {
        // Get value from buyer profile
        $actualValue = $buyerProfile[$field] ?? null;

        if ($actualValue === null) {
            return false; // Field not present in profile
        }

        // Simple value comparison (implicit equals)
        if (!is_array($condition)) {
            return $actualValue == $condition;
        }

        // Operator-based comparison
        if (isset($condition['operator']) && isset($condition['value'])) {
            return $this->compareWithOperator(
                $actualValue,
                $condition['operator'],
                $condition['value']
            );
        }

        // Shorthand: {">=": 100000}
        foreach ($condition as $operator => $value) {
            if (!$this->compareWithOperator($actualValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare values using an operator.
     */
    protected function compareWithOperator(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '<' => $actual < $expected,
            '>=' => $actual >= $expected,
            '<=' => $actual <= $expected,
            'between' => is_array($expected) && $actual >= $expected[0] && $actual <= $expected[1],
            'not_between' => is_array($expected) && ($actual < $expected[0] || $actual > $expected[1]),
            'in' => is_array($expected) && in_array($actual, $expected),
            'not_in' => is_array($expected) && !in_array($actual, $expected),
            default => false,
        };
    }
}
