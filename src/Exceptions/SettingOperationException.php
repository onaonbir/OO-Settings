<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Exceptions;

/**
 * Exception thrown when a setting operation fails.
 * 
 * This includes database errors, cache failures, and other
 * operational issues that prevent setting management.
 */
class SettingOperationException extends OOSettingsException
{
    /**
     * Create exception for failed database operation.
     *
     * @param string $operation The operation that failed
     * @param string $details Error details
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function databaseOperation(string $operation, string $details, ?\Throwable $previous = null): static
    {
        $message = "Database operation '{$operation}' failed: {$details}";
        
        return new static($message, 500, $previous, [
            'operation' => $operation,
            'type' => 'database',
        ]);
    }

    /**
     * Create exception for failed cache operation.
     *
     * @param string $operation The operation that failed
     * @param string $details Error details
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function cacheOperation(string $operation, string $details, ?\Throwable $previous = null): static
    {
        $message = "Cache operation '{$operation}' failed: {$details}";
        
        return new static($message, 500, $previous, [
            'operation' => $operation,
            'type' => 'cache',
        ]);
    }

    /**
     * Create exception for failed encryption operation.
     *
     * @param string $operation The operation that failed
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function encryptionOperation(string $operation, ?\Throwable $previous = null): static
    {
        $message = "Encryption operation '{$operation}' failed";
        
        return new static($message, 500, $previous, [
            'operation' => $operation,
            'type' => 'encryption',
        ]);
    }

    /**
     * Create exception for concurrent modification.
     *
     * @param string $key The setting key
     * @return static
     */
    public static function concurrentModification(string $key): static
    {
        $message = "Setting '{$key}' was modified by another process during operation";
        
        return new static($message, 409, null, [
            'key' => $key,
            'type' => 'concurrency',
        ]);
    }

    /**
     * Create exception for read-only setting.
     *
     * @param string $key The read-only setting key
     * @return static
     */
    public static function readOnly(string $key): static
    {
        $message = "Setting '{$key}' is read-only and cannot be modified";
        
        return new static($message, 403, null, [
            'key' => $key,
            'type' => 'readonly',
        ]);
    }
}
