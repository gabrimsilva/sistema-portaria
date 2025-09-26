<?php

require_once __DIR__ . '/AuditService.php';

/**
 * Serviço para gerenciar política de retenção de dados conforme LGPD
 * Implementa soft delete, anonimização automática e compliance legal
 */
class DataRetentionService {
    private $db;
    private $auditService;
    
    // Whitelist de entidades permitidas para prevenir SQL injection
    private $allowedEntities = [
        'usuarios' => [
            'table' => 'usuarios',
            'fields' => ['nome', 'email', 'ativo'],
            'has_soft_delete' => false, // Usuários não podem ser deletados
            'has_created_at' => true,
            'created_at_field' => 'data_criacao' // Campo customizado
        ],
        'funcionarios' => [
            'table' => 'funcionarios', 
            'fields' => ['nome', 'cpf', 'cargo', 'email', 'telefone', 'data_admissao', 'ativo'],
            'has_soft_delete' => true,
            'has_created_at' => true,
            'created_at_field' => 'data_criacao' // Campo customizado
        ],
        'visitantes_novo' => [
            'table' => 'visitantes_novo',
            'fields' => ['nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor'],
            'has_soft_delete' => true, 
            'has_created_at' => true,
            'created_at_field' => 'created_at' // Campo padrão
        ],
        'prestadores_servico' => [
            'table' => 'prestadores_servico',
            'fields' => ['nome', 'cpf', 'empresa', 'funcionario_responsavel', 'setor'],
            'has_soft_delete' => true,
            'has_created_at' => true,
            'created_at_field' => 'created_at' // Campo padrão
        ]
    ];
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
    }
    
    /**
     * Validar e obter configuração de entidade
     */
    private function validateEntity($entityType) {
        if (!isset($this->allowedEntities[$entityType])) {
            throw new Exception("Tipo de entidade não permitido: {$entityType}");
        }
        
        return $this->allowedEntities[$entityType];
    }
    
    /**
     * Configurar política de retenção para um tipo de entidade
     */
    public function setRetentionPolicy($entityType, $retentionMonths, $anonymizationMonths, $legalBasis, $purpose, $canBeDeleted = true, $notes = null) {
        try {
            // Verificar se já existe política para esta entidade
            $existing = $this->db->fetch(
                "SELECT id FROM data_retention_policies WHERE entity_type = ? AND active = true",
                [$entityType]
            );
            
            if ($existing) {
                // Atualizar política existente
                $this->db->query(
                    "UPDATE data_retention_policies 
                     SET retention_period_months = ?, anonymization_period_months = ?, 
                         legal_basis = ?, purpose = ?, can_be_deleted = ?, notes = ?, 
                         updated_at = CURRENT_TIMESTAMP
                     WHERE entity_type = ? AND active = true",
                    [$retentionMonths, $anonymizationMonths, $legalBasis, $purpose, $canBeDeleted, $notes, $entityType]
                );
                
                $this->auditService->log('update', 'data_retention_policies', $existing['id'], null, [
                    'entity_type' => $entityType,
                    'retention_months' => $retentionMonths,
                    'anonymization_months' => $anonymizationMonths
                ]);
            } else {
                // Criar nova política usando RETURNING para PostgreSQL
                $result = $this->db->fetch(
                    "INSERT INTO data_retention_policies 
                     (entity_type, retention_period_months, anonymization_period_months, legal_basis, purpose, can_be_deleted, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id",
                    [$entityType, $retentionMonths, $anonymizationMonths, $legalBasis, $purpose, $canBeDeleted, $notes]
                );
                
                $policyId = $result['id'];
                $this->auditService->log('create', 'data_retention_policies', $policyId, null, [
                    'entity_type' => $entityType,
                    'retention_months' => $retentionMonths
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao configurar política de retenção: " . $e->getMessage());
            throw new Exception("Falha ao configurar política de retenção: " . $e->getMessage());
        }
    }
    
    /**
     * Obter política de retenção para um tipo de entidade
     */
    public function getRetentionPolicy($entityType) {
        return $this->db->fetch(
            "SELECT * FROM data_retention_policies WHERE entity_type = ? AND active = true",
            [$entityType]
        );
    }
    
    /**
     * Listar todas as políticas de retenção ativas
     */
    public function getAllRetentionPolicies() {
        return $this->db->fetchAll(
            "SELECT * FROM data_retention_policies WHERE active = true ORDER BY entity_type"
        );
    }
    
    /**
     * Realizar soft delete de um registro
     */
    public function softDelete($entityType, $entityId, $reason = 'Política de retenção') {
        $entityConfig = $this->validateEntity($entityType);
        $policy = $this->getRetentionPolicy($entityType);
        
        if (!$policy) {
            throw new Exception("Nenhuma política de retenção configurada para {$entityType}");
        }
        
        if (!$policy['can_be_deleted']) {
            throw new Exception("Entidade {$entityType} não pode ser deletada conforme política");
        }
        
        if (!$entityConfig['has_soft_delete']) {
            throw new Exception("Entidade {$entityType} não suporta soft delete");
        }
        
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Obter dados antes da exclusão para auditoria (sem dados pessoais)
            $dataBeforeDelete = $this->getEntityDataSafe($entityType, $entityId);
            
            if (!$dataBeforeDelete) {
                throw new Exception("Registro não encontrado para exclusão");
            }
            
            // Executar soft delete com query segura
            $table = $entityConfig['table'];
            $this->db->query(
                "UPDATE {$table} SET deleted_at = CURRENT_TIMESTAMP, deletion_reason = ? WHERE id = ?",
                [$reason, $entityId]
            );
            
            // Agendar anonimização futura
            $this->scheduleAnonymization($entityType, $entityId, $policy['anonymization_period_months']);
            
            // Commit transação
            $this->db->commit();
            
            // Log de auditoria (dados já mascarados)
            $this->auditService->log('soft_delete', $entityType, $entityId, $dataBeforeDelete, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deletion_reason' => 'MASKED_REASON'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro no soft delete: " . $e->getMessage());
            throw new Exception("Falha na exclusão lógica: " . $e->getMessage());
        }
    }
    
    /**
     * Restaurar registro que foi soft deleted (apenas se não foi anonimizado)
     */
    public function restoreDeleted($entityType, $entityId, $reason = 'Restauração solicitada') {
        $entityConfig = $this->validateEntity($entityType);
        
        if (!$entityConfig['has_soft_delete']) {
            throw new Exception("Entidade {$entityType} não suporta soft delete");
        }
        
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            $table = $entityConfig['table'];
            
            // Verificar se o registro existe e está deletado, mas não anonimizado
            $record = $this->db->fetch(
                "SELECT deleted_at, anonymized_at FROM {$table} WHERE id = ?",
                [$entityId]
            );
            
            if (!$record) {
                throw new Exception("Registro não encontrado");
            }
            
            if (!$record['deleted_at']) {
                throw new Exception("Registro não está deletado");
            }
            
            if ($record['anonymized_at']) {
                throw new Exception("Registro já foi anonimizado e não pode ser restaurado");
            }
            
            // Restaurar registro
            $this->db->query(
                "UPDATE {$table} SET deleted_at = NULL, deletion_reason = NULL WHERE id = ?",
                [$entityId]
            );
            
            // Cancelar tarefas de anonimização pendentes
            $this->db->query(
                "UPDATE data_retention_tasks 
                 SET status = 'cancelled', executed_at = CURRENT_TIMESTAMP 
                 WHERE entity_type = ? AND entity_id = ? AND task_type = 'anonymize' AND status = 'pending'",
                [$entityType, $entityId]
            );
            
            $this->db->commit();
            
            // Log de auditoria (sem dados pessoais)
            $this->auditService->log('restore', $entityType, $entityId, null, [
                'restored_at' => date('Y-m-d H:i:s'),
                'restoration_reason' => 'MASKED_REASON'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro na restauração: " . $e->getMessage());
            throw new Exception("Falha na restauração: " . $e->getMessage());
        }
    }
    
    /**
     * Anonimizar dados de um registro (irreversível)
     */
    public function anonymizeData($entityType, $entityId, $reason = 'Política de retenção automática') {
        $entityConfig = $this->validateEntity($entityType);
        
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Obter dados seguros antes da anonimização (sem PII)
            $dataBeforeAnonymize = $this->getEntityDataSafe($entityType, $entityId);
            
            if (!$dataBeforeAnonymize) {
                throw new Exception("Registro não encontrado para anonimização");
            }
            
            // Aplicar anonimização baseada no tipo de entidade
            $anonymizedData = $this->generateAnonymizedData($entityType, $entityConfig);
            
            $table = $entityConfig['table'];
            
            // Construir query segura de UPDATE
            $updateFields = [];
            $updateValues = [];
            
            foreach ($anonymizedData as $field => $value) {
                $updateFields[] = "{$field} = ?";
                $updateValues[] = $value;
            }
            
            $updateFields[] = "anonymized_at = CURRENT_TIMESTAMP";
            $updateValues[] = $entityId;
            
            $sql = "UPDATE {$table} SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($sql, $updateValues);
            
            $this->db->commit();
            
            // Log de auditoria (dados já mascarados)
            $this->auditService->log('anonymize', $entityType, $entityId, $dataBeforeAnonymize, [
                'anonymized_at' => date('Y-m-d H:i:s'),
                'anonymization_reason' => 'MASKED_REASON'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro na anonimização: " . $e->getMessage());
            throw new Exception("Falha na anonimização: " . $e->getMessage());
        }
    }
    
    /**
     * Agendar anonimização futura
     */
    private function scheduleAnonymization($entityType, $entityId, $anonymizationMonths) {
        $scheduledFor = date('Y-m-d H:i:s', strtotime("+{$anonymizationMonths} months"));
        
        $this->db->query(
            "INSERT INTO data_retention_tasks (entity_type, entity_id, task_type, scheduled_for, created_by)
             VALUES (?, ?, 'anonymize', ?, ?)",
            [$entityType, $entityId, $scheduledFor, $_SESSION['user_id'] ?? null]
        );
    }
    
    /**
     * Processar tarefas de retenção agendadas (para executar via cron)
     */
    public function processScheduledTasks($limit = 50) {
        // Usar SELECT FOR UPDATE SKIP LOCKED para evitar duplo processamento
        $sql = "SELECT * FROM data_retention_tasks 
                WHERE status = 'pending' AND scheduled_for <= CURRENT_TIMESTAMP 
                ORDER BY scheduled_for ASC";
        
        if ($limit > 0 && $limit <= 1000) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $sql .= " FOR UPDATE SKIP LOCKED";
        
        $processed = 0;
        
        try {
            // Buscar tarefas com lock exclusivo
            $this->db->beginTransaction();
            $tasks = $this->db->fetchAll($sql);
            
            foreach ($tasks as $task) {
                try {
                    // Marcar como processando (já temos o lock)
                    $updateResult = $this->db->query(
                        "UPDATE data_retention_tasks SET status = 'processing' WHERE id = ?",
                        [$task['id']]
                    );
                    
                    // Validar tipo de entidade
                    $this->validateEntity($task['entity_type']);
                    
                    // Executar tarefa SEM iniciar nova transação (usar a existente)
                    switch ($task['task_type']) {
                        case 'anonymize':
                            $this->anonymizeDataWithoutTransaction($task['entity_type'], $task['entity_id'], 'Anonimização automática agendada');
                            break;
                            
                        case 'soft_delete':
                            $this->softDeleteWithoutTransaction($task['entity_type'], $task['entity_id'], 'Exclusão automática agendada');
                            break;
                            
                        default:
                            throw new Exception("Tipo de tarefa desconhecido: " . $task['task_type']);
                    }
                    
                    // Marcar como completada
                    $this->db->query(
                        "UPDATE data_retention_tasks SET status = 'completed', executed_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$task['id']]
                    );
                    
                    $processed++;
                    
                } catch (Exception $e) {
                    // Marcar tarefa específica como falha, mas continuar processando outras
                    $this->db->query(
                        "UPDATE data_retention_tasks SET status = 'failed', executed_at = CURRENT_TIMESTAMP, error_message = ? WHERE id = ?",
                        [$e->getMessage(), $task['id']]
                    );
                    
                    error_log("Erro ao processar tarefa de retenção {$task['id']}: " . $e->getMessage());
                }
            }
            
            $this->db->commit();
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro geral no processamento de tarefas de retenção: " . $e->getMessage());
            throw $e;
        }
        
        return $processed;
    }
    
    /**
     * Obter estatísticas de retenção de dados
     */
    public function getRetentionStatistics() {
        $stats = [];
        
        $policies = $this->getAllRetentionPolicies();
        
        foreach ($policies as $policy) {
            $entityType = $policy['entity_type'];
            
            try {
                $entityConfig = $this->validateEntity($entityType);
                $table = $entityConfig['table'];
                
                $counts = [
                    'active' => 0,
                    'soft_deleted' => 0,
                    'anonymized' => 0,
                    'expired' => 0,
                    'total' => 0
                ];
                
                // Contar registros ativos
                if ($entityConfig['has_soft_delete']) {
                    $active = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table} WHERE deleted_at IS NULL"
                    )['count'] ?? 0;
                    
                    // Contar registros soft deleted
                    $softDeleted = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table} WHERE deleted_at IS NOT NULL AND anonymized_at IS NULL"
                    )['count'] ?? 0;
                    
                    // Contar registros anonimizados
                    $anonymized = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table} WHERE anonymized_at IS NOT NULL"
                    )['count'] ?? 0;
                    
                    $counts['active'] = $active;
                    $counts['soft_deleted'] = $softDeleted;
                    $counts['anonymized'] = $anonymized;
                } else {
                    // Para entidades sem soft delete (como usuários)
                    $total = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table}"
                    )['count'] ?? 0;
                    
                    $anonymized = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table} WHERE anonymized_at IS NOT NULL"
                    )['count'] ?? 0;
                    
                    $counts['active'] = $total - $anonymized;
                    $counts['anonymized'] = $anonymized;
                }
                
                // Contar registros vencidos (que deveriam ser processados)
                if ($entityConfig['has_created_at']) {
                    $retentionDate = date('Y-m-d', strtotime("-{$policy['retention_period_months']} months"));
                    $createdAtField = $entityConfig['created_at_field'];
                    $whereClause = $entityConfig['has_soft_delete'] ? 
                        "{$createdAtField} < ? AND deleted_at IS NULL" : 
                        "{$createdAtField} < ? AND anonymized_at IS NULL";
                        
                    $expired = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM {$table} WHERE {$whereClause}",
                        [$retentionDate]
                    )['count'] ?? 0;
                    
                    $counts['expired'] = $expired;
                }
                
                $counts['total'] = $counts['active'] + $counts['soft_deleted'] + $counts['anonymized'];
                
                $stats[$entityType] = [
                    'policy' => $policy,
                    'counts' => $counts
                ];
                
            } catch (Exception $e) {
                error_log("Erro ao obter estatísticas para {$entityType}: " . $e->getMessage());
                $stats[$entityType] = [
                    'policy' => $policy,
                    'counts' => [
                        'active' => 0,
                        'soft_deleted' => 0,
                        'anonymized' => 0,
                        'expired' => 0,
                        'total' => 0
                    ],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Obter dados de uma entidade (todos os campos)
     */
    private function getEntityData($entityType, $entityId) {
        $entityConfig = $this->validateEntity($entityType);
        $table = $entityConfig['table'];
        
        return $this->db->fetch(
            "SELECT * FROM {$table} WHERE id = ?",
            [$entityId]
        );
    }
    
    /**
     * Obter dados de uma entidade de forma segura (sem dados pessoais)
     */
    private function getEntityDataSafe($entityType, $entityId) {
        $entityConfig = $this->validateEntity($entityType);
        $table = $entityConfig['table'];
        
        // Obter apenas campos não pessoais para auditoria
        $safeFields = ['id', 'ativo'];
        
        // Adicionar campo de criação conforme configuração da entidade
        if ($entityConfig['has_created_at']) {
            $safeFields[] = $entityConfig['created_at_field'];
        }
        
        if ($entityConfig['has_soft_delete']) {
            $safeFields[] = 'deleted_at';
            $safeFields[] = 'anonymized_at'; 
        }
        
        $selectFields = implode(', ', $safeFields);
        
        return $this->db->fetch(
            "SELECT {$selectFields} FROM {$table} WHERE id = ?",
            [$entityId]
        );
    }
    
    /**
     * Gerar dados anonimizados baseados no tipo de entidade
     */
    private function generateAnonymizedData($entityType, $entityConfig) {
        $anonymized = [];
        $timestamp = date('Y');
        
        // Aplicar anonimização apenas aos campos definidos na configuração
        foreach ($entityConfig['fields'] as $field) {
            switch ($field) {
                case 'nome':
                    $anonymized[$field] = "ANONIMIZADO_{$timestamp}";
                    break;
                case 'email':
                    $anonymized[$field] = "anonimizado_" . uniqid() . "@example.com";
                    break;
                case 'telefone':
                    $anonymized[$field] = "(00) 00000-0000";
                    break;
                case 'cpf':
                    $anonymized[$field] = "000.000.000-00";
                    break;
                case 'cargo':
                    $anonymized[$field] = "CARGO_ANONIMIZADO";
                    break;
                case 'empresa':
                    $anonymized[$field] = "EMPRESA_ANONIMIZADA";
                    break;
                case 'funcionario_responsavel':
                    $anonymized[$field] = "RESPONSAVEL_ANONIMIZADO";
                    break;
                case 'setor':
                    $anonymized[$field] = "SETOR_ANONIMIZADO";
                    break;
                // Campos não pessoais permanecem inalterados
                case 'ativo':
                case 'data_admissao':
                    // Não anonimizar estes campos
                    break;
            }
        }
        
        return $anonymized;
    }
    
    /**
     * Anonimizar dados sem iniciar nova transação (para uso em processScheduledTasks)
     */
    private function anonymizeDataWithoutTransaction($entityType, $entityId, $reason = 'Política de retenção automática') {
        $entityConfig = $this->validateEntity($entityType);
        
        // Obter dados seguros antes da anonimização (sem PII)
        $dataBeforeAnonymize = $this->getEntityDataSafe($entityType, $entityId);
        
        if (!$dataBeforeAnonymize) {
            throw new Exception("Registro não encontrado para anonimização");
        }
        
        // Aplicar anonimização baseada no tipo de entidade
        $anonymizedData = $this->generateAnonymizedData($entityType, $entityConfig);
        
        $table = $entityConfig['table'];
        
        // Construir query segura de UPDATE
        $updateFields = [];
        $updateValues = [];
        
        foreach ($anonymizedData as $field => $value) {
            $updateFields[] = "{$field} = ?";
            $updateValues[] = $value;
        }
        
        $updateFields[] = "anonymized_at = CURRENT_TIMESTAMP";
        $updateValues[] = $entityId;
        
        $sql = "UPDATE {$table} SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $updateValues);
        
        // Log de auditoria (dados já mascarados)
        $this->auditService->log('anonymize', $entityType, $entityId, $dataBeforeAnonymize, [
            'anonymized_at' => date('Y-m-d H:i:s'),
            'anonymization_reason' => 'MASKED_REASON'
        ]);
        
        return true;
    }
    
    /**
     * Soft delete sem iniciar nova transação (para uso em processScheduledTasks)
     */
    private function softDeleteWithoutTransaction($entityType, $entityId, $reason = 'Política de retenção') {
        $entityConfig = $this->validateEntity($entityType);
        $policy = $this->getRetentionPolicy($entityType);
        
        if (!$policy) {
            throw new Exception("Nenhuma política de retenção configurada para {$entityType}");
        }
        
        if (!$policy['can_be_deleted']) {
            throw new Exception("Entidade {$entityType} não pode ser deletada conforme política");
        }
        
        if (!$entityConfig['has_soft_delete']) {
            throw new Exception("Entidade {$entityType} não suporta soft delete");
        }
        
        // Obter dados antes da exclusão para auditoria (sem dados pessoais)
        $dataBeforeDelete = $this->getEntityDataSafe($entityType, $entityId);
        
        if (!$dataBeforeDelete) {
            throw new Exception("Registro não encontrado para exclusão");
        }
        
        // Executar soft delete com query segura
        $table = $entityConfig['table'];
        $this->db->query(
            "UPDATE {$table} SET deleted_at = CURRENT_TIMESTAMP, deletion_reason = ? WHERE id = ?",
            [$reason, $entityId]
        );
        
        // Agendar anonimização futura
        $this->scheduleAnonymization($entityType, $entityId, $policy['anonymization_period_months']);
        
        // Log de auditoria (dados já mascarados)
        $this->auditService->log('soft_delete', $entityType, $entityId, $dataBeforeDelete, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deletion_reason' => 'MASKED_REASON'
        ]);
        
        return true;
    }
    
    /**
     * Verificar se um registro pode ser deletado conforme política
     */
    public function canDelete($entityType, $entityId) {
        $policy = $this->getRetentionPolicy($entityType);
        
        if (!$policy || !$policy['can_be_deleted']) {
            return false;
        }
        
        $record = $this->getEntityData($entityType, $entityId);
        
        if (!$record) {
            return false;
        }
        
        // Verificar se já está deletado
        if (isset($record['deleted_at']) && $record['deleted_at']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar registros que precisam ser processados conforme política
     */
    public function getRecordsForProcessing($entityType) {
        $entityConfig = $this->validateEntity($entityType);
        $policy = $this->getRetentionPolicy($entityType);
        
        if (!$policy) {
            return [];
        }
        
        if (!$entityConfig['has_created_at']) {
            return []; // Não pode processar sem created_at
        }
        
        $table = $entityConfig['table'];
        $retentionDate = date('Y-m-d', strtotime("-{$policy['retention_period_months']} months"));
        $createdAtField = $entityConfig['created_at_field'];
        
        // Query segura com campos fixos
        $selectFields = "id, {$createdAtField}";
        
        // Adicionar campo nome se existir na configuração
        if (in_array('nome', $entityConfig['fields'])) {
            $selectFields .= ", nome";
        }
        
        $whereClause = $entityConfig['has_soft_delete'] ? 
            "{$createdAtField} < ? AND deleted_at IS NULL" : 
            "{$createdAtField} < ? AND anonymized_at IS NULL";
        
        return $this->db->fetchAll(
            "SELECT {$selectFields} FROM {$table} 
             WHERE {$whereClause}
             ORDER BY created_at ASC",
            [$retentionDate]
        );
    }
}