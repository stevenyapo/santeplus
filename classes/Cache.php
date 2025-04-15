<?php

class Cache {
    private static $instance = null;
    private $cacheDir;
    private $defaultTTL = 3600; // 1 heure par défaut
    private $cache = [];
    private $ttl = 3600; // Durée de vie du cache en secondes (1 heure par défaut)

    private function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        if (!isset($this->cache[$key])) {
            return null;
        }

        if (time() > $this->cache[$key]['expires']) {
            unset($this->cache[$key]);
            return null;
        }

        return $this->cache[$key]['value'];
    }

    public function set($key, $value, $ttl = null) {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->ttl)
        ];
        return true;
    }

    public function delete($key) {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            return true;
        }
        return false;
    }

    public function clear() {
        $this->cache = [];
        return true;
    }

    private function getCacheFilePath($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }

    public function setDefaultTTL($ttl) {
        $this->defaultTTL = $ttl;
    }

    public function setTTL($ttl) {
        $this->ttl = $ttl;
        return true;
    }

    // Méthode pour mettre en cache une requête SQL
    public function query($sql, $params = [], $ttl = null) {
        $key = 'sql_' . md5($sql . serialize($params));
        
        $result = $this->get($key);
        if ($result !== null) {
            return $result;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->set($key, $result, $ttl);
            return $result;
        } catch (Exception $e) {
            error_log("Erreur lors de l'exécution de la requête: " . $e->getMessage());
            return null;
        }
    }

    // Méthode pour mettre en cache un graphique
    public function cacheGraph($graphId, $data, $ttl = null) {
        $key = 'graph_' . $graphId;
        return $this->set($key, $data, $ttl);
    }

    // Méthode pour récupérer un graphique en cache
    public function getGraph($graphId) {
        $key = 'graph_' . $graphId;
        return $this->get($key);
    }
} 