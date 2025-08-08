<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OnaOnbir\OOSettings\Contracts\ValidationServiceContract;
use OnaOnbir\OOSettings\Exceptions\InvalidKeyException;
use OnaOnbir\OOSettings\Exceptions\InvalidValueException;

/**
 * Comprehensive validation service for OOSettings.
 *
 * Provides robust validation for keys and values with customizable rules
 * and sanitization capabilities.
 */
class ValidationService implements ValidationServiceContract
{
    /**
     * Maximum key length.
     */
    protected int $maxKeyLength;

    /**
     * Reserved key patterns.
     *
     * @var array<string>
     */
    protected array $reservedPatterns;

    /**
     * Allowed key characters regex.
     */
    protected string $allowedKeyChars;

    /**
     * Maximum value size in bytes.
     */
    protected int $maxValueSize;

    /**
     * Custom validation rules for key patterns.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $customRules = [];

    /**
     * Validation errors from last validation.
     *
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * Create a new validation service.
     */
    public function __construct()
    {
        $this->maxKeyLength = config('oo-settings.validation.max_key_length', 255);
        $this->reservedPatterns = config('oo-settings.validation.reserved_patterns', [
            '__*',
            'system.*',
            'internal.*',
            'cache.*',
            'debug.*',
        ]);
        $this->allowedKeyChars = config('oo-settings.validation.allowed_key_chars', '/^[a-zA-Z0-9._-]+$/');
        $this->maxValueSize = config('oo-settings.validation.max_value_size', 1048576); // 1MB
    }

    /**
     * {@inheritdoc}
     */
    public function validateKey(string $key): bool
    {
        $this->clearErrors();

        // Check if key is empty
        if (empty($key)) {
            throw InvalidKeyException::empty();
        }

        // Check key length
        if (strlen($key) > $this->maxKeyLength) {
            throw InvalidKeyException::tooLong($key, $this->maxKeyLength);
        }

        // Check for invalid characters
        if (! preg_match($this->allowedKeyChars, $key)) {
            $invalidChars = $this->findInvalidCharacters($key);
            throw InvalidKeyException::invalidCharacters($key, $invalidChars);
        }

        // Check for malformed dot notation
        if ($this->hasMalformedDotNotation($key)) {
            throw InvalidKeyException::malformedDotNotation($key);
        }

        // Check against reserved patterns
        if ($this->isReservedKey($key)) {
            throw InvalidKeyException::reserved($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(mixed $value, array $rules = []): bool
    {
        $this->clearErrors();

        // Check value size
        $size = $this->calculateValueSize($value);
        if ($size > $this->maxValueSize) {
            throw InvalidValueException::tooLarge($value, $this->maxValueSize, $size);
        }

        // Check if value is serializable
        if (! $this->isSerializable($value)) {
            throw InvalidValueException::notSerializable($value);
        }

        // Check for circular references
        if ($this->hasCircularReference($value)) {
            throw InvalidValueException::circularReference($value);
        }

        // Apply custom validation rules
        if (! empty($rules)) {
            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if ($validator->fails()) {
                throw InvalidValueException::validationFailed($value, $validator->errors()->all());
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeKey(string $key): string
    {
        // Remove leading/trailing whitespace
        $key = trim($key);

        // Convert to lowercase for consistency
        $key = strtolower($key);

        // Replace multiple consecutive dots with single dot
        $key = preg_replace('/\.{2,}/', '.', $key);

        // Remove leading/trailing dots
        $key = trim($key, '.');

        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            // Trim whitespace
            $value = trim($value);

            // Remove null bytes
            $value = str_replace("\0", '', $value);

            // Optionally sanitize HTML if enabled
            if (config('oo-settings.validation.sanitize_html', true)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (is_array($value)) {
            // Recursively sanitize array values
            return array_map([$this, 'sanitizeValue'], $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isReservedKey(string $key): bool
    {
        foreach ($this->reservedPatterns as $pattern) {
            if (Str::is($pattern, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRulesForKey(string $key): array
    {
        foreach ($this->customRules as $pattern => $rules) {
            if (Str::is($pattern, $key)) {
                return $rules;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setRulesForPattern(string $pattern, array $rules): void
    {
        $this->customRules[$pattern] = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function validateMany(array $settings): bool
    {
        $this->clearErrors();
        $hasErrors = false;

        foreach ($settings as $key => $value) {
            try {
                $this->validateKey($key);

                $rules = $this->getRulesForKey($key);
                $this->validateValue($value, $rules);
            } catch (InvalidKeyException $e) {
                $this->errors[$key][] = $e->getMessage();
                $hasErrors = true;
            } catch (InvalidValueException $e) {
                $this->errors[$key][] = $e->getMessage();
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            throw new \OnaOnbir\OOSettings\Exceptions\ValidationException(
                'Multiple validation errors occurred',
                422,
                null,
                ['errors' => $this->errors]
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Find invalid characters in a key.
     *
     * @return array<string>
     */
    protected function findInvalidCharacters(string $key): array
    {
        $allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789._-';
        $invalidChars = [];

        for ($i = 0; $i < strlen($key); $i++) {
            $char = $key[$i];
            if (strpos($allowedChars, $char) === false) {
                $invalidChars[] = $char;
            }
        }

        return array_unique($invalidChars);
    }

    /**
     * Check if key has malformed dot notation.
     */
    protected function hasMalformedDotNotation(string $key): bool
    {
        // Check for consecutive dots
        if (strpos($key, '..') !== false) {
            return true;
        }

        // Check for leading/trailing dots
        if (str_starts_with($key, '.') || str_ends_with($key, '.')) {
            return true;
        }

        return false;
    }

    /**
     * Calculate the size of a value in bytes.
     */
    protected function calculateValueSize(mixed $value): int
    {
        if (is_string($value)) {
            return strlen($value);
        }

        if (is_array($value) || is_object($value)) {
            return strlen(json_encode($value) ?: '');
        }

        return strlen((string) $value);
    }

    /**
     * Check if a value is JSON serializable.
     */
    protected function isSerializable(mixed $value): bool
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);

            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    /**
     * Check if value contains circular references.
     */
    protected function hasCircularReference(mixed $value): bool
    {
        if (! is_array($value) && ! is_object($value)) {
            return false;
        }

        try {
            json_encode($value, JSON_THROW_ON_ERROR);

            return false;
        } catch (\JsonException $e) {
            return str_contains($e->getMessage(), 'recursion') ||
                   str_contains($e->getMessage(), 'circular');
        }
    }
}
