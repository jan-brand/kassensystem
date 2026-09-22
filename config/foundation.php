<?php

return [
    'strict' => (bool) env('FOUNDATION_STRICT', true),
    'default_surface' => env('FOUNDATION_DEFAULT_SURFACE', 'pos'),
    'dashboard' => [
        'enabled' => (bool) env('FOUNDATION_DASHBOARD', true),
        'local_only' => true,
        'path' => '__foundation',
    ],
    'paths' => [
        'modules' => app_path('Modules'),
        'design' => resource_path('design'),
        'pages' => resource_path('pages'),
        'surfaces' => resource_path('surfaces'),
        'navigation' => resource_path('navigation'),
        'permissions' => resource_path('permissions/permissions.json'),
        'history' => base_path('.foundation/history'),
        'generated_docs' => base_path('docs/generated'),
    ],
    'environment' => [
        'template' => '.env.example',
        'targets' => ['.env', '.env.testing'],
    ],
];
