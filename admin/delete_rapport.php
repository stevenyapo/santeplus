<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure uniquement la configuration de la base de données
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID du rapport est fourni
if (!isset($_POST['id_rapport'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du rapport manquant']);
    exit();
}

try {
    // Supprimer le rapport
    $stmt = $pdo->prepare("DELETE FROM rapports_hemodialyse WHERE id_rapport = ?");
    $stmt->execute([$_POST['id_rapport']]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Rapport supprimé avec succès']);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
} 