<?php

class AuditService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Registra uma ação de auditoria
     */
    public function log($acao, $entidade, $entidade_id, $dados_antes = null, $dados_depois = null) {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        try {
            $this->db->query(
                "INSERT INTO audit_log (user_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    $acao,
                    $entidade,
                    $entidade_id,
                    $dados_antes ? json_encode($dados_antes) : null,
                    $dados_depois ? json_encode($dados_depois) : null,
                    $ip_address,
                    $user_agent
                ]
            );
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém o IP real do cliente (considerando proxies)
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Busca logs de auditoria com filtros
     */
    public function getLogs($filtros = []) {
        $sql = "SELECT al.*, u.nome as usuario_nome 
                FROM audit_log al 
                LEFT JOIN usuarios u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filtros['user_id'];
        }
        
        if (!empty($filtros['acao'])) {
            $sql .= " AND al.acao = ?";
            $params[] = $filtros['acao'];
        }
        
        if (!empty($filtros['entidade'])) {
            $sql .= " AND al.entidade = ?";
            $params[] = $filtros['entidade'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND al.timestamp >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND al.timestamp <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $sql .= " ORDER BY al.timestamp DESC";
        
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filtros['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
}