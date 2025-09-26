<?php

require_once __DIR__ . '/AuditService.php';

/**
 * Serviço para implementar direitos do titular LGPD
 * Artigos 15-22 da LGPD (Lei 13.709/2018)
 */
class LGPDService {
    private $db;
    private $auditService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
    }
    
    /**
     * DIREITO DE ACESSO (Art. 15, I) 
     * Confirmar a existência de tratamento de dados pessoais
     */
    public function getPersonalDataSummary($cpf = null, $email = null) {
        if (!$cpf && !$email) {
            throw new Exception('CPF ou email é obrigatório para consulta');
        }
        
        $summary = [
            'titular' => null,
            'dados_encontrados' => [],
            'finalidades' => [],
            'tempo_tratamento' => null,
            'compartilhamento' => []
        ];
        
        try {
            // Buscar dados em todas as tabelas
            $tables = [
                'usuarios' => ['nome', 'email', 'role_id', 'ativo', 'data_criacao', 'ultimo_login'],
                'funcionarios' => ['nome', 'cpf', 'cargo', 'email', 'telefone', 'data_admissao', 'ativo'],
                'visitantes_novo' => ['nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor', 'data_entrada'],
                'prestadores_servico' => ['nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor', 'entrada']
            ];
            
            foreach ($tables as $table => $fields) {
                $where = [];
                $params = [];
                
                if ($cpf && in_array('cpf', $fields)) {
                    $where[] = "cpf = ?";
                    $params[] = preg_replace('/\D/', '', $cpf);
                }
                
                if ($email && in_array('email', $fields)) {
                    $where[] = "email = ?";
                    $params[] = $email;
                }
                
                if (empty($where)) continue;
                
                $sql = "SELECT " . implode(', ', $fields) . " FROM {$table} WHERE " . implode(' OR ', $where);
                $records = $this->db->fetchAll($sql, $params);
                
                if (!empty($records)) {
                    $summary['dados_encontrados'][$table] = [
                        'quantidade' => count($records),
                        'campos' => $fields,
                        'registros' => $this->anonymizeForDisplay($records, $table)
                    ];
                    
                    // Identificar titular
                    if (!$summary['titular'] && isset($records[0]['nome'])) {
                        $summary['titular'] = $this->maskPersonalData($records[0]['nome'], 'nome');
                    }
                }
            }
            
            // Definir finalidades baseadas nos dados encontrados
            $summary['finalidades'] = $this->getFinalidades($summary['dados_encontrados']);
            
            // Registrar acesso para auditoria
            $this->auditService->log(
                'lgpd_data_access', 
                'lgpd_request', 
                null, 
                null, 
                ['tipo' => 'consulta_dados', 'cpf_email' => $cpf ?: $email]
            );
            
            return $summary;
            
        } catch (Exception $e) {
            error_log("Erro na consulta LGPD: " . $e->getMessage());
            throw new Exception("Erro ao consultar dados pessoais");
        }
    }
    
    /**
     * DIREITO DE PORTABILIDADE (Art. 18, V)
     * Exportar dados em formato estruturado
     */
    public function exportPersonalData($cpf = null, $email = null, $format = 'json') {
        if (!$cpf && !$email) {
            throw new Exception('CPF ou email é obrigatório para exportação');
        }
        
        $data = [];
        $exportDate = date('Y-m-d H:i:s');
        
        try {
            // Buscar todos os dados do titular
            $tables = [
                'usuarios' => ['id', 'nome', 'email', 'role_id', 'ativo', 'data_criacao', 'ultimo_login'],
                'funcionarios' => ['id', 'nome', 'cpf', 'cargo', 'email', 'telefone', 'data_admissao', 'ativo', 'foto'],
                'visitantes_novo' => ['id', 'nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor', 'data_entrada', 'data_saida'],
                'prestadores_servico' => ['id', 'nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor', 'entrada', 'saida']
            ];
            
            foreach ($tables as $table => $fields) {
                $where = [];
                $params = [];
                
                if ($cpf && in_array('cpf', $fields)) {
                    $where[] = "cpf = ?";
                    $params[] = preg_replace('/\D/', '', $cpf);
                }
                
                if ($email && in_array('email', $fields)) {
                    $where[] = "email = ?";
                    $params[] = $email;
                }
                
                if (empty($where)) continue;
                
                $sql = "SELECT " . implode(', ', $fields) . " FROM {$table} WHERE " . implode(' OR ', $where);
                $records = $this->db->fetchAll($sql, $params);
                
                if (!empty($records)) {
                    $data[$table] = $records;
                }
            }
            
            $export = [
                'metadata' => [
                    'data_exportacao' => $exportDate,
                    'solicitante' => $cpf ?: $email,
                    'formato' => $format,
                    'versao_lgpd' => '1.0'
                ],
                'dados_pessoais' => $data,
                'observacoes' => [
                    'Os dados foram exportados conforme Art. 18, V da LGPD',
                    'Dados sensíveis como senhas não são incluídos na exportação',
                    'Fotos e arquivos devem ser solicitados separadamente'
                ]
            ];
            
            // Registrar exportação para auditoria
            $this->auditService->log(
                'lgpd_data_export', 
                'lgpd_request', 
                null, 
                null, 
                ['tipo' => 'exportacao', 'formato' => $format, 'cpf_email' => $cpf ?: $email]
            );
            
            return $export;
            
        } catch (Exception $e) {
            error_log("Erro na exportação LGPD: " . $e->getMessage());
            throw new Exception("Erro ao exportar dados pessoais");
        }
    }
    
    /**
     * DIREITO DE RETIFICAÇÃO (Art. 18, III)
     * Corrigir dados incompletos, inexatos ou desatualizados
     */
    public function requestDataCorrection($data) {
        $requiredFields = ['cpf_email', 'table', 'field', 'current_value', 'new_value', 'justification'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obrigatório ausente: {$field}");
            }
        }
        
        try {
            // Criar solicitação de retificação
            $requestId = $this->db->fetch("
                INSERT INTO lgpd_requests (tipo, dados_titular, tabela, campo, valor_atual, valor_novo, justificativa, status, data_solicitacao)
                VALUES ('retificacao', ?, ?, ?, ?, ?, ?, 'pendente', CURRENT_TIMESTAMP)
                RETURNING id
            ", [
                $data['cpf_email'],
                $data['table'],
                $data['field'],
                $data['current_value'],
                $data['new_value'],
                $data['justification']
            ])['id'];
            
            // Registrar para auditoria
            $this->auditService->log(
                'lgpd_correction_request', 
                'lgpd_request', 
                $requestId, 
                null, 
                [
                    'tipo' => 'retificacao',
                    'tabela' => $data['table'],
                    'campo' => $data['field'],
                    'cpf_email' => $data['cpf_email']
                ]
            );
            
            return $requestId;
            
        } catch (Exception $e) {
            error_log("Erro na solicitação de retificação LGPD: " . $e->getMessage());
            throw new Exception("Erro ao processar solicitação de retificação");
        }
    }
    
    /**
     * DIREITO DE EXCLUSÃO (Art. 18, VI)
     * Solicitar eliminação dos dados pessoais
     */
    public function requestDataDeletion($cpf_email, $justification, $tables = []) {
        if (empty($cpf_email) || empty($justification)) {
            throw new Exception('CPF/email e justificativa são obrigatórios');
        }
        
        try {
            // Verificar se há dados para excluir
            $dataFound = $this->getPersonalDataSummary($cpf_email, $cpf_email);
            
            if (empty($dataFound['dados_encontrados'])) {
                throw new Exception('Nenhum dado pessoal encontrado para o titular informado');
            }
            
            // Se não especificado, incluir todas as tabelas encontradas
            if (empty($tables)) {
                $tables = array_keys($dataFound['dados_encontrados']);
            }
            
            // Criar solicitação de exclusão
            $requestId = $this->db->fetch("
                INSERT INTO lgpd_requests (tipo, dados_titular, tabelas_afetadas, justificativa, status, data_solicitacao)
                VALUES ('exclusao', ?, ?, ?, 'pendente', CURRENT_TIMESTAMP)
                RETURNING id
            ", [
                $cpf_email,
                json_encode($tables),
                $justification
            ])['id'];
            
            // Registrar para auditoria
            $this->auditService->log(
                'lgpd_deletion_request', 
                'lgpd_request', 
                $requestId, 
                null, 
                [
                    'tipo' => 'exclusao',
                    'tabelas' => $tables,
                    'cpf_email' => $cpf_email
                ]
            );
            
            return $requestId;
            
        } catch (Exception $e) {
            error_log("Erro na solicitação de exclusão LGPD: " . $e->getMessage());
            throw new Exception("Erro ao processar solicitação de exclusão: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar solicitações LGPD pendentes
     */
    public function getPendingRequests($limit = 50) {
        return $this->db->fetchAll("
            SELECT id, tipo, dados_titular, tabela, campo, valor_atual, valor_novo, 
                   tabelas_afetadas, justificativa, status, data_solicitacao, data_processamento
            FROM lgpd_requests 
            WHERE status = 'pendente'
            ORDER BY data_solicitacao DESC
            LIMIT ?
        ", [$limit]);
    }
    
    /**
     * Processar solicitação LGPD (aprovação/rejeição)
     */
    public function processRequest($requestId, $action, $reason = null) {
        if (!in_array($action, ['aprovar', 'rejeitar'])) {
            throw new Exception('Ação deve ser "aprovar" ou "rejeitar"');
        }
        
        try {
            $request = $this->db->fetch("SELECT * FROM lgpd_requests WHERE id = ?", [$requestId]);
            if (!$request) {
                throw new Exception('Solicitação não encontrada');
            }
            
            if ($request['status'] !== 'pendente') {
                throw new Exception('Solicitação já foi processada');
            }
            
            $newStatus = $action === 'aprovar' ? 'aprovada' : 'rejeitada';
            
            // Atualizar status
            $this->db->query("
                UPDATE lgpd_requests 
                SET status = ?, data_processamento = CURRENT_TIMESTAMP, observacoes = ?, processado_por = ?
                WHERE id = ?
            ", [$newStatus, $reason, $_SESSION['user_id'], $requestId]);
            
            // Se aprovada, executar a ação
            if ($action === 'aprovar') {
                $this->executeApprovedRequest($request);
            }
            
            // Registrar para auditoria
            $this->auditService->log(
                'lgpd_request_processed', 
                'lgpd_request', 
                $requestId, 
                ['status' => 'pendente'], 
                ['status' => $newStatus, 'acao' => $action]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao processar solicitação LGPD: " . $e->getMessage());
            throw new Exception("Erro ao processar solicitação: " . $e->getMessage());
        }
    }
    
    /**
     * Executar solicitação aprovada
     */
    private function executeApprovedRequest($request) {
        switch ($request['tipo']) {
            case 'retificacao':
                $this->executeDataCorrection($request);
                break;
                
            case 'exclusao':
                $this->executeDataDeletion($request);
                break;
                
            default:
                throw new Exception('Tipo de solicitação não suportada: ' . $request['tipo']);
        }
    }
    
    /**
     * Executar retificação de dados
     */
    private function executeDataCorrection($request) {
        // Buscar dados atuais
        $currentData = $this->db->fetch(
            "SELECT * FROM {$request['tabela']} WHERE {$request['campo']} = ?", 
            [$request['valor_atual']]
        );
        
        if (!$currentData) {
            throw new Exception('Dados atuais não encontrados para retificação');
        }
        
        // Executar atualização
        $this->db->query(
            "UPDATE {$request['tabela']} SET {$request['campo']} = ? WHERE {$request['campo']} = ?",
            [$request['valor_novo'], $request['valor_atual']]
        );
        
        // Log da execução
        $this->auditService->log(
            'lgpd_correction_executed', 
            $request['tabela'], 
            $currentData['id'] ?? null,
            [$request['campo'] => $request['valor_atual']],
            [$request['campo'] => $request['valor_novo']]
        );
    }
    
    /**
     * Executar exclusão de dados
     */
    private function executeDataDeletion($request) {
        $tables = json_decode($request['tabelas_afetadas'], true);
        
        foreach ($tables as $table) {
            // Buscar registros para log
            $records = $this->findRecordsByTitular($table, $request['dados_titular']);
            
            foreach ($records as $record) {
                // Fazer backup antes da exclusão
                $this->auditService->log(
                    'lgpd_deletion_executed', 
                    $table, 
                    $record['id'] ?? null,
                    $record,
                    null
                );
                
                // Executar exclusão
                $this->db->query("DELETE FROM {$table} WHERE id = ?", [$record['id']]);
            }
        }
    }
    
    /**
     * Buscar registros por titular
     */
    private function findRecordsByTitular($table, $cpf_email) {
        $where = [];
        $params = [];
        
        // Verificar se é CPF ou email
        if (filter_var($cpf_email, FILTER_VALIDATE_EMAIL)) {
            $where[] = "email = ?";
            $params[] = $cpf_email;
        } else {
            $where[] = "cpf = ?";
            $params[] = preg_replace('/\D/', '', $cpf_email);
        }
        
        if (empty($where)) return [];
        
        try {
            return $this->db->fetchAll("SELECT * FROM {$table} WHERE " . implode(' OR ', $where), $params);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Anonimizar dados para exibição
     */
    private function anonymizeForDisplay($records, $table) {
        $anonymized = [];
        
        foreach ($records as $record) {
            $newRecord = [];
            foreach ($record as $field => $value) {
                if (in_array($field, ['cpf', 'email', 'telefone', 'nome'])) {
                    $newRecord[$field] = $this->maskPersonalData($value, $field);
                } elseif (in_array($field, ['senha_hash', 'password'])) {
                    $newRecord[$field] = '[OCULTO]';
                } else {
                    $newRecord[$field] = $value;
                }
            }
            $anonymized[] = $newRecord;
        }
        
        return $anonymized;
    }
    
    /**
     * Mascarar dados pessoais
     */
    private function maskPersonalData($value, $type) {
        if (empty($value)) return $value;
        
        switch ($type) {
            case 'email':
                $parts = explode('@', $value);
                if (count($parts) === 2) {
                    $local = $parts[0];
                    $domain = $parts[1];
                    $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 4)) . substr($local, -2);
                    $domainParts = explode('.', $domain);
                    $maskedDomain = substr($domainParts[0], 0, 2) . '***';
                    return $maskedLocal . '@' . $maskedDomain . '.***';
                }
                return 'e***@***.***';
                
            case 'cpf':
                $digits = preg_replace('/\D/', '', $value);
                if (strlen($digits) === 11) {
                    return substr($digits, 0, 3) . '.***.**' . substr($digits, -2);
                }
                return '***.***.***-**';
                
            case 'nome':
                if (strlen($value) <= 3) return str_repeat('*', strlen($value));
                return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
                
            case 'telefone':
                return '(***) ***-**' . substr($value, -2);
                
            default:
                return '[MASCARADO]';
        }
    }
    
    /**
     * Obter finalidades do tratamento baseado nos dados
     */
    private function getFinalidades($dadosEncontrados) {
        $finalidades = [];
        
        if (isset($dadosEncontrados['usuarios'])) {
            $finalidades[] = 'Controle de acesso ao sistema e autenticação de usuários';
        }
        
        if (isset($dadosEncontrados['funcionarios'])) {
            $finalidades[] = 'Gestão de recursos humanos e controle de acesso físico';
        }
        
        if (isset($dadosEncontrados['visitantes_novo'])) {
            $finalidades[] = 'Controle de acesso de visitantes e segurança patrimonial';
        }
        
        if (isset($dadosEncontrados['prestadores_servico'])) {
            $finalidades[] = 'Gestão de prestadores de serviço e controle de acesso';
        }
        
        return $finalidades;
    }
}