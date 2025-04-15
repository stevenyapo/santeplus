<?php
require_once __DIR__ . '/../includes/Cacheable.php';

class User {
    use Cacheable;
    
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        parent::__construct();
    }
    
    public function getActiveUsers() {
        return $this->remember('active_users', 3600, function() {
            $stmt = $this->pdo->query("SELECT * FROM users WHERE status = 'active'");
            return $stmt->fetchAll();
        });
    }
    
    public function getUserById($id) {
        return $this->remember("user:$id", 86400, function() use ($id) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        });
    }
    
    public function updateUser($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['email'], $id]);
        
        // Invalider le cache
        $this->forget("user:$id");
        $this->forget('active_users');
        
        return true;
    }
} 