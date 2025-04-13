<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier si l'ID du message est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du message manquant']);
    exit;
}

$id_message = cleanInput($_GET['id']);

try {
    // Récupérer les détails du message
    $stmt = $pdo->prepare("
        SELECT * FROM messages_contact 
        WHERE id_message = ?
    ");
    $stmt->execute([$id_message]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message non trouvé');
    }

    // Mettre à jour le statut en "lu" si le message est nouveau
    if ($message['statut'] === 'nouveau') {
        $stmt = $pdo->prepare("
            UPDATE messages_contact 
            SET statut = 'lu' 
            WHERE id_message = ?
        ");
        $stmt->execute([$id_message]);
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 