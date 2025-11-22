<?php

namespace LBHurtado\Mortgage\Exceptions;

class InvalidInputException extends MortgageException
{
    protected array $errors = [];

    public function __construct(string $message, array $errors = [], int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public static function forField(string $field, string $message): self
    {
        return new self("Invalid input for field: {$field}", [$field => [$message]]);
    }

    public function getUserMessage(): string
    {
        if (empty($this->errors)) {
            return $this->getMessage();
        }

        $messages = collect($this->errors)
            ->flatten()
            ->implode('; ');

        return "Validation failed: {$messages}";
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return ['errors' => $this->errors];
    }
}
