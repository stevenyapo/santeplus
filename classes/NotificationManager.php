<?php

class NotificationManager {
    private static $instance = null;
    private $db;
    private $logger;

    private function __construct($db) {
        $this->db = $db;
        $this->logger = Logger::getInstance($db);
    }

    public static function getInstance($db) {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    public function createNotification($userId, $title, $message, $type = 'info', $link = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications 
                (user_id, title, message, type, link, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $stmt->execute([$userId, $title, $message, $type, $link]);
            
            $this->logger->info("Notification créée", [
                'user_id' => $userId,
                'title' => $title,
                'type' => $type
            ]);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la création de la notification", [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$notificationId, $userId]);
            
            $this->logger->info("Notification marquée comme lue", [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du marquage de la notification comme lue", [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }

    public function getUnreadNotifications($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des notifications non lues", [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return [];
        }
    }

    public function getAllNotifications($userId, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des notifications", [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return [];
        }
    }

    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$notificationId, $userId]);
            
            $this->logger->info("Notification supprimée", [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression de la notification", [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }
} 