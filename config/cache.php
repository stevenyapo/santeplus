<?php
return [
    'enabled' => true,
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 0,
        'prefix' => 'santeplus:',
        'ttl' => 3600 // Durée de vie par défaut en secondes
    ],
    'default_ttl' => [
        'short' => 300,    // 5 minutes
        'medium' => 3600,  // 1 heure
        'long' => 86400,   // 24 heures
        'very_long' => 604800 // 1 semaine
    ],
    'excluded_paths' => [
        '/admin/',
        '/api/',
        '/auth/'
    ],
    'cacheable_queries' => [
        'SELECT COUNT(*) FROM',
        'SELECT * FROM users WHERE',
        'SELECT * FROM patients WHERE',
        'SELECT * FROM appointments WHERE'
    ]
]; 