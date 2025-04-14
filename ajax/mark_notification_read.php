<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants ou non autorisé'
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $notificationId = $_POST['notification_id'];
    
    // Vérifier que la notification appartient bien à l'utilisateur
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    
    $success = $stmt->execute([$notificationId, $userId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Notification non trouvée'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la notification'
    ]);
} 