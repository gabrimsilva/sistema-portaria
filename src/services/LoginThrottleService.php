<?php

class LoginThrottleService {
    private $db;
    private $maxAttempts = 5;
    private $lockoutDuration = 900; // 15 minutos
    
    public function __construct() {
        $this->db = new Database();
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id SERIAL PRIMARY KEY,
                ip_address INET NOT NULL,
                email VARCHAR(255),
                attempts INTEGER DEFAULT 1,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                locked_until TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Índice para consultas rápidas
        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_email 
            ON login_attempts(ip_address, email)
        ");
    }
    
    /**
     * Verifica se IP/email está bloqueado
     */
    public function isBlocked(string $ipAddress, string $email = null): array {
        // Limpar registros antigos primeiro
        $this->cleanup();
        
        $whereClause = "ip_address = ?";
        $params = [$ipAddress];
        
        if ($email) {
            $whereClause .= " OR email = ?";
            $params[] = $email;
        }
        
        $record = $this->db->fetch("
            SELECT attempts, locked_until, last_attempt 
            FROM login_attempts 
            WHERE ($whereClause) AND locked_until > NOW()
            ORDER BY last_attempt DESC 
            LIMIT 1
        ", $params);
        
        if ($record) {
            return [
                'blocked' => true,
                'attempts' => $record['attempts'],
                'locked_until' => $record['locked_until'],
                'remaining_seconds' => strtotime($record['locked_until']) - time()
            ];
        }
        
        return ['blocked' => false, 'attempts' => 0];
    }
    
    /**
     * Registra tentativa falhada
     */
    public function recordFailedAttempt(string $ipAddress, string $email = null): array {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Buscar registro existente
        $whereClause = "ip_address = ?";
        $params = [$ipAddress];
        
        if ($email) {
            $whereClause .= " AND email = ?";
            $params[] = $email;
        }
        
        $existing = $this->db->fetch("
            SELECT id, attempts, last_attempt 
            FROM login_attempts 
            WHERE $whereClause AND last_attempt > NOW() - INTERVAL '1 hour'
            ORDER BY last_attempt DESC 
            LIMIT 1
        ", $params);
        
        if ($existing) {
            // Incrementar tentativas
            $newAttempts = $existing['attempts'] + 1;
            $lockedUntil = null;
            
            if ($newAttempts >= $this->maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            }
            
            $this->db->query("
                UPDATE login_attempts 
                SET attempts = ?, last_attempt = CURRENT_TIMESTAMP, locked_until = ?
                WHERE id = ?
            ", [$newAttempts, $lockedUntil, $existing['id']]);
            
            return [
                'attempts' => $newAttempts,
                'blocked' => $newAttempts >= $this->maxAttempts,
                'locked_until' => $lockedUntil
            ];
        } else {
            // Criar novo registro
            $this->db->query("
                INSERT INTO login_attempts (ip_address, email, attempts, last_attempt) 
                VALUES (?, ?, 1, CURRENT_TIMESTAMP)
            ", [$ipAddress, $email]);
            
            return [
                'attempts' => 1,
                'blocked' => false,
                'locked_until' => null
            ];
        }
    }
    
    /**
     * Limpa tentativa bem-sucedida
     */
    public function clearAttempts(string $ipAddress, string $email = null): void {
        $whereClause = "ip_address = ?";
        $params = [$ipAddress];
        
        if ($email) {
            $whereClause .= " OR email = ?";
            $params[] = $email;
        }
        
        $this->db->query("DELETE FROM login_attempts WHERE $whereClause", $params);
    }
    
    /**
     * Limpeza de registros antigos
     */
    private function cleanup(): void {
        // Remover registros mais antigos que 24 horas
        $this->db->query("
            DELETE FROM login_attempts 
            WHERE created_at < NOW() - INTERVAL '24 hours'
        ");
    }
    
    /**
     * Obter estatísticas de tentativas
     */
    public function getAttemptStats(string $ipAddress, string $email = null): array {
        $whereClause = "ip_address = ?";
        $params = [$ipAddress];
        
        if ($email) {
            $whereClause .= " AND email = ?";
            $params[] = $email;
        }
        
        $record = $this->db->fetch("
            SELECT attempts, last_attempt, locked_until
            FROM login_attempts 
            WHERE $whereClause AND last_attempt > NOW() - INTERVAL '1 hour'
            ORDER BY last_attempt DESC 
            LIMIT 1
        ", $params);
        
        if (!$record) {
            return [
                'attempts' => 0,
                'remaining_attempts' => $this->maxAttempts,
                'blocked' => false
            ];
        }
        
        return [
            'attempts' => $record['attempts'],
            'remaining_attempts' => max(0, $this->maxAttempts - $record['attempts']),
            'blocked' => $record['locked_until'] && strtotime($record['locked_until']) > time(),
            'locked_until' => $record['locked_until']
        ];
    }
    
    /**
     * Configurar limites customizados
     */
    public function setLimits(int $maxAttempts, int $lockoutDuration): void {
        $this->maxAttempts = $maxAttempts;
        $this->lockoutDuration = $lockoutDuration;
    }
}