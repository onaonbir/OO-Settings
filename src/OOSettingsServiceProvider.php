<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use OnaOnbir\OOSettings\Contracts\SettingsContract;
use OnaOnbir\OOSettings\Contracts\CacheManagerContract;
use OnaOnbir\OOSettings\Contracts\ValidationServiceContract;
use OnaOnbir\OOSettings\Services\CacheManager;
use OnaOnbir\OOSettings\Services\ValidationService;
use OnaOnbir\OOSettings\Repositories\SettingRepository;
use OnaOnbir\OOSettings\Models\Setting;
use OnaOnbir\OOSettings\Console\Commands\ClearCacheCommand;
use OnaOnbir\OOSettings\Console\Commands\ExportSettingsCommand;
use OnaOnbir\OOSettings\Console\Commands\ImportSettingsCommand;
use OnaOnbir\OOSettings\Console\Commands\WarmCacheCommand;

/**
 * Advanced OOSettings Service Provider with dependency injection,
 * caching, validation, and comprehensive Laravel integration.
 */
class OOSettingsServiceProvider extends ServiceProvider
{
    /**
     * Package name.
     */
    protected string $packageName = 'oo-settings';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/'.$this->packageName.'.php',
            $this->packageName
        );

        // Register contracts and implementations
        $this->registerContracts();
        
        // Register main service
        $this->registerOOSettings();
        
        // Register repository
        $this->registerRepository();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Register custom validation rules (after Laravel is fully booted)
        $this->registerValidationRules();
        
        // Publish configuration and migrations
        $this->publishConfiguration();
        $this->publishMigrations();
        
        // Register console commands
        $this->registerCommands();
        
        // Boot additional features
        $this->bootFeatures();
        
        // Cache warming if enabled
        $this->warmCacheIfEnabled();
    }

    /**
     * Register contracts and their implementations.
     */
    protected function registerContracts(): void
    {
        // Cache Manager
        $this->app->singleton(CacheManagerContract::class, function ($app) {
            return new CacheManager();
        });

        // Validation Service
        $this->app->singleton(ValidationServiceContract::class, function ($app) {
            return new ValidationService();
        });
    }

    /**
     * Register the main OOSettings service.
     */
    protected function registerOOSettings(): void
    {
        $this->app->singleton(SettingsContract::class, function ($app) {
            return new OOSettings(
                $app->make(SettingRepository::class),
                $app->make(CacheManagerContract::class),
                $app->make(ValidationServiceContract::class)
            );
        });

        // Also register as 'oo-settings' for easy access
        $this->app->alias(SettingsContract::class, 'oo-settings');
        
        // Backward compatibility alias
        $this->app->alias(SettingsContract::class, OOSettings::class);
    }

    /**
     * Register the setting repository.
     */
    protected function registerRepository(): void
    {
        $this->app->singleton(SettingRepository::class, function ($app) {
            return new SettingRepository(new Setting());
        });
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidationRules(): void
    {
        // Only register if validator is available
        if (!$this->app->bound('validator')) {
            return;
        }
        
        try {
            // Extend Laravel's validator with custom rules
            Validator::extend('setting_key', function ($attribute, $value, $parameters, $validator) {
                try {
                    app(ValidationServiceContract::class)->validateKey($value);
                    return true;
                } catch (\Exception) {
                    return false;
                }
            });

            Validator::extend('setting_value', function ($attribute, $value, $parameters, $validator) {
                try {
                    app(ValidationServiceContract::class)->validateValue($value);
                    return true;
                } catch (\Exception) {
                    return false;
                }
            });

            // Custom validation messages
            Validator::replacer('setting_key', function ($message, $attribute, $rule, $parameters) {
                return 'The :attribute field must be a valid setting key.';
            });

            Validator::replacer('setting_value', function ($message, $attribute, $rule, $parameters) {
                return 'The :attribute field must be a valid setting value.';
            });
        } catch (\Throwable $e) {
            // Silently fail if validation service registration fails
            // This prevents errors during testing or package installation
        }
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/../config/'.$this->packageName.'.php' => config_path($this->packageName.'.php'),
        ], $this->packageName.'-config');
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], $this->packageName.'-migrations');
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearCacheCommand::class,
                ExportSettingsCommand::class,
                ImportSettingsCommand::class,
                WarmCacheCommand::class,
            ]);
        }
    }

    /**
     * Boot additional features based on configuration.
     */
    protected function bootFeatures(): void
    {
        // Boot audit trail if enabled
        if (config('oo-settings.features.audit_trail', false)) {
            $this->bootAuditTrail();
        }

        // Boot API endpoints if enabled
        if (config('oo-settings.features.api_endpoints', false)) {
            $this->bootApiEndpoints();
        }

        // Boot web interface if enabled
        if (config('oo-settings.features.web_interface', false)) {
            $this->bootWebInterface();
        }

        // Boot multi-tenant support if enabled
        if (config('oo-settings.features.multi_tenant', false)) {
            $this->bootMultiTenant();
        }
    }

    /**
     * Warm cache if enabled and configured.
     */
    protected function warmCacheIfEnabled(): void
    {
        if (!config('oo-settings.performance.cache_warming.enabled', false)) {
            return;
        }

        if (!config('oo-settings.performance.cache_warming.on_boot', false)) {
            return;
        }

        // Defer cache warming to avoid blocking application boot
        $this->app->booted(function () {
            try {
                $patterns = config('oo-settings.performance.cache_warming.patterns', []);
                $this->warmCacheForPatterns($patterns);
            } catch (\Exception $e) {
                // Log error but don't break application boot
                logger()->error('OOSettings cache warming failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Boot audit trail functionality.
     */
    protected function bootAuditTrail(): void
    {
        // Register audit trail event listeners
        // This would be implemented in a separate service
    }

    /**
     * Boot API endpoints.
     */
    protected function bootApiEndpoints(): void
    {
        // Load API routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Boot web interface.
     */
    protected function bootWebInterface(): void
    {
        // Load web routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'oo-settings');
        
        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/oo-settings'),
        ], $this->packageName.'-views');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/oo-settings'),
        ], $this->packageName.'-assets');
    }

    /**
     * Boot multi-tenant support.
     */
    protected function bootMultiTenant(): void
    {
        // Register tenant-aware middleware and services
        // This would be implemented based on specific tenant strategy
    }

    /**
     * Warm cache for specific patterns.
     */
    protected function warmCacheForPatterns(array $patterns): void
    {
        $settingsService = app(SettingsContract::class);
        $repository = app(SettingRepository::class);

        foreach ($patterns as $pattern) {
            try {
                $settings = $repository->searchByKeyPattern($pattern);
                
                foreach ($settings as $setting) {
                    // Pre-load into cache
                    $settingsService->get($setting->key);
                }
            } catch (\Exception $e) {
                logger()->warning("Failed to warm cache for pattern: {$pattern}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            SettingsContract::class,
            CacheManagerContract::class,
            ValidationServiceContract::class,
            SettingRepository::class,
            'oo-settings',
        ];
    }
}
