<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Events;

/**
 * Event fired after a setting has been successfully deleted.
 * 
 * This event can be used to:
 * - Log the setting deletion
 * - Clear related caches
 * - Notify other systems
 * - Clean up dependent data
 */
class SettingDeleted extends SettingEvent
{
    /**
     * Convert event to array for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'action' => 'deleted',
        ]);
    }
}
