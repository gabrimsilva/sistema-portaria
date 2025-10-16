<?php

class AuditService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Registra uma ação de auditoria com anonimização LGPD
     * 
     * @param string $acao - Ação realizada (create, update, delete, import, etc)
     * @param string $entidade - Tipo de entidade afetada
     * @param int|null $entidade_id - ID da entidade
     * @param array|null $dados_antes - Estado anterior (para updates/deletes)
     * @param array|null $dados_depois - Estado novo (para creates/updates)
     * @param string|null $severidade - Nível do log (DEBUG, INFO, WARN, ERROR, AUDIT) - auto-inferido se null
     * @param string|null $modulo - Módulo origem (sistema, api, import, autenticacao) - auto-inferido se null
     * @param string $resultado - Resultado da operação (success, failure, partial)
     */
    public function log($acao, $entidade, $entidade_id, $dados_antes = null, $dados_depois = null, $severidade = null, $modulo = null, $resultado = 'success') {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $this->anonymizeIP($this->getClientIP());
        $user_agent = $this->anonymizeUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);
        
        // Inferir severidade e módulo se não fornecidos
        $severidade = $severidade ?? $this->inferSeveridade($acao);
        $modulo = $modulo ?? $this->inferModulo($entidade, $acao);
        
        // Extrair campos de entrada retroativa se presentes
        $is_retroactive = false;
        $justificativa = null;
        
        if (is_array($dados_depois)) {
            if (isset($dados_depois['entrada_retroativa']) && $dados_depois['entrada_retroativa']) {
                $is_retroactive = true;
                $justificativa = $dados_depois['justificativa'] ?? null;
            }
        }
        
        // Anonimizar dados pessoais antes de salvar
        $dados_antes_anonimizados = $dados_antes ? $this->anonymizeData($dados_antes, $entidade) : null;
        $dados_depois_anonimizados = $dados_depois ? $this->anonymizeData($dados_depois, $entidade) : null;
        
        try {
            $this->db->query(
                "INSERT INTO audit_log (user_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip_address, user_agent, severidade, modulo, resultado, is_retroactive, justificativa) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    $acao,
                    $entidade,
                    $entidade_id,
                    $dados_antes_anonimizados ? json_encode($dados_antes_anonimizados) : null,
                    $dados_depois_anonimizados ? json_encode($dados_depois_anonimizados) : null,
                    $ip_address,
                    $user_agent,
                    $severidade,
                    $modulo,
                    $resultado,
                    $is_retroactive,
                    $justificativa
                ]
            );
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria: " . $e->getMessage());
        }
    }
    
    /**
     * Infere severidade baseado na ação
     */
    private function inferSeveridade($acao) {
        $severidadeMap = [
            'create' => 'AUDIT',
            'update' => 'AUDIT',
            'delete' => 'WARN',
            'import' => 'AUDIT',
            'export' => 'INFO',
            'login' => 'INFO',
            'logout' => 'INFO',
            'error' => 'ERROR',
            'access_denied' => 'WARN',
            'config_change' => 'WARN'
        ];
        
        return $severidadeMap[$acao] ?? 'INFO';
    }
    
    /**
     * Infere módulo baseado na entidade e ação
     */
    private function inferModulo($entidade, $acao) {
        // Prioridade para ações específicas
        if ($acao === 'import') return 'import';
        if ($acao === 'export') return 'export';
        if (in_array($acao, ['login', 'logout', 'access_denied'])) return 'autenticacao';
        
        // Mapeamento por entidade
        $moduloMap = [
            'usuarios' => 'autenticacao',
            'role_permissions' => 'autenticacao',
            'roles' => 'autenticacao',
            'profissionais_renner' => 'sistema',
            'registro_acesso' => 'sistema',
            'visitantes' => 'sistema',
            'visitantes_novo' => 'sistema',
            'prestadores_servico' => 'sistema',
            'organization_settings' => 'configuracao',
            'sites' => 'configuracao',
            'sectors' => 'configuracao',
            'data_retention_policies' => 'configuracao'
        ];
        
        return $moduloMap[$entidade] ?? 'sistema';
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
        $sql = "SELECT al.*, u.nome as usuario_nome, al.severidade, al.modulo, al.resultado
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
            $params[] = (int)$filtros['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Anonimiza dados pessoais conforme LGPD
     */
    private function anonymizeData($data, $entidade) {
        if (!is_array($data)) {
            return $data;
        }
        
        $anonymized = $data;
        
        // Campos sempre sensíveis (todas entidades)
        $sensitiveFields = [
            'senha_hash', 'password', 'token', 'secret', 'hash',
            'cpf', 'rg', 'documento', 'telefone', 'celular',
            'data_nascimento', 'endereco', 'cep',
            'ultimo_login', 'created_at', 'updated_at'
        ];
        
        // Campos específicos por entidade
        $entitySensitiveFields = [
            'usuarios' => ['nome', 'email'],
            'funcionarios' => ['nome', 'email', 'cargo', 'setor'],
            'visitantes' => ['nome', 'email', 'empresa', 'telefone'],
            'prestadores' => ['nome', 'email', 'empresa', 'cnpj'],
            'prestadores_servico' => ['nome', 'email', 'empresa', 'cnpj'],
            'profissionais_renner' => ['nome', 'email'],
            'registro_acesso' => ['nome']
        ];
        
        // Aplicar anonimização geral
        foreach ($sensitiveFields as $field) {
            if (isset($anonymized[$field])) {
                $anonymized[$field] = '[REMOVIDO-LGPD]';
            }
        }
        
        // Aplicar anonimização específica da entidade
        if (isset($entitySensitiveFields[$entidade])) {
            foreach ($entitySensitiveFields[$entidade] as $field) {
                if (isset($anonymized[$field])) {
                    $anonymized[$field] = $this->maskPersonalData($anonymized[$field], $field);
                }
            }
        }
        
        // Manter apenas campos essenciais para auditoria
        $auditEssentialFields = ['id', 'ativo', 'status', 'role_id', 'perfil', 'permissoes'];
        
        // Campos importantes para identificação em auditoria (mantidos para rastreabilidade)
        $auditIdentificationFields = ['nome', 'setor', 'placa_veiculo', 'tipo', 'empresa', 'company_name', 'observacao', 'entrada_at', 'saida_at', 'retorno', 'saida_final'];
        
        $allEssentialFields = array_merge($auditEssentialFields, $auditIdentificationFields);
        
        return array_intersect_key($anonymized, array_flip($allEssentialFields)) + 
               array_filter($anonymized, function($key) {
                   return strpos($key, '_id') !== false || strpos($key, 'acao') !== false;
               }, ARRAY_FILTER_USE_KEY);
    }
    
    /**
     * Mascara dados pessoais
     */
    private function maskPersonalData($value, $type) {
        if (empty($value)) return $value;
        
        switch ($type) {
            case 'email':
                $parts = explode('@', $value);
                if (count($parts) === 2) {
                    $local = $parts[0];
                    $domain = $parts[1];
                    $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 2)) . substr($local, -1);
                    $domainParts = explode('.', $domain);
                    $maskedDomain = substr($domainParts[0], 0, 1) . '***';
                    return $maskedLocal . '@' . $maskedDomain . '.***';
                }
                return 'e***@***.***';
                
            case 'nome':
                if (strlen($value) <= 2) return str_repeat('*', strlen($value));
                return substr($value, 0, 1) . str_repeat('*', strlen($value) - 2) . substr($value, -1);
                
            case 'telefone':
            case 'celular':
                return '(***) ***-**' . substr($value, -2);
                
            case 'empresa':
                if (strlen($value) <= 3) return str_repeat('*', strlen($value));
                return substr($value, 0, 2) . '***';
                
            default:
                return '[ANONIMIZADO]';
        }
    }
    
    /**
     * Anonimiza endereço IP (remove últimos octetos)
     */
    private function anonymizeIP($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::xxxx';
        }
        
        return 'xxx.xxx.xxx.xxx';
    }
    
    /**
     * Anonimiza User Agent (remove informações específicas)
     */
    private function anonymizeUserAgent($userAgent) {
        if (empty($userAgent)) return null;
        
        // Extrair apenas informações básicas do browser/SO
        $patterns = [
            '/\b[\d\.]+\b/' => 'x.x.x', // Versões específicas
            '/\([^)]*\)/' => '(***)', // Informações de sistema detalhadas
            '/[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}/i' => '[UUID]' // UUIDs
        ];
        
        $anonymized = $userAgent;
        foreach ($patterns as $pattern => $replacement) {
            $anonymized = preg_replace($pattern, $replacement, $anonymized);
        }
        
        return substr($anonymized, 0, 100); // Limitar tamanho
    }
}