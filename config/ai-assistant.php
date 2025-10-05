<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the AI Assistant package.
    | You can customize these settings based on your application needs.
    |
    */

    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    
    'api' => [
        'prefix' => env('AI_API_PREFIX', 'api/ai'),
        'middleware' => ['auth:sanctum', 'ai.security'],
        'rate_limit' => env('AI_RATE_LIMIT', '60,1'),
    ],

    'security' => [
        'token_expiry' => env('AI_TOKEN_EXPIRY', 24), // hours
        'max_requests_per_minute' => env('AI_MAX_REQUESTS', 60),
        'allowed_ips' => env('AI_ALLOWED_IPS', ''), // comma-separated
    ],

    'models' => [
        'exclude' => [
            'User', 'PasswordReset', 'PersonalAccessToken', 'Migration',
            'FailedJob', 'Job', 'Cache', 'Session'
        ],
        'include_only' => [], // If specified, only these models will be included
        'custom_descriptions' => [
            // 'User' => 'User accounts and authentication',
            // 'Project' => 'Investment projects and properties',
        ]
    ],

    'capabilities' => [
        'default' => [
            'can_list' => true,
            'can_search' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ],
        'per_model' => [
            // 'User' => ['can_create' => false, 'can_delete' => false],
            // 'Project' => ['can_create' => true, 'can_update' => true],
        ]
    ],

    'custom_tools' => [
        // [
        //     'name' => 'send_notification',
        //     'description' => 'Send a notification to a user',
        //     'category' => 'communication',
        //     'endpoint' => '/api/notifications/send',
        //     'method' => 'POST',
        //     'parameters' => [
        //         'type' => 'object',
        //         'properties' => [
        //             'user_id' => ['type' => 'string', 'description' => 'User ID'],
        //             'message' => ['type' => 'string', 'description' => 'Notification message'],
        //             'type' => ['type' => 'string', 'description' => 'Notification type']
        //         ],
        //         'required' => ['user_id', 'message']
        //     ]
        // ]
    ],

    'metadata' => [
        'cache_ttl' => env('AI_METADATA_CACHE_TTL', 3600), // seconds
        'auto_refresh' => env('AI_METADATA_AUTO_REFRESH', true),
        'include_relationships' => true,
        'include_scopes' => true,
        'include_accessors' => true,
    ],

    'conversations' => [
        'enabled' => true,
        'max_history' => env('AI_MAX_CONVERSATION_HISTORY', 50),
        'storage_driver' => env('AI_CONVERSATION_STORAGE', 'database'),
    ],

    'vps' => [
        'url' => env('AI_VPS_URL'),
        'websocket_url' => env('AI_WEBSOCKET_URL'),
        'api_key' => env('AI_VPS_API_KEY'),
    ]
];
