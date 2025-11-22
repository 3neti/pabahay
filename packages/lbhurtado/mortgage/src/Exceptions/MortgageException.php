<?php

namespace LBHurtado\Mortgage\Exceptions;

use Exception;

class MortgageException extends Exception
{
    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * Get error context for logging.
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * Check if exception is retryable.
     */
    public function isRetryable(): bool
    {
        return false;
    }
}
