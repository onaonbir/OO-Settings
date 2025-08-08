<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Console\Commands;

use Illuminate\Console\Command;
use OnaOnbir\OOSettings\Contracts\SettingsContract;

/**
 * Warm OOSettings cache command.
 */
class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oo-settings:warm-cache 
                           {--pattern=* : Key patterns to warm (supports wildcards)}
                           {--global : Warm only global settings}
                           {--force : Force cache warming even if disabled}';

    /**
     * The console command description.
     */
    protected $description = 'Warm OOSettings cache by pre-loading frequently accessed settings';

    /**
     * Execute the console command.
     */
    public function handle(SettingsContract $settings): int
    {
        if (! config('oo-settings.performance.cache_warming.enabled') && ! $this->option('force')) {
            $this->warn('Cache warming is disabled. Use --force to override.');

            return 1;
        }

        $this->info('Warming OOSettings cache...');

        try {
            $patterns = $this->option('pattern') ?: config('oo-settings.performance.cache_warming.patterns', []);

            if (empty($patterns)) {
                $patterns = ['*']; // Default to all settings
            }

            $repository = $settings->getRepository();
            $warmedCount = 0;

            foreach ($patterns as $pattern) {
                $this->line("Warming cache for pattern: <comment>{$pattern}</comment>");

                if ($this->option('global')) {
                    $foundSettings = $repository->searchByKeyPattern($pattern);
                } else {
                    $foundSettings = $repository->searchByKeyPattern($pattern);
                }

                foreach ($foundSettings as $setting) {
                    // Pre-load the setting into cache
                    if ($setting->settingable_type === null) {
                        $settings->get($setting->key);
                    } else {
                        // For model settings, we'd need the actual model instance
                        // This is a simplified implementation
                        $this->line("  Skipping model setting: {$setting->key}");

                        continue;
                    }

                    $warmedCount++;
                    $this->line("  ✓ Warmed: <info>{$setting->key}</info>");
                }
            }

            $this->line('');
            $this->info("Cache warming completed. Warmed {$warmedCount} settings.");

            // Show cache statistics
            $stats = $settings->getCacheManager()->stats();
            $this->table(['Metric', 'Value'], [
                ['Hit Ratio', $stats['hit_ratio'].'%'],
                ['Hits', $stats['hits']],
                ['Misses', $stats['misses']],
                ['Writes', $stats['writes']],
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Error warming cache: '.$e->getMessage());

            return 1;
        }
    }
}
