<?php
require_once __DIR__ . '/../vendor/autoload.php';

class CacheManager {
    private static $instance = null;
    private $redis;
    private $logger;
    private $enabled;
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->enabled = true;
        
        try {
            $this->redis = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ]);
            
            // Test de connexion
            $this->redis->ping();
        } catch (Exception $e) {
            $this->logger->warning("Redis non disponible, le cache est désactivé: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            $value = $this->redis->get($key);
            return $value ? json_decode($value, true) : null;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération du cache: " . $e->getMessage());
            return null;
        }
    }
    
    public function set($key, $value, $ttl = 3600) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            return $this->redis->setex($key, $ttl, json_encode($value));
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la mise en cache: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($key) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            return $this->redis->del($key);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression du cache: " . $e->getMessage());
            return false;
        }
    }
    
    public function clear() {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            return $this->redis->flushdb();
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du nettoyage du cache: " . $e->getMessage());
            return false;
        }
    }
    
    public function isEnabled() {
        return $this->enabled;
    }
} 