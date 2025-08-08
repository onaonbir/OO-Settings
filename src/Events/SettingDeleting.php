<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Event fired when a setting is being deleted (before removal).
 * 
 * This event can be used to:
 * - Prevent deletion of critical settings
 * - Log the attempted deletion
 * - Clean up related data
 * - Cancel the operation by throwing an exception
 */
class SettingDeleting extends SettingEvent
{
    /**
     * Whether the operation should be cancelled.
     */
    public bool $cancelled = false;

    /**
     * Cancel the setting deletion.
     *
     * @param string $reason Reason for cancellation
     * @return void
     */
    public function cancel(string $reason = 'Deletion cancelled by event listener'): void
    {
        $this->cancelled = true;
        $this->context['cancellation_reason'] = $reason;
    }

    /**
     * Check if the deletion operation was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
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
            'cancelled' => $this->cancelled,
        ]);
    }
}
