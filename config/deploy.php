<?php
return [
    // Versions requises
    'php_version' => '7.4.0',
    'mysql_version' => '5.7.0',
    
    // Configuration du dépôt
    'repository' => 'https://github.com/your-username/santeplus.git',
    'branch' => 'main',
    
    // Fichiers à exclure
    'exclude_files' => [
        '.git',
        '.gitignore',
        'README.md',
        'composer.lock',
        'package-lock.json',
        'node_modules',
        'vendor',
        'deploy',
        'backups',
        'logs',
        'cache',
        'tmp',
        'uploads'
    ],
    
    // Espace disque minimum requis (en octets)
    'min_disk_space' => 1024 * 1024 * 100, // 100 Mo
    
    // Configuration des permissions
    'permissions' => [
        'dirs' => [
            'cache' => '0775',
            'logs' => '0775',
            'uploads' => '0775',
            'backups' => '0775'
        ],
        'files' => [
            '*.php' => '0644',
            '*.js' => '0644',
            '*.css' => '0644',
            '*.html' => '0644'
        ]
    ],
    
    // Configuration des dépendances
    'dependencies' => [
        'composer' => true,
        'npm' => false
    ],
    
    // Configuration des migrations
    'migrations' => [
        'enabled' => true,
        'path' => '../sql/migrations',
        'table' => 'migrations'
    ],
    
    // Configuration du rollback
    'rollback' => [
        'enabled' => true,
        'max_attempts' => 3
    ],
    
    // Configuration des notifications
    'notifications' => [
        'enabled' => true,
        'email' => 'admin@example.com',
        'on_success' => true,
        'on_failure' => true
    ],
    
    // Configuration du logging
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'file' => '../logs/deploy.log'
    ]
]; 