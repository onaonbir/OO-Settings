<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Event fired when a setting is being set (before save).
 * 
 * This event can be used to:
 * - Validate the setting change
 * - Transform the value before saving
 * - Log the attempted change
 * - Cancel the operation by throwing an exception
 */
class SettingChanging extends SettingEvent
{
    /**
     * The old value (null if setting didn't exist).
     */
    public readonly mixed $oldValue;

    /**
     * Whether the operation should be cancelled.
     */
    public bool $cancelled = false;

    /**
     * Create a new setting changing event.
     *
     * @param string $key Setting key
     * @param mixed $newValue New setting value
     * @param mixed $oldValue Old setting value
     * @param Model|null $model Model instance
     * @param array<string, mixed> $context Additional context
     */
    public function __construct(
        string $key,
        mixed $newValue,
        mixed $oldValue = null,
        ?Model $model = null,
        array $context = []
    ) {
        parent::__construct($key, $newValue, $model, $context);
        $this->oldValue = $oldValue;
    }

    /**
     * Cancel the setting operation.
     *
     * @param string $reason Reason for cancellation
     * @return void
     */
    public function cancel(string $reason = 'Operation cancelled by event listener'): void
    {
        $this->cancelled = true;
        $this->context['cancellation_reason'] = $reason;
    }

    /**
     * Check if the setting operation was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Check if this is a new setting (no old value).
     */
    public function isNewSetting(): bool
    {
        return $this->oldValue === null;
    }

    /**
     * Check if the value is actually changing.
     */
    public function isValueChanging(): bool
    {
        return $this->value !== $this->oldValue;
    }

    /**
     * Get the cancellation reason if cancelled.
     */
    public function getCancellationReason(): ?string
    {
        return $this->context['cancellation_reason'] ?? null;
    }

    /**
     * Convert event to array for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'old_value' => $this->oldValue,
            'cancelled' => $this->cancelled,
            'is_new_setting' => $this->isNewSetting(),
            'is_value_changing' => $this->isValueChanging(),
        ]);
    }
}
