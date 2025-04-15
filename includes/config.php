<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'santeplus');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('APP_NAME', 'SantePlus');
define('APP_URL', 'http://localhost/santeplus');

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 en production avec HTTPS

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données.");
}

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log'); 