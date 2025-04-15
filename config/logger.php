<?php
return [
    'log_level' => Logger::INFO,
    'log_path' => __DIR__ . '/../logs/',
    'max_file_size' => 10485760, // 10MB
    'max_files' => 30,
    'date_format' => 'Y-m-d H:i:s',
    'log_types' => [
        'app' => [
            'enabled' => true,
            'path' => 'app/',
            'levels' => ['debug', 'info', 'warning', 'error']
        ],
        'error' => [
            'enabled' => true,
            'path' => 'error/',
            'levels' => ['error']
        ],
        'access' => [
            'enabled' => true,
            'path' => 'access/',
            'levels' => ['info']
        ]
    ]
]; 