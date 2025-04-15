<?php
return [
    // Nombre maximum de backups à conserver
    'max_backups' => 10,
    
    // Tables à exclure du backup
    'exclude_tables' => [
        'system_logs',
        'history_logs'
    ],
    
    // Tables à inclure uniquement (structure sans données)
    'structure_only' => [
        'users',
        'sessions'
    ],
    
    // Options de compression
    'compression' => [
        'enabled' => true,
        'format' => 'zip'
    ],
    
    // Planification des backups
    'schedule' => [
        'daily' => true,
        'weekly' => true,
        'monthly' => true
    ],
    
    // Notification par email
    'notifications' => [
        'enabled' => true,
        'email' => 'admin@example.com',
        'on_success' => true,
        'on_failure' => true
    ],
    
    // Options de stockage
    'storage' => [
        'local' => true,
        'remote' => false,
        'remote_path' => '',
        'remote_credentials' => []
    ]
]; 