<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use OnaOnbir\OOSettings\Contracts\SettingsContract;

/**
 * Import OOSettings from file command.
 */
class ImportSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oo-settings:import 
                           {file : Import file path}
                           {--format=json : Import format (json, yaml, csv)}
                           {--merge : Merge with existing settings instead of replacing}
                           {--dry-run : Show what would be imported without actually importing}
                           {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Import OOSettings from a file';

    /**
     * Execute the console command.
     */
    public function handle(SettingsContract $settings): int
    {
        $file = $this->argument('file');
        $format = $this->option('format');
        
        if (!Storage::disk('local')->exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Importing OOSettings from: <comment>{$file}</comment>");

        try {
            $content = Storage::disk('local')->get($file);
            
            // Check if file is compressed
            if (str_ends_with($file, '.gz')) {
                $content = gzdecode($content);
                if ($content === false) {
                    $this->error('Failed to decompress file.');
                    return 1;
                }
            }

            $data = $this->parseData($content, $format);
            
            if (empty($data)) {
                $this->warn('No settings found in the import file.');
                return 0;
            }

            if ($this->option('dry-run')) {
                return $this->showDryRun($data);
            }

            if (!$this->option('force') && !$this->confirm("Import {$this->countSettings($data)} setting(s)?")) {
                $this->info('Import cancelled.');
                return 0;
            }

            $imported = $this->importSettings($settings, $data);

            $this->info("✓ Successfully imported {$imported} settings.");
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Parse data according to format.
     */
    protected function parseData(string $content, string $format): array
    {
        return match ($format) {
            'json' => $this->parseJson($content),
            'yaml' => $this->parseYaml($content),
            'csv' => $this->parseCsv($content),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Parse JSON content.
     */
    protected function parseJson(string $content): array
    {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Parse YAML content.
     */
    protected function parseYaml(string $content): array
    {
        // Simple YAML parser - in production use symfony/yaml
        $lines = explode("\n", $content);
        $data = [];
        $current = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            if (str_starts_with($line, '- key:')) {
                if ($current) {
                    $data[] = $current;
                }
                $current = ['key' => trim(str_replace(['- key:', '"'], '', $line))];
            } elseif (str_starts_with($line, 'value:') && $current) {
                $value = trim(str_replace('value:', '', $line));
                $current['value'] = json_decode($value, true) ?? $value;
            } elseif (str_starts_with($line, 'type:') && $current) {
                $current['type'] = trim(str_replace(['type:', '"'], '', $line));
            }
        }
        
        if ($current) {
            $data[] = $current;
        }
        
        return $data;
    }

    /**
     * Parse CSV content.
     */
    protected function parseCsv(string $content): array
    {
        $lines = explode("\n", trim($content));
        $headers = str_getcsv(array_shift($lines));
        $data = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $values = str_getcsv($line);
            $item = array_combine($headers, $values);
            
            // Parse JSON values if needed
            if (isset($item['value']) && $this->isJson($item['value'])) {
                $item['value'] = json_decode($item['value'], true);
            }
            
            $data[] = $item;
        }
        
        return $data;
    }

    /**
     * Check if string is valid JSON.
     */
    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Show dry run results.
     */
    protected function showDryRun(array $data): int
    {
        $this->info('DRY RUN - No changes will be made');
        $this->line('');
        
        $globalSettings = array_filter($data, fn($item) => ($item['type'] ?? 'global') === 'global');
        $modelSettings = array_filter($data, fn($item) => ($item['type'] ?? 'global') !== 'global');
        
        if (!empty($globalSettings)) {
            $this->info('Global Settings to Import:');
            foreach ($globalSettings as $item) {
                $value = is_array($item['value']) ? json_encode($item['value']) : $item['value'];
                $this->line("  • {$item['key']} = {$value}");
            }
            $this->line('');
        }
        
        if (!empty($modelSettings)) {
            $this->info('Model Settings to Import:');
            foreach ($modelSettings as $item) {
                $value = is_array($item['value']) ? json_encode($item['value']) : $item['value'];
                $model = $item['model_class'] ?? 'Unknown';
                $id = $item['model_id'] ?? 'Unknown';
                $this->line("  • {$model}#{$id}: {$item['key']} = {$value}");
            }
        }
        
        $this->table(['Type', 'Count'], [
            ['Global Settings', count($globalSettings)],
            ['Model Settings', count($modelSettings)],
            ['Total', count($data)],
        ]);
        
        return 0;
    }

    /**
     * Count settings in data.
     */
    protected function countSettings(array $data): int
    {
        return count($data);
    }

    /**
     * Import settings data.
     */
    protected function importSettings(SettingsContract $settings, array $data): int
    {
        $imported = 0;
        $progressBar = $this->output->createProgressBar(count($data));
        
        foreach ($data as $item) {
            try {
                $key = $item['key'];
                $value = $item['value'];
                $type = $item['type'] ?? 'global';
                
                if ($type === 'global') {
                    if ($this->option('merge') && $settings->has($key)) {
                        // Skip existing settings when merging
                        continue;
                    }
                    
                    $settings->set($key, $value);
                    $imported++;
                } else {
                    // Model-specific settings would be handled here
                    // This is a simplified implementation
                    $this->warn("Skipping model setting: {$key}");
                }
                
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to import {$item['key']}: {$e->getMessage()}");
            }
        }
        
        $progressBar->finish();
        $this->line('');
        
        return $imported;
    }
}
