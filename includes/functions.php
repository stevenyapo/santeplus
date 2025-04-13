<?php
// Fonction pour nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    error_log("=== Debug isLoggedIn ===");
    error_log("Session ID: " . session_id());
    error_log("Session Data: " . print_r($_SESSION, true));
    
    $isLogged = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && 
                isset($_SESSION['role']) && !empty($_SESSION['role']);
    
    error_log("isLoggedIn result: " . ($isLogged ? "true" : "false"));
    return $isLogged;
}

// Fonction pour vérifier le rôle de l'utilisateur
function checkRole($requiredRole) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    error_log("=== Debug checkRole ===");
    error_log("Required Role: " . $requiredRole);
    error_log("Current Role: " . ($_SESSION['role'] ?? 'non défini'));
    error_log("Session Data: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        error_log("Accès non autorisé - Redirection vers login.php");
        $_SESSION['error_message'] = "Accès non autorisé - Veuillez vous connecter en tant qu'" . $requiredRole;
        header('Location: /santeplus/login.php');
        exit();
    }
}
?> 