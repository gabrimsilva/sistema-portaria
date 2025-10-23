<?php
/**
 * Controller: Pré-Cadastros de Visitantes
 * 
 * Gerencia cadastros de visitantes com validade de 1 ano (reutilizáveis)
 * Separado dos registros de acesso (eventos pontuais de entrada/saída)
 * 
 * @version 2.0.0
 * @status DRAFT - NÃO APLICAR SEM APROVAÇÃO
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/AuditService.php';

class PreCadastrosVisitantesController {
    private $db;
    private $authService;
    private $auditService;
    
    public function __construct() {
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->auditService = new AuditService();
    }
    
    /**
     * Lista de pré-cadastros (página principal)
     */
    public function index() {
        // Verificar permissão
        $this->authService->requirePermission('pre_cadastros.read');
        
        // Buscar estatísticas
        $stats = $this->getStats();
        
        // Renderizar view
        require_once __DIR__ . '/../../views/pre-cadastros/visitantes/index.php';
    }
    
    /**
     * Formulário de novo pré-cadastro
     */
    public function create() {
        $this->authService->requirePermission('pre_cadastros.create');
        
        require_once __DIR__ . '/../../views/pre-cadastros/visitantes/form.php';
    }
    
    /**
     * Salvar novo pré-cadastro
     */
    public function save() {
        $this->authService->requirePermission('pre_cadastros.create');
        
        // Validação CSRF
        require_once __DIR__ . '/../../config/csrf.php';
        CSRFProtection::verifyRequest();
        
        try {
            // Capturar dados do formulário
            $nome = $_POST['nome'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $doc_type = $_POST['doc_type'] ?? 'CPF';
            $doc_number = $_POST['doc_number'] ?? '';
            $doc_country = $_POST['doc_country'] ?? 'Brasil';
            $placa_veiculo = $_POST['placa_veiculo'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;
            
            // Validade: padrão 1 ano a partir de hoje
            $valid_from = $_POST['valid_from'] ?? date('Y-m-d');
            $valid_until = $_POST['valid_until'] ?? null;
            
            // Se valid_until não informado, calcular +1 ano
            if (empty($valid_until)) {
                $dateFrom = new DateTime($valid_from);
                $dateFrom->modify('+1 year');
                $valid_until = $dateFrom->format('Y-m-d');
            }
            
            // Normalização de documento (type-aware)
            $doc_number = $this->normalizeDocument($doc_number, $doc_type);
            
            // Normalização de placa
            if (!empty($placa_veiculo) && strtoupper($placa_veiculo) !== 'APE') {
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
            }
            
            // Validações
            $this->validate($nome, $doc_type, $doc_number, $valid_from, $valid_until);
            
            // Verificar duplicidade
            $this->checkDuplicity($doc_type, $doc_number);
            
            // Inserir no banco
            $sql = "INSERT INTO visitantes_cadastro 
                    (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, 
                     valid_from, valid_until, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    RETURNING id";
            
            $result = $this->db->fetch($sql, [
                $nome, 
                $empresa, 
                $doc_type, 
                $doc_number, 
                $doc_country, 
                $placa_veiculo,
                $valid_from,
                $valid_until,
                $observacoes
            ]);
            
            $cadastro_id = $result['id'] ?? null;
            
            // Auditoria
            $this->auditService->log(
                'create',
                'pre_cadastros_visitantes',
                $cadastro_id,
                null,
                [
                    'nome' => $nome,
                    'doc_type' => $doc_type,
                    'valid_until' => $valid_until
                ]
            );
            
            // Redirect com sucesso
            $_SESSION['flash_success'] = 'Pré-cadastro criado com sucesso! Válido até ' . 
                                          date('d/m/Y', strtotime($valid_until));
            header('Location: /pre-cadastros/visitantes');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/visitantes?action=new');
            exit;
        }
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id) {
        $this->authService->requirePermission('pre_cadastros.update');
        
        // Buscar cadastro
        $cadastro = $this->db->fetch(
            "SELECT * FROM vw_visitantes_cadastro_status WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
        
        if (!$cadastro) {
            $_SESSION['flash_error'] = 'Cadastro não encontrado';
            header('Location: /pre-cadastros/visitantes');
            exit;
        }
        
        require_once __DIR__ . '/../../views/pre-cadastros/visitantes/edit.php';
    }
    
    /**
     * Atualizar pré-cadastro
     */
    public function update() {
        // 🔍 DEBUG: Log de entrada
        error_log("🔍 PreCadastrosVisitantesController::update() - INÍCIO");
        error_log("🔍 POST Data: " . json_encode($_POST));
        
        try {
            $this->authService->requirePermission('pre_cadastros.update');
            error_log("✅ Permissão verificada");
            
            // Validação CSRF
            require_once __DIR__ . '/../../config/csrf.php';
            CSRFProtection::verifyRequest();
            error_log("✅ CSRF validado");
        } catch (Exception $e) {
            error_log("❌ ERRO na validação inicial: " . $e->getMessage());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
            header('Location: /pre-cadastros/visitantes?action=edit&id=' . ($_POST['id'] ?? '0'));
            exit;
        }
        
        try {
            $id = $_POST['id'] ?? null;
            $nome = $_POST['nome'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $doc_type = $_POST['doc_type'] ?? 'CPF';
            $doc_number = $_POST['doc_number'] ?? '';
            $doc_country = $_POST['doc_country'] ?? 'Brasil';
            $placa_veiculo = $_POST['placa_veiculo'] ?? null;
            $valid_from = $_POST['valid_from'] ?? date('Y-m-d');
            $valid_until = $_POST['valid_until'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;
            
            // Normalização
            $doc_number = $this->normalizeDocument($doc_number, $doc_type);
            if (!empty($placa_veiculo) && strtoupper($placa_veiculo) !== 'APE') {
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
            }
            
            // Validações
            $this->validate($nome, $doc_type, $doc_number, $valid_from, $valid_until);
            
            // Verificar duplicidade (exceto o próprio)
            $this->checkDuplicity($doc_type, $doc_number, $id);
            
            // Atualizar
            $sql = "UPDATE visitantes_cadastro 
                    SET nome = ?, empresa = ?, doc_type = ?, doc_number = ?, 
                        doc_country = ?, placa_veiculo = ?, valid_from = ?, 
                        valid_until = ?, observacoes = ?
                    WHERE id = ? AND deleted_at IS NULL";
            
            $this->db->execute($sql, [
                $nome, $empresa, $doc_type, $doc_number, $doc_country,
                $placa_veiculo, $valid_from, $valid_until, $observacoes, $id
            ]);
            
            // Auditoria
            $this->auditService->log(
                'update',
                'pre_cadastros_visitantes',
                $id,
                null,
                ['nome' => $nome, 'doc_type' => $doc_type]
            );
            
            $_SESSION['flash_success'] = 'Pré-cadastro atualizado com sucesso!';
            header('Location: /pre-cadastros/visitantes');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/visitantes?action=edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Desativar pré-cadastro (soft delete)
     */
    public function delete($id) {
        $this->authService->requirePermission('pre_cadastros.delete');
        
        try {
            // Verificar se tem registros vinculados
            $count = $this->db->fetch(
                "SELECT COUNT(*) as total FROM visitantes_registros 
                 WHERE cadastro_id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if ($count['total'] > 0) {
                throw new Exception(
                    "Não é possível excluir este cadastro pois existem {$count['total']} " .
                    "entrada(s) vinculada(s). Desative-o ao invés de excluir."
                );
            }
            
            // Soft delete
            $sql = "UPDATE visitantes_cadastro 
                    SET deleted_at = NOW(), 
                        deletion_reason = 'Excluído pelo usuário',
                        ativo = false
                    WHERE id = ?";
            
            $this->db->execute($sql, [$id]);
            
            // Auditoria
            $this->auditService->log(
                'delete',
                'pre_cadastros_visitantes',
                $id,
                ['nome' => $cadastro['nome']],
                null
            );
            
            $_SESSION['flash_success'] = 'Cadastro excluído com sucesso!';
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /pre-cadastros/visitantes');
        exit;
    }
    
    /**
     * Renovar validade (+1 ano)
     */
    public function renovar($id) {
        $this->authService->requirePermission('pre_cadastros.update');
        
        try {
            // Buscar cadastro atual
            $cadastro = $this->db->fetch(
                "SELECT * FROM visitantes_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if (!$cadastro) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Nova data de validade: hoje + 1 ano
            $newValidFrom = date('Y-m-d');
            $newValidUntil = date('Y-m-d', strtotime('+1 year'));
            
            // Atualizar
            $sql = "UPDATE visitantes_cadastro 
                    SET valid_from = ?, valid_until = ?, ativo = true
                    WHERE id = ?";
            
            $this->db->execute($sql, [$newValidFrom, $newValidUntil, $id]);
            
            // Auditoria
            $this->auditService->log(
                'renovar',
                'pre_cadastros_visitantes',
                $id,
                null,
                ['valid_until' => $newValidUntil]
            );
            
            $_SESSION['flash_success'] = 'Cadastro renovado até ' . 
                                          date('d/m/Y', strtotime($newValidUntil));
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /pre-cadastros/visitantes');
        exit;
    }
    
    /**
     * API: Listar cadastros em JSON (para DataTables)
     */
    public function listJson() {
        $this->authService->requirePermission('pre_cadastros.read');
        
        header('Content-Type: application/json');
        
        try {
            // Filtros
            $status = $_GET['status'] ?? 'all'; // all, valido, expirando, expirado
            $search = $_GET['search'] ?? '';
            
            // Query base
            $sql = "SELECT * FROM vw_visitantes_cadastro_status WHERE 1=1";
            $params = [];
            
            // Filtro por status
            if ($status !== 'all') {
                $sql .= " AND status_validade = ?";
                $params[] = $status;
            }
            
            // Busca por nome/documento
            if (!empty($search)) {
                $sql .= " AND (nome ILIKE ? OR doc_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Ordenação
            $sql .= " ORDER BY created_at DESC";
            
            $cadastros = $this->db->fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $cadastros
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================
    
    /**
     * Normalizar documento (type-aware)
     */
    private function normalizeDocument($doc_number, $doc_type) {
        // Documentos brasileiros: apenas dígitos
        if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
            return preg_replace('/[^0-9]/', '', $doc_number);
        }
        
        // Documentos internacionais: alfanuméricos uppercase
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $doc_number));
    }
    
    /**
     * Validar dados
     */
    private function validate($nome, $doc_type, $doc_number, $valid_from, $valid_until) {
        if (empty($nome)) {
            throw new Exception('Nome é obrigatório');
        }
        
        if (empty($doc_number)) {
            throw new Exception('Número do documento é obrigatório');
        }
        
        // ⚠️ IMPORTANTE: Validar datas apenas se ambas estiverem preenchidas
        if (!empty($valid_until) && !empty($valid_from)) {
            if (strtotime($valid_until) <= strtotime($valid_from)) {
                throw new Exception('Data de fim deve ser posterior à data de início');
            }
        }
    }
    
    /**
     * Verificar duplicidade
     */
    private function checkDuplicity($doc_type, $doc_number, $excludeId = null) {
        $sql = "SELECT id, nome FROM visitantes_cadastro 
                WHERE doc_type = ? AND doc_number = ? 
                  AND ativo = true 
                  AND deleted_at IS NULL";
        
        $params = [$doc_type, $doc_number];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $existing = $this->db->fetch($sql, $params);
        
        if ($existing) {
            throw new Exception(
                "Já existe um cadastro ativo para este documento: {$existing['nome']}"
            );
        }
    }
    
    /**
     * Obter estatísticas
     */
    private function getStats() {
        return [
            'total' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM visitantes_cadastro 
                 WHERE deleted_at IS NULL AND ativo = true"
            )['count'],
            
            'validos' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_visitantes_cadastro_status 
                 WHERE status_validade = 'valido'"
            )['count'],
            
            'expirando' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_visitantes_cadastro_status 
                 WHERE status_validade = 'expirando'"
            )['count'],
            
            'expirados' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_visitantes_cadastro_status 
                 WHERE status_validade = 'expirado'"
            )['count']
        ];
    }
}
