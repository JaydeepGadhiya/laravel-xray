<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Define the directories that Laravel X-Ray should scan when analyzing
    | your application architecture. Each key maps to an analyzer type.
    |
    */
    'paths' => [
        'controllers' => app_path('Http/Controllers'),
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        'repositories' => app_path('Repositories'),
        'views' => resource_path('views'),
        'routes' => base_path('routes'),
        'middleware' => app_path('Http/Middleware'),
        'form_requests' => app_path('Http/Requests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The directory where X-Ray will store generated reports, dependency
    | graphs, and analysis results.
    |
    */
    'output_path' => storage_path('app/project-xray'),

    /*
    |--------------------------------------------------------------------------
    | Ignored Files
    |--------------------------------------------------------------------------
    |
    | Files listed here will be excluded from analysis. Use the filename
    | (not the full path) to match files in any scanned directory.
    |
    */
    'ignore' => [
        'Controller.php',
    ],
];
