<?php
trait Cacheable {
    protected $cacheManager;
    protected $cachePrefix;
    
    protected function initCacheable() {
        $this->cacheManager = CacheManager::getInstance();
        $this->cachePrefix = strtolower(get_class($this)) . ':';
    }
    
    protected function getCacheKey($key) {
        return $this->cachePrefix . $key;
    }
    
    protected function remember($key, $ttl, $callback) {
        if (!$this->cacheManager->isEnabled()) {
            return $callback();
        }
        
        $cacheKey = $this->getCacheKey($key);
        $cached = $this->cacheManager->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->cacheManager->set($cacheKey, $value, $ttl);
        
        return $value;
    }
    
    protected function forget($key) {
        if (!$this->cacheManager->isEnabled()) {
            return false;
        }
        
        return $this->cacheManager->delete($this->getCacheKey($key));
    }
    
    protected function flush() {
        if (!$this->cacheManager->isEnabled()) {
            return false;
        }
        
        return $this->cacheManager->clear();
    }
} 