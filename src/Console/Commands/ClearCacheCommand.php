<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Console\Commands;

use Illuminate\Console\Command;
use OnaOnbir\OOSettings\Contracts\SettingsContract;

/**
 * Clear OOSettings cache command.
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oo-settings:clear-cache 
                           {--global : Clear only global settings cache}
                           {--model= : Clear cache for specific model class}
                           {--id= : Clear cache for specific model ID (requires --model)}';

    /**
     * The console command description.
     */
    protected $description = 'Clear OOSettings cache';

    /**
     * Execute the console command.
     */
    public function handle(SettingsContract $settings): int
    {
        $this->info('Clearing OOSettings cache...');

        try {
            $cacheManager = $settings->getCacheManager();
            
            if ($this->option('global')) {
                // Clear only global cache
                $success = $cacheManager->invalidateGlobal();
                $this->info('Global settings cache cleared.');
            } elseif ($this->option('model')) {
                // Clear model-specific cache
                $modelClass = $this->option('model');
                $modelId = $this->option('id');
                
                if (!$modelId) {
                    $this->error('Model ID is required when clearing model-specific cache.');
                    return 1;
                }
                
                $success = $cacheManager->invalidateModel($modelClass, $modelId);
                $this->info("Cache cleared for {$modelClass}#{$modelId}");
            } else {
                // Clear all cache
                $success = $settings->clearCache();
                $this->info('All OOSettings cache cleared.');
            }

            if (!$success) {
                $this->error('Failed to clear cache.');
                return 1;
            }

            $this->line('<info>✓</info> Cache cleared successfully.');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error clearing cache: ' . $e->getMessage());
            return 1;
        }
    }
}
