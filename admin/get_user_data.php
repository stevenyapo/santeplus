<?php
// Démarrer la session avant toute chose
session_start();

// Définir l'en-tête JSON avant toute sortie
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => 'Accès non autorisé - Veuillez vous connecter en tant qu\'administrateur',
        'debug' => [
            'session_status' => session_status(),
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

try {
    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // Vérifier les paramètres
    if (!isset($_GET['user_id']) || !isset($_GET['user_type'])) {
        throw new Exception('Paramètres manquants dans la requête');
    }

    $user_id = cleanInput($_GET['user_id']);
    $user_type = cleanInput($_GET['user_type']);

    // Vérifier que l'ID et le type ne sont pas vides
    if (empty($user_id) || empty($user_type)) {
        throw new Exception('ID utilisateur ou type utilisateur invalide');
    }

    // Déterminer la table et l'ID en fonction du type d'utilisateur
    $table = match($user_type) {
        'patient' => 'patients',
        'medecin' => 'medecins',
        'secretaire' => 'secretaire',
        default => throw new Exception('Type d\'utilisateur invalide: ' . $user_type)
    };

    $id_field = "id_" . $user_type;

    // Construire la requête en fonction du type d'utilisateur
    if ($user_type === 'patient') {
        $sql = "SELECT nom, prenom, email, telephone, adresse, date_naissance, sexe, groupe_sanguin, allergies, maladies_chroniques 
                FROM $table 
                WHERE $id_field = ?";
    } else if ($user_type === 'medecin') {
        $sql = "SELECT nom, prenom, email, telephone, adresse, specialite 
                FROM $table 
                WHERE $id_field = ?";
    } else {
        $sql = "SELECT nom, prenom, email, telephone 
                FROM $table 
                WHERE $id_field = ?";
    }

    // Vérifier la connexion à la base de données
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête SQL');
    }

    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Aucun utilisateur trouvé avec l\'ID: ' . $user_id);
    }

    // Retourner les données en JSON
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $e->getMessage(),
        'debug' => [
            'user_id' => $_GET['user_id'] ?? 'non défini',
            'user_type' => $_GET['user_type'] ?? 'non défini',
            'session' => [
                'user_id' => $_SESSION['user_id'] ?? 'non défini',
                'role' => $_SESSION['role'] ?? 'non défini'
            ]
        ]
    ]);
}
exit(); 