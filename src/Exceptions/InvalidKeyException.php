<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Exceptions;

/**
 * Exception thrown when an invalid setting key is used.
 *
 * This includes keys that are empty, contain invalid characters,
 * exceed maximum length, or match reserved patterns.
 */
class InvalidKeyException extends OOSettingsException
{
    /**
     * Create a new invalid key exception.
     *
     * @param  string  $key  The invalid key
     * @param  string  $reason  Reason why the key is invalid
     * @param  array<string, mixed>  $context  Additional context
     */
    public static function forKey(string $key, string $reason, array $context = []): static
    {
        $message = "Invalid setting key '{$key}': {$reason}";

        return new static($message, 400, null, array_merge([
            'key' => $key,
            'reason' => $reason,
        ], $context));
    }

    /**
     * Create exception for empty key.
     */
    public static function empty(): static
    {
        return static::forKey('', 'Key cannot be empty');
    }

    /**
     * Create exception for reserved key.
     *
     * @param  string  $key  The reserved key
     */
    public static function reserved(string $key): static
    {
        return static::forKey($key, 'Key is reserved and cannot be used');
    }

    /**
     * Create exception for key that's too long.
     *
     * @param  string  $key  The long key
     * @param  int  $maxLength  Maximum allowed length
     */
    public static function tooLong(string $key, int $maxLength): static
    {
        $length = strlen($key);

        return static::forKey($key, "Key length ({$length}) exceeds maximum ({$maxLength})", [
            'actual_length' => $length,
            'max_length' => $maxLength,
        ]);
    }

    /**
     * Create exception for key with invalid characters.
     *
     * @param  string  $key  The key with invalid characters
     * @param  array<string>  $invalidChars  List of invalid characters found
     */
    public static function invalidCharacters(string $key, array $invalidChars): static
    {
        $chars = implode(', ', $invalidChars);

        return static::forKey($key, "Key contains invalid characters: {$chars}", [
            'invalid_characters' => $invalidChars,
        ]);
    }

    /**
     * Create exception for malformed dot notation.
     *
     * @param  string  $key  The malformed key
     */
    public static function malformedDotNotation(string $key): static
    {
        return static::forKey($key, 'Malformed dot notation (consecutive dots, leading/trailing dots)');
    }
}
