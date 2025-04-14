<?php
require_once '../config/database.php';
require_once '../classes/Notification.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$notification = new Notification($db);
$count = $notification->getUnreadCount($_SESSION['user_id']);

echo json_encode(['count' => $count]); 