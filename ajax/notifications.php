<?php
require_once '../includes/config.php';
require_once '../classes/NotificationManager.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$notificationManager = NotificationManager::getInstance($db);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_unread':
        $notifications = $notificationManager->getUnreadNotifications($_SESSION['user_id']);
        echo json_encode(['notifications' => $notifications]);
        break;

    case 'get_all':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $notifications = $notificationManager->getAllNotifications($_SESSION['user_id'], $limit);
        echo json_encode(['notifications' => $notifications]);
        break;

    case 'mark_read':
        if (!isset($_POST['notification_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de notification manquant']);
            exit;
        }
        $success = $notificationManager->markAsRead($_POST['notification_id'], $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;

    case 'delete':
        if (!isset($_POST['notification_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de notification manquant']);
            exit;
        }
        $success = $notificationManager->deleteNotification($_POST['notification_id'], $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action non valide']);
        break;
} 