<?php

namespace LBHurtado\Mortgage\Exceptions;

use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;

class ComputationFailedException extends MortgageException
{
    protected ?MortgageInputsData $inputs = null;

    public function __construct(string $message, ?MortgageInputsData $inputs = null, int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->inputs = $inputs;
    }

    public function getUserMessage(): string
    {
        return 'Unable to compute mortgage details. Please check your inputs and try again.';
    }

    public function getContext(): array
    {
        return [
            'inputs' => $this->inputs?->toArray(),
            'error' => $this->getMessage(),
        ];
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
