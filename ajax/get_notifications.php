<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit;
}

try {
    $notification = new Notification($db);
    $userId = $_SESSION['user_id'];
    
    // Récupérer les notifications
    $notifications = $notification->getNotifications($userId);
    
    // Compter les notifications non lues
    $unreadCount = $notification->getUnreadCount($userId);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des notifications: ' . $e->getMessage()
    ]);
} 