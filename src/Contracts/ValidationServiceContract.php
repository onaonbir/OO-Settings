<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Contracts;

/**
 * Validation Service Contract for settings validation.
 * 
 * Provides comprehensive validation for setting keys and values
 * with support for custom rules and sanitization.
 */
interface ValidationServiceContract
{
    /**
     * Validate a setting key.
     *
     * @param string $key The setting key
     * @return bool True if valid
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     */
    public function validateKey(string $key): bool;

    /**
     * Validate a setting value.
     *
     * @param mixed $value The setting value
     * @param array<string, mixed> $rules Validation rules
     * @return bool True if valid
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidValueException
     */
    public function validateValue(mixed $value, array $rules = []): bool;

    /**
     * Sanitize a setting key.
     *
     * @param string $key The setting key
     * @return string Sanitized key
     */
    public function sanitizeKey(string $key): string;

    /**
     * Sanitize a setting value.
     *
     * @param mixed $value The setting value
     * @return mixed Sanitized value
     */
    public function sanitizeValue(mixed $value): mixed;

    /**
     * Check if a key matches reserved patterns.
     *
     * @param string $key The setting key
     * @return bool True if key is reserved
     */
    public function isReservedKey(string $key): bool;

    /**
     * Get validation rules for a specific key.
     *
     * @param string $key The setting key
     * @return array<string, mixed> Validation rules
     */
    public function getRulesForKey(string $key): array;

    /**
     * Set validation rules for a key pattern.
     *
     * @param string $pattern Key pattern (supports wildcards)
     * @param array<string, mixed> $rules Validation rules
     * @return void
     */
    public function setRulesForPattern(string $pattern, array $rules): void;

    /**
     * Validate multiple settings at once.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @return bool True if all are valid
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\ValidationException
     */
    public function validateMany(array $settings): bool;

    /**
     * Get all validation errors from last validation.
     *
     * @return array<string, array<string>> Validation errors
     */
    public function getErrors(): array;

    /**
     * Clear validation errors.
     *
     * @return void
     */
    public function clearErrors(): void;
}
