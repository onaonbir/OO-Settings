<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use OnaOnbir\OOSettings\Contracts\SettingsContract;

/**
 * Export OOSettings to file command.
 */
class ExportSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oo-settings:export 
                           {file? : Output file path}
                           {--format=json : Export format (json, yaml, csv)}
                           {--global : Export only global settings}
                           {--model= : Export settings for specific model class}
                           {--compress : Compress the output file}
                           {--include-meta : Include metadata in export}';

    /**
     * The console command description.
     */
    protected $description = 'Export OOSettings to a file';

    /**
     * Execute the console command.
     */
    public function handle(SettingsContract $settings): int
    {
        $format = $this->option('format');
        $file = $this->argument('file') ?: $this->generateDefaultFilename($format);
        
        $this->info("Exporting OOSettings to: <comment>{$file}</comment>");

        try {
            $data = $this->collectExportData($settings);
            
            if (empty($data)) {
                $this->warn('No settings found to export.');
                return 0;
            }

            $exportedContent = $this->formatData($data, $format);
            
            if ($this->option('compress')) {
                $exportedContent = $this->compressData($exportedContent);
                $file .= '.gz';
            }

            Storage::disk('local')->put($file, $exportedContent);

            $this->info("✓ Exported " . count($data) . " settings to: <info>{$file}</info>");
            
            if ($this->option('include-meta')) {
                $this->displayExportSummary($data);
            }

            return 0;
            
        } catch (\Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Collect data for export.
     */
    protected function collectExportData(SettingsContract $settings): array
    {
        $data = [];

        if ($this->option('global') || (!$this->option('model'))) {
            // Export global settings
            $globalSettings = $settings->all();
            
            foreach ($globalSettings as $key => $value) {
                $data[] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'global',
                    'model_class' => null,
                    'model_id' => null,
                    'exported_at' => now()->toISOString(),
                ];
            }
        }

        return $data;
    }

    /**
     * Format data according to the specified format.
     */
    protected function formatData(array $data, string $format): string
    {
        return match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'yaml' => $this->arrayToYaml($data),
            'csv' => $this->arrayToCsv($data),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Convert array to YAML format.
     */
    protected function arrayToYaml(array $data): string
    {
        $yaml = "# OOSettings Export\n# Generated: " . now()->toISOString() . "\n\n";
        
        foreach ($data as $item) {
            $yaml .= "- key: \"{$item['key']}\"\n";
            $yaml .= "  value: " . json_encode($item['value']) . "\n";
            $yaml .= "  type: \"{$item['type']}\"\n";
            $yaml .= "  exported_at: \"{$item['exported_at']}\"\n\n";
        }
        
        return $yaml;
    }

    /**
     * Convert array to CSV format.
     */
    protected function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $csv = "key,value,type,model_class,model_id,exported_at\n";
        
        foreach ($data as $item) {
            $value = is_string($item['value']) ? $item['value'] : json_encode($item['value']);
            $csv .= sprintf(
                "%s,\"%s\",%s,%s,%s,%s\n",
                $item['key'],
                str_replace('"', '""', $value),
                $item['type'],
                $item['model_class'] ?? '',
                $item['model_id'] ?? '',
                $item['exported_at']
            );
        }
        
        return $csv;
    }

    /**
     * Compress data using gzip.
     */
    protected function compressData(string $data): string
    {
        return gzencode($data, 9);
    }

    /**
     * Generate default filename based on format.
     */
    protected function generateDefaultFilename(string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "oo-settings-export_{$timestamp}.{$format}";
    }

    /**
     * Display export summary.
     */
    protected function displayExportSummary(array $data): void
    {
        $globalCount = count(array_filter($data, fn($item) => $item['type'] === 'global'));
        $modelCount = count($data) - $globalCount;
        
        $this->table(['Type', 'Count'], [
            ['Global Settings', $globalCount],
            ['Model Settings', $modelCount],
            ['Total', count($data)],
        ]);
    }
}
