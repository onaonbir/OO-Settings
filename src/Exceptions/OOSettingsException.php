<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Exceptions;

use Exception;

/**
 * Base exception for OOSettings package.
 *
 * All package-specific exceptions should extend this class
 * to provide consistent error handling and logging.
 */
class OOSettingsException extends Exception
{
    /**
     * Additional context data for the exception.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new OOSettings exception.
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array<string, mixed>  $context  Additional context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context.
     *
     * @return array<string, mixed> Context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context for the exception.
     *
     * @param  array<string, mixed>  $context  Context data
     */
    public function withContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Convert exception to array for logging.
     *
     * @return array<string, mixed> Exception data
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];
    }
}
