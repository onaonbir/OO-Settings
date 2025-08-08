<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Event fired after a setting has been successfully set.
 *
 * This event can be used to:
 * - Log the setting change
 * - Clear related caches
 * - Notify other systems
 * - Trigger dependent operations
 */
class SettingChanged extends SettingEvent
{
    /**
     * The old value (null if setting didn't exist).
     */
    public readonly mixed $oldValue;

    /**
     * Whether this was a new setting creation.
     */
    public readonly bool $wasCreated;

    /**
     * Create a new setting changed event.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $newValue  New setting value
     * @param  mixed  $oldValue  Old setting value
     * @param  Model|null  $model  Model instance
     * @param  array<string, mixed>  $context  Additional context
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
        $this->wasCreated = $oldValue === null;
    }

    /**
     * Check if this was a new setting creation.
     */
    public function wasCreated(): bool
    {
        return $this->wasCreated;
    }

    /**
     * Check if this was an update to existing setting.
     */
    public function wasUpdated(): bool
    {
        return ! $this->wasCreated;
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
            'was_created' => $this->wasCreated,
            'was_updated' => $this->wasUpdated(),
        ]);
    }
}
