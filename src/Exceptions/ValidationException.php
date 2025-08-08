<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Exceptions;

/**
 * Exception thrown when validation fails for multiple settings.
 *
 * This exception aggregates multiple validation errors and provides
 * detailed information about each failure.
 */
class ValidationException extends OOSettingsException
{
    /**
     * Create a new validation exception with multiple errors.
     *
     * @param  array<string, array<string>>  $errors  Validation errors by key
     */
    public static function withErrors(array $errors): static
    {
        $errorCount = array_sum(array_map('count', $errors));
        $message = "Validation failed for {$errorCount} setting(s)";

        return new static($message, 422, null, [
            'errors' => $errors,
            'error_count' => $errorCount,
        ]);
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getValidationErrors(): array
    {
        return $this->context['errors'] ?? [];
    }

    /**
     * Get error count.
     */
    public function getErrorCount(): int
    {
        return $this->context['error_count'] ?? 0;
    }

    /**
     * Check if a specific key has errors.
     */
    public function hasErrorsForKey(string $key): bool
    {
        return isset($this->context['errors'][$key]);
    }

    /**
     * Get errors for a specific key.
     *
     * @return array<string>
     */
    public function getErrorsForKey(string $key): array
    {
        return $this->context['errors'][$key] ?? [];
    }
}
