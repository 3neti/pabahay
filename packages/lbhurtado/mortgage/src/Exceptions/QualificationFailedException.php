<?php

namespace LBHurtado\Mortgage\Exceptions;

class QualificationFailedException extends MortgageException
{
    protected string $reason;

    protected array $remedies;

    public function __construct(string $reason, array $remedies = [], int $code = 400, ?\Throwable $previous = null)
    {
        $message = "Loan qualification failed: {$reason}";
        parent::__construct($message, $code, $previous);

        $this->reason = $reason;
        $this->remedies = $remedies;
    }

    public function getUserMessage(): string
    {
        $message = "Your loan application does not qualify. Reason: {$this->reason}";

        if (! empty($this->remedies)) {
            $remedyList = collect($this->remedies)
                ->pluck('description')
                ->implode(', ');

            $message .= " Suggested actions: {$remedyList}";
        }

        return $message;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getRemedies(): array
    {
        return $this->remedies;
    }

    public function getContext(): array
    {
        return [
            'reason' => $this->reason,
            'remedies' => $this->remedies,
        ];
    }
}
