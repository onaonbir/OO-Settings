<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event for all OOSettings events.
 *
 * Provides common functionality and structure for setting-related events.
 */
abstract class SettingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The setting key.
     */
    public readonly string $key;

    /**
     * The setting value.
     */
    public readonly mixed $value;

    /**
     * The model instance (null for global settings).
     */
    public readonly ?Model $model;

    /**
     * Event timestamp.
     */
    public readonly float $timestamp;

    /**
     * Additional event context.
     *
     * @var array<string, mixed>
     */
    public readonly array $context;

    /**
     * Create a new setting event.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Setting value
     * @param  Model|null  $model  Model instance
     * @param  array<string, mixed>  $context  Additional context
     */
    public function __construct(string $key, mixed $value, ?Model $model = null, array $context = [])
    {
        $this->key = $key;
        $this->value = $value;
        $this->model = $model;
        $this->timestamp = microtime(true);
        $this->context = $context;
    }

    /**
     * Check if this is a global setting event.
     */
    public function isGlobal(): bool
    {
        return $this->model === null;
    }

    /**
     * Check if this is a model-specific setting event.
     */
    public function isModelSpecific(): bool
    {
        return $this->model !== null;
    }

    /**
     * Get the model class name if applicable.
     */
    public function getModelClass(): ?string
    {
        return $this->model ? get_class($this->model) : null;
    }

    /**
     * Get the model ID if applicable.
     */
    public function getModelId(): int|string|null
    {
        return $this->model ? $this->model->getKey() : null;
    }

    /**
     * Convert event to array for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event' => static::class,
            'key' => $this->key,
            'value' => $this->value,
            'model_class' => $this->getModelClass(),
            'model_id' => $this->getModelId(),
            'timestamp' => $this->timestamp,
            'context' => $this->context,
        ];
    }
}
