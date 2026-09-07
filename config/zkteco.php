<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZKTeco Device Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for ZKTeco biometric devices.
    | You can configure multiple devices and their connection settings.
    |
    */

    // Default device IP address
    'device_ip' => env('ZKTECO_DEVICE_IP', '192.168.1.201'),

    // Default device port
    'device_port' => env('ZKTECO_DEVICE_PORT', 4370),

    // Connection timeout in seconds
    'connection_timeout' => env('ZKTECO_CONNECTION_TIMEOUT', 60),

    // Connection mode: "adms" (device pushes) or "pull" (UDP rats/zkteco)
    'mode' => env('ZKTECO_MODE', 'adms'),

    // ADMS / Cloud Server settings (must match device Cloud Server menu)
    'adms' => [
        'timezone' => env('ZKTECO_ADMS_TIMEZONE', 6),
        'online_window_seconds' => env('ZKTECO_ADMS_ONLINE_WINDOW', 120),
    ],

    // Multiple devices configuration
    'devices' => [
        'main' => [
            'ip' => env('ZKTECO_MAIN_DEVICE_IP', '192.168.1.201'),
            'port' => env('ZKTECO_MAIN_DEVICE_PORT', 4370),
            'name' => 'Main Entrance',
            'location' => 'Building A - Main Door',
        ],
        // Add more devices as needed
        // 'secondary' => [
        //     'ip' => env('ZKTECO_SECONDARY_DEVICE_IP', '192.168.1.202'),
        //     'port' => env('ZKTECO_SECONDARY_DEVICE_PORT', 4370),
        //     'name' => 'Secondary Entrance',
        //     'location' => 'Building B - Side Door',
        // ],
    ],

    // Sync settings
    'sync' => [
        // Auto sync interval in minutes (0 to disable)
        'auto_sync_interval' => env('ZKTECO_AUTO_SYNC_INTERVAL', 5),
        
        // Maximum logs to sync per request
        'max_logs_per_sync' => env('ZKTECO_MAX_LOGS_PER_SYNC', 1000),
        
        // Delete logs from device after successful sync
        'delete_after_sync' => env('ZKTECO_DELETE_AFTER_SYNC', false),
    ],

    // User mapping settings
    'user_mapping' => [
        // Field in users table to match with device user ID
        'user_id_field' => env('ZKTECO_USER_ID_FIELD', 'device_user_id'),
        
        // Alternative field for mapping (fallback)
        'alternative_field' => env('ZKTECO_ALTERNATIVE_FIELD', 'employee_id'),
        
        // Create new users if not found in database
        'auto_create_users' => env('ZKTECO_AUTO_CREATE_USERS', false),
    ],

    // Punch type mapping
    'punch_types' => [
        0 => 'check_in',
        1 => 'check_out',
        2 => 'break_out',
        3 => 'break_in',
        4 => 'overtime_in',
        5 => 'overtime_out',
    ],

    // Verification type mapping
    'verification_types' => [
        1 => 'fingerprint',
        2 => 'password',
        3 => 'card',
        4 => 'combination',
        15 => 'face',
        16 => 'vein',
        17 => 'palm',
    ],

    // Logging settings
    'logging' => [
        // Enable detailed logging
        'enabled' => env('ZKTECO_LOGGING_ENABLED', true),
        
        // Log channel to use
        'channel' => env('ZKTECO_LOG_CHANNEL', 'daily'),
        
        // Log level (debug, info, warning, error)
        'level' => env('ZKTECO_LOG_LEVEL', 'info'),
    ],
];