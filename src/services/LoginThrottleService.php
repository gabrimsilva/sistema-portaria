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
        
        // Índices únicos para prevenir race conditions
        $this->db->query("
            CREATE UNIQUE INDEX IF NOT EXISTS uq_login_attempts_ip_email 
            ON login_attempts(ip_address, email) WHERE email IS NOT NULL
        ");
        $this->db->query("
            CREATE UNIQUE INDEX IF NOT EXISTS uq_login_attempts_ip_null 
            ON login_attempts(ip_address) WHERE email IS NULL
        ");
    }
    
    /**
     * Verifica se IP/email está bloqueado
     */
    public function isBlocked(string $ipAddress, string $email = null): array {
        // Limpar registros antigos primeiro
        $this->cleanup();
        
        // Verificar bloqueio específico IP+email (priorizando par específico)
        if ($email) {
            $record = $this->db->fetch("
                SELECT attempts, locked_until, last_attempt 
                FROM login_attempts 
                WHERE ip_address = ? AND email = ? AND locked_until > NOW()
                ORDER BY last_attempt DESC 
                LIMIT 1
            ", [$ipAddress, $email]);
            
            if ($record) {
                return [
                    'blocked' => true,
                    'attempts' => $record['attempts'],
                    'locked_until' => $record['locked_until'],
                    'remaining_seconds' => strtotime($record['locked_until']) - time()
                ];
            }
            
            // Apenas para alta segurança: verificar se esta conta específica está sob ataque distribuído
            // (múltiplos IPs falhando) - aplicar threshold mais alto para evitar DoS cross-IP fácil
            $distributedAttack = $this->db->fetch("
                SELECT COUNT(DISTINCT ip_address) as ip_count, MAX(locked_until) as max_locked
                FROM login_attempts 
                WHERE email = ? AND last_attempt > NOW() - INTERVAL '1 hour' AND attempts >= ?
            ", [$email, $this->maxAttempts - 1]);
            
            if ($distributedAttack && $distributedAttack['ip_count'] >= 3 && 
                $distributedAttack['max_locked'] && strtotime($distributedAttack['max_locked']) > time()) {
                return [
                    'blocked' => true,
                    'attempts' => 999, // Indicador de bloqueio distribuído
                    'locked_until' => $distributedAttack['max_locked'],
                    'remaining_seconds' => strtotime($distributedAttack['max_locked']) - time()
                ];
            }
        } else {
            // Sem email, verificar apenas IP
            $record = $this->db->fetch("
                SELECT attempts, locked_until, last_attempt 
                FROM login_attempts 
                WHERE ip_address = ? AND email IS NULL AND locked_until > NOW()
                ORDER BY last_attempt DESC 
                LIMIT 1
            ", [$ipAddress]);
            
            if ($record) {
                return [
                    'blocked' => true,
                    'attempts' => $record['attempts'],
                    'locked_until' => $record['locked_until'],
                    'remaining_seconds' => strtotime($record['locked_until']) - time()
                ];
            }
        }
        
        return ['blocked' => false, 'attempts' => 0];
    }
    
    /**
     * Registra tentativa falhada (implementação funcional simplificada)
     */
    public function recordFailedAttempt(string $ipAddress, string $email = null): array {
        try {
            // Buscar registro existente (sem filtro de tempo para evitar stale-row bug)
            if ($email) {
                $existing = $this->db->fetch("
                    SELECT id, attempts, last_attempt 
                    FROM login_attempts 
                    WHERE ip_address = ? AND email = ?
                    ORDER BY last_attempt DESC 
                    LIMIT 1
                ", [$ipAddress, $email]);
            } else {
                $existing = $this->db->fetch("
                    SELECT id, attempts, last_attempt 
                    FROM login_attempts 
                    WHERE ip_address = ? AND email IS NULL
                    ORDER BY last_attempt DESC 
                    LIMIT 1
                ", [$ipAddress]);
            }
            
            if ($existing) {
                // Reset se última tentativa foi há mais de 1 hora
                if (strtotime($existing['last_attempt']) < time() - 3600) {
                    $newAttempts = 1;
                } else {
                    $newAttempts = $existing['attempts'] + 1;
                }
                
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
                try {
                    $this->db->query("
                        INSERT INTO login_attempts (ip_address, email, attempts, last_attempt, created_at) 
                        VALUES (?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ", [$ipAddress, $email]);
                    
                    return [
                        'attempts' => 1,
                        'blocked' => false,
                        'locked_until' => null
                    ];
                } catch (Exception $insertEx) {
                    // Se violação de unique constraint, tentar buscar novamente
                    if ($email) {
                        $record = $this->db->fetch("
                            SELECT attempts, locked_until 
                            FROM login_attempts 
                            WHERE ip_address = ? AND email = ?
                        ", [$ipAddress, $email]);
                    } else {
                        $record = $this->db->fetch("
                            SELECT attempts, locked_until 
                            FROM login_attempts 
                            WHERE ip_address = ? AND email IS NULL
                        ", [$ipAddress]);
                    }
                    
                    if ($record) {
                        return [
                            'attempts' => $record['attempts'],
                            'blocked' => $record['locked_until'] && strtotime($record['locked_until']) > time(),
                            'locked_until' => $record['locked_until']
                        ];
                    }
                }
            }
            
            // Fallback final
            return [
                'attempts' => 1,
                'blocked' => false,
                'locked_until' => null
            ];
            
        } catch (Exception $e) {
            error_log("Login throttle error: " . $e->getMessage());
            
            // Fallback seguro
            return [
                'attempts' => 1,
                'blocked' => false,
                'locked_until' => null
            ];
        }
    }
    
    /**
     * Limpa tentativa bem-sucedida (somente para o par IP+email específico)
     */
    public function clearAttempts(string $ipAddress, string $email = null): void {
        if ($email) {
            // Limpar apenas o registro específico do IP+email
            $this->db->query("DELETE FROM login_attempts WHERE ip_address = ? AND email = ?", [$ipAddress, $email]);
        } else {
            // Limpar apenas registros sem email no IP específico
            $this->db->query("DELETE FROM login_attempts WHERE ip_address = ? AND email IS NULL", [$ipAddress]);
        }
    }
    
    /**
     * Limpeza de registros antigos
     */
    private function cleanup(): void {
        // Remover registros sem atividade nas últimas 24 horas
        $this->db->query("
            DELETE FROM login_attempts 
            WHERE last_attempt < NOW() - INTERVAL '24 hours'
        ");
    }
    
    /**
     * Obter estatísticas de tentativas
     */
    public function getAttemptStats(string $ipAddress, string $email = null): array {
        if ($email) {
            $record = $this->db->fetch("
                SELECT attempts, last_attempt, locked_until
                FROM login_attempts 
                WHERE ip_address = ? AND email = ? AND last_attempt > NOW() - INTERVAL '1 hour'
                ORDER BY last_attempt DESC 
                LIMIT 1
            ", [$ipAddress, $email]);
        } else {
            $record = $this->db->fetch("
                SELECT attempts, last_attempt, locked_until
                FROM login_attempts 
                WHERE ip_address = ? AND email IS NULL AND last_attempt > NOW() - INTERVAL '1 hour'
                ORDER BY last_attempt DESC 
                LIMIT 1
            ", [$ipAddress]);
        }
        
        if (!$record) {
            return [
                'attempts' => 0,
                'remaining_attempts' => $this->maxAttempts,
                'blocked' => false,
                'max_attempts' => $this->maxAttempts,
                'lockout_duration' => $this->lockoutDuration
            ];
        }
        
        return [
            'attempts' => $record['attempts'],
            'remaining_attempts' => max(0, $this->maxAttempts - $record['attempts']),
            'blocked' => $record['locked_until'] && strtotime($record['locked_until']) > time(),
            'locked_until' => $record['locked_until'],
            'max_attempts' => $this->maxAttempts,
            'lockout_duration' => $this->lockoutDuration
        ];
    }
    
    /**
     * Configurar limites customizados
     */
    public function setLimits(int $maxAttempts, int $lockoutDuration): void {
        $this->maxAttempts = $maxAttempts;
        $this->lockoutDuration = $lockoutDuration;
    }
    
    /**
     * Obter número máximo de tentativas
     */
    public function getMaxAttempts(): int {
        return $this->maxAttempts;
    }
    
    /**
     * Obter duração do bloqueio em segundos
     */
    public function getLockoutDuration(): int {
        return $this->lockoutDuration;
    }
}