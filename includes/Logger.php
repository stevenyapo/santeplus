<?php
class Logger {
    private static $instance = null;
    private $logPath;
    private $logLevel;
    
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../logs/';
        $this->logLevel = self::INFO; // Niveau par défaut
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setLogLevel($level) {
        $this->logLevel = $level;
    }
    
    private function writeLog($level, $message, $context = [], $type = 'app') {
        if ($level < $this->logLevel) {
            return;
        }
        
        $logLevels = [
            self::DEBUG => 'DEBUG',
            self::INFO => 'INFO',
            self::WARNING => 'WARNING',
            self::ERROR => 'ERROR'
        ];
        
        $timestamp = date('Y-m-d H:i:s');
        $logLevel = $logLevels[$level];
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logMessage = "[$timestamp] [$logLevel] $message $contextStr" . PHP_EOL;
        
        $logFile = $this->logPath . $type . '/' . date('Y-m-d') . '.log';
        
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    public function debug($message, $context = []) {
        $this->writeLog(self::DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->writeLog(self::INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->writeLog(self::WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->writeLog(self::ERROR, $message, $context, 'error');
    }
    
    public function access($message, $context = []) {
        $this->writeLog(self::INFO, $message, $context, 'access');
    }
    
    public static function logException($e) {
        if ($e instanceof \Exception || $e instanceof \Error) {
            $message = sprintf(
                "Exception: %s\nFile: %s\nLine: %d\nTrace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            self::log($message, 'ERROR');
        }
    }
} 