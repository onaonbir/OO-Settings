<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Exceptions;

/**
 * Exception thrown when an invalid setting value is used.
 * 
 * This includes values that fail validation rules, exceed size limits,
 * or contain unsafe content.
 */
class InvalidValueException extends OOSettingsException
{
    /**
     * Create a new invalid value exception.
     *
     * @param mixed $value The invalid value
     * @param string $reason Reason why the value is invalid
     * @param array<string, mixed> $context Additional context
     * @return static
     */
    public static function forValue(mixed $value, string $reason, array $context = []): static
    {
        $valueType = is_object($value) ? get_class($value) : gettype($value);
        $message = "Invalid setting value ({$valueType}): {$reason}";
        
        return new static($message, 422, null, array_merge([
            'value_type' => $valueType,
            'reason' => $reason,
        ], $context));
    }

    /**
     * Create exception for value that's too large.
     *
     * @param mixed $value The large value
     * @param int $maxSize Maximum allowed size in bytes
     * @param int $actualSize Actual size in bytes
     * @return static
     */
    public static function tooLarge(mixed $value, int $maxSize, int $actualSize): static
    {
        return static::forValue($value, "Value size ({$actualSize} bytes) exceeds maximum ({$maxSize} bytes)", [
            'max_size' => $maxSize,
            'actual_size' => $actualSize,
        ]);
    }

    /**
     * Create exception for unsupported value type.
     *
     * @param mixed $value The unsupported value
     * @param array<string> $supportedTypes List of supported types
     * @return static
     */
    public static function unsupportedType(mixed $value, array $supportedTypes): static
    {
        $types = implode(', ', $supportedTypes);
        return static::forValue($value, "Unsupported value type. Supported types: {$types}", [
            'supported_types' => $supportedTypes,
        ]);
    }

    /**
     * Create exception for validation failure.
     *
     * @param mixed $value The invalid value
     * @param array<string> $errors Validation errors
     * @return static
     */
    public static function validationFailed(mixed $value, array $errors): static
    {
        $errorList = implode(', ', $errors);
        return static::forValue($value, "Validation failed: {$errorList}", [
            'validation_errors' => $errors,
        ]);
    }

    /**
     * Create exception for circular reference in data.
     *
     * @param mixed $value The value with circular reference
     * @return static
     */
    public static function circularReference(mixed $value): static
    {
        return static::forValue($value, 'Value contains circular reference and cannot be serialized');
    }

    /**
     * Create exception for non-serializable value.
     *
     * @param mixed $value The non-serializable value
     * @return static
     */
    public static function notSerializable(mixed $value): static
    {
        return static::forValue($value, 'Value cannot be serialized to JSON');
    }
}
