<?php
require_once 'Database.php';

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($userId, $title, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $title, $message, $type]);
    }

    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function getNotifications($userId) {
        $query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$notificationId, $userId]);
    }

    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId]);
    }

    public function deleteNotification($notificationId, $userId) {
        $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$notificationId, $userId]);
    }

    public function createNotification($userId, $message, $type = 'info', $link = null) {
        $query = "INSERT INTO notifications (user_id, message, type, link, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $message, $type, $link]);
    }
} 