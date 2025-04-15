<?php
/**
 * Fichier d'initialisation centrale pour l'application SantéPlus
 * Ce fichier gère la session, les inclusions et les fonctions communes
 */

require_once __DIR__ . '/Logger.php';

// Initialisation du logger
$logger = Logger::getInstance();

// Configuration du niveau de log en fonction de l'environnement
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    $logger->setLogLevel(Logger::DEBUG);
} else {
    $logger->setLogLevel(Logger::INFO);
}

// Log de l'accès à la page
$logger->access("Accès à la page: {$_SERVER['REQUEST_URI']}", [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'method' => $_SERVER['REQUEST_METHOD']
]);

// Gestion des erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logger) {
    $logger->error($errstr, [
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
    return false;
});

// Gestion des exceptions
set_exception_handler(function($exception) use ($logger) {
    $logger->logException($exception);
    // Afficher une page d'erreur personnalisée
    include __DIR__ . '/../templates/error.php';
    exit;
});

// Configuration de la base de données
$host = 'localhost';
$dbname = 'santeplus';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Configuration de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le timeout de session (10 minutes)
$timeout = 600;

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    // Vérifier si le timestamp de dernière activité existe
    if (isset($_SESSION['last_activity'])) {
        // Calculer le temps d'inactivité
        $inactive_time = time() - $_SESSION['last_activity'];
        
        // Si le temps d'inactivité dépasse le timeout
        if ($inactive_time > $timeout) {
            // Détruire la session
            session_destroy();
            // Redémarrer la session pour stocker le message d'erreur
            session_start();
            $_SESSION['error_message'] = "Votre session a expiré. Veuillez vous reconnecter.";
            // Rediriger vers la page de connexion
            header('Location: /santeplus/login.php');
            exit();
        }
    }
    
    // Mettre à jour le timestamp de dernière activité
    $_SESSION['last_activity'] = time();
    
    // Vérifier si l'utilisateur doit changer son mot de passe
    if (isset($_SESSION['change_password']) && $_SESSION['change_password'] === true) {
        // Rediriger vers la page de changement de mot de passe si ce n'est pas déjà la page actuelle
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'change_password.php') {
            header('Location: /santeplus/change_password.php');
            exit();
        }
    }
}

// Fonction pour compter les notifications non lues
function getUnreadMessagesCount($pdo, $user_id, $user_role) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM messages_internes 
        WHERE destinataire_id = ? 
        AND destinataire_role = ? 
        AND lu = FALSE 
        AND supprime_destinataire = FALSE
    ");
    $stmt->execute([$user_id, $user_role]);
    return $stmt->fetchColumn();
}

// Récupérer le nombre de notifications si l'utilisateur est connecté
$notifications_count = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $notifications_count = getUnreadMessagesCount($pdo, $_SESSION['user_id'], $_SESSION['user_type']);
}

// Définir la base URL
$base_url = '/santeplus';

// Fonction pour générer une URL absolue
function url($path = '') {
    global $base_url;
    return $base_url . '/' . ltrim($path, '/');
}
?> 