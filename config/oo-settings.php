<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'table_names' => [
        'oo_settings' => env('OO_SETTINGS_TABLE', 'oo_settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Enable/disable caching
        'enabled' => env('OO_SETTINGS_CACHE_ENABLED', true),

        // Cache store to use (null = default cache store)
        'store' => env('OO_SETTINGS_CACHE_STORE', null),

        // Cache key prefix
        'prefix' => env('OO_SETTINGS_CACHE_PREFIX', 'oo_settings'),

        // Default TTL in seconds (1 hour)
        'default_ttl' => (int) env('OO_SETTINGS_CACHE_TTL', 3600),

        // Whether to use cache tags (requires Redis or Memcached)
        'use_tags' => env('OO_SETTINGS_CACHE_USE_TAGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Maximum key length
        'max_key_length' => (int) env('OO_SETTINGS_MAX_KEY_LENGTH', 255),

        // Maximum value size in bytes (1MB)
        'max_value_size' => (int) env('OO_SETTINGS_MAX_VALUE_SIZE', 1048576),

        // Allowed key characters pattern
        'allowed_key_chars' => '/^[a-zA-Z0-9._-]+$/',

        // Reserved key patterns that cannot be used
        'reserved_patterns' => [
            '__*',           // Double underscore prefixed keys
            'system.*',      // System namespace
            'internal.*',    // Internal namespace
            'cache.*',       // Cache namespace
            'debug.*',       // Debug namespace
            'temp.*',        // Temporary namespace
            'test.*',        // Test namespace (in production)
        ],

        // Whether to sanitize HTML in string values
        'sanitize_html' => env('OO_SETTINGS_SANITIZE_HTML', true),

        // Custom validation rules for specific key patterns
        'custom_rules' => [
            'email.*' => ['email'],
            'url.*' => ['url'],
            'numeric.*' => ['numeric'],
            'boolean.*' => ['boolean'],
            'date.*' => ['date'],
            // Add more patterns as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    */
    'events' => [
        // Enable/disable event dispatching
        'enabled' => env('OO_SETTINGS_EVENTS_ENABLED', true),

        // Events to dispatch
        'dispatch' => [
            'changing' => true,  // Before setting is changed
            'changed' => true,   // After setting is changed
            'deleting' => true,  // Before setting is deleted
            'deleted' => true,   // After setting is deleted
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        // Enable/disable logging
        'enabled' => env('OO_SETTINGS_LOGGING_ENABLED', true),

        // Log channel to use (null = default log channel)
        'channel' => env('OO_SETTINGS_LOG_CHANNEL', null),

        // Log level for operations
        'level' => env('OO_SETTINGS_LOG_LEVEL', 'info'),

        // Whether to log successful operations (can be verbose)
        'log_info' => env('OO_SETTINGS_LOG_INFO', false),

        // Whether to log cache operations
        'log_cache' => env('OO_SETTINGS_LOG_CACHE', false),

        // Operations to log
        'log_operations' => [
            'get' => false,      // Too verbose for production
            'set' => true,
            'forget' => true,
            'clear' => true,
            'bulk' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Enable encryption for sensitive settings
        'encryption' => [
            'enabled' => env('OO_SETTINGS_ENCRYPTION_ENABLED', false),

            // Key patterns that should be encrypted
            'encrypt_patterns' => [
                'password.*',
                'secret.*',
                'token.*',
                'key.*',
                'credential.*',
                'private.*',
            ],

            // Encryption cipher
            'cipher' => env('OO_SETTINGS_ENCRYPTION_CIPHER', 'AES-256-CBC'),
        ],

        // Rate limiting for setting operations
        'rate_limiting' => [
            'enabled' => env('OO_SETTINGS_RATE_LIMITING_ENABLED', false),

            // Max operations per minute
            'max_attempts' => (int) env('OO_SETTINGS_RATE_LIMIT_ATTEMPTS', 60),

            // Rate limit key prefix
            'key_prefix' => 'oo_settings_rate_limit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // Enable query optimization
        'optimize_queries' => env('OO_SETTINGS_OPTIMIZE_QUERIES', true),

        // Batch size for bulk operations
        'bulk_batch_size' => (int) env('OO_SETTINGS_BULK_BATCH_SIZE', 100),

        // Enable lazy loading
        'lazy_loading' => env('OO_SETTINGS_LAZY_LOADING', true),

        // Cache warming settings
        'cache_warming' => [
            'enabled' => env('OO_SETTINGS_CACHE_WARMING_ENABLED', false),
            'on_boot' => false,  // Warm cache on application boot
            'patterns' => [      // Patterns to warm up
                'app.*',
                'site.*',
                'config.*',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    */
    'development' => [
        // Enable debug mode
        'debug' => env('OO_SETTINGS_DEBUG', false),

        // Track performance metrics
        'track_performance' => env('OO_SETTINGS_TRACK_PERFORMANCE', false),

        // Enable query logging
        'log_queries' => env('OO_SETTINGS_LOG_QUERIES', false),

        // Validate on every operation (expensive)
        'strict_validation' => env('OO_SETTINGS_STRICT_VALIDATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        // Enable audit trail
        'audit_trail' => env('OO_SETTINGS_AUDIT_TRAIL', false),

        // Enable versioning
        'versioning' => env('OO_SETTINGS_VERSIONING', false),

        // Enable multi-tenant support
        'multi_tenant' => env('OO_SETTINGS_MULTI_TENANT', false),

        // Enable backup/restore
        'backup_restore' => env('OO_SETTINGS_BACKUP_RESTORE', false),

        // Enable API endpoints
        'api_endpoints' => env('OO_SETTINGS_API_ENDPOINTS', false),

        // Enable web interface
        'web_interface' => env('OO_SETTINGS_WEB_INTERFACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These are default settings that will be created when the package
    | is first installed or when running the seed command.
    */
    'defaults' => [
        // Global defaults
        'global' => [
            'app.name' => env('APP_NAME', 'Laravel'),
            'app.timezone' => env('APP_TIMEZONE', 'UTC'),
            'app.locale' => env('APP_LOCALE', 'en'),
        ],

        // Model-specific defaults (applied when trait is first used)
        'model' => [
            // User model defaults
            'App\Models\User' => [
                'preferences.theme' => 'default',
                'preferences.language' => 'en',
                'notifications.email' => true,
                'privacy.profile_public' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export/Import Configuration
    |--------------------------------------------------------------------------
    */
    'export_import' => [
        // Allowed formats for export/import
        'formats' => ['json', 'yaml', 'csv', 'xml'],

        // Default format
        'default_format' => 'json',

        // Whether to include metadata in exports
        'include_metadata' => true,

        // Compression for large exports
        'compression' => [
            'enabled' => true,
            'method' => 'gzip',  // gzip, bzip2, zip
        ],
    ],
];
