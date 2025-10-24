<?php
/**
 * Controller: Pré-Cadastros de Prestadores de Serviço
 * 
 * Gerencia cadastros de prestadores com validade de 1 ano (reutilizáveis)
 * Separado dos registros de acesso (eventos pontuais de entrada/saída)
 * 
 * @version 2.0.0
 * @status DRAFT - NÃO APLICAR SEM APROVAÇÃO
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/AuditService.php';

class PreCadastrosPrestadoresController {
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
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/index.php';
    }
    
    /**
     * Formulário de novo pré-cadastro
     */
    public function create() {
        $this->authService->requirePermission('pre_cadastros.create');
        
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/form.php';
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
            $sql = "INSERT INTO prestadores_cadastro 
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
                'pre_cadastros_prestadores',
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
            header('Location: /pre-cadastros/prestadores');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/prestadores?action=new');
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
            "SELECT * FROM vw_prestadores_cadastro_status WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
        
        if (!$cadastro) {
            $_SESSION['flash_error'] = 'Cadastro não encontrado';
            header('Location: /pre-cadastros/prestadores');
            exit;
        }
        
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/edit.php';
    }
    
    /**
     * Atualizar pré-cadastro
     */
    public function update() {
        $this->authService->requirePermission('pre_cadastros.update');
        
        // Validação CSRF
        require_once __DIR__ . '/../../config/csrf.php';
        CSRFProtection::verifyRequest();
        
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
            $sql = "UPDATE prestadores_cadastro 
                    SET nome = ?, empresa = ?, doc_type = ?, doc_number = ?, 
                        doc_country = ?, placa_veiculo = ?, valid_from = ?, 
                        valid_until = ?, observacoes = ?
                    WHERE id = ? AND deleted_at IS NULL";
            
            $this->db->query($sql, [
                $nome, $empresa, $doc_type, $doc_number, $doc_country,
                $placa_veiculo, $valid_from, $valid_until, $observacoes, $id
            ]);
            
            // Auditoria
            $this->auditService->log(
                'update',
                'pre_cadastros_prestadores',
                $id,
                null,
                ['nome' => $nome, 'doc_type' => $doc_type]
            );
            
            $_SESSION['flash_success'] = 'Pré-cadastro atualizado com sucesso!';
            header('Location: /pre-cadastros/prestadores');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/prestadores?action=edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Desativar pré-cadastro (soft delete)
     */
    public function delete($id) {
        file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 🔐 DELETE() START | ID=$id\n", FILE_APPEND);
        
        $this->authService->requirePermission('pre_cadastros.delete');
        file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | ✅ PERMISSÃO OK\n", FILE_APPEND);
        
        // Detectar se é requisição AJAX
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            !empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );
        file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 📡 AJAX=" . ($isAjax ? 'true' : 'false') . "\n", FILE_APPEND);
        
        try {
            // Verificar se tem registros vinculados
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 🔍 CHECANDO REGISTROS VINCULADOS\n", FILE_APPEND);
            $count = $this->db->fetch(
                "SELECT COUNT(*) as total FROM prestadores_registros 
                 WHERE cadastro_id = ?",
                [$id]
            );
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 📊 COUNT=" . ($count['total'] ?? 'NULL') . "\n", FILE_APPEND);
            
            if ($count['total'] > 0) {
                file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | ❌ TEM REGISTROS VINCULADOS\n", FILE_APPEND);
                throw new Exception(
                    "Não é possível excluir este cadastro pois existem {$count['total']} " .
                    "entrada(s) vinculada(s). Desative-o ao invés de excluir."
                );
            }
            
            // Buscar dados antes de deletar (para auditoria)
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 📝 BUSCANDO CADASTRO\n", FILE_APPEND);
            $cadastro = $this->db->fetch(
                "SELECT nome FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            // Soft delete
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 🗑️ EXECUTANDO DELETE\n", FILE_APPEND);
            $sql = "UPDATE prestadores_cadastro 
                    SET deleted_at = NOW(), 
                        ativo = false
                    WHERE id = ?";
            
            $this->db->query($sql, [$id]);
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | ✅ DELETE EXECUTADO\n", FILE_APPEND);
            
            // Auditoria
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 📜 REGISTRANDO AUDITORIA\n", FILE_APPEND);
            $this->auditService->log(
                'delete',
                'pre_cadastros_prestadores',
                $id,
                ['nome' => $cadastro['nome'] ?? 'N/A'],
                null
            );
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | ✅ AUDITORIA OK\n", FILE_APPEND);
            
            // Resposta AJAX ou Redirect
            if ($isAjax) {
                file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 📤 RETORNANDO JSON\n", FILE_APPEND);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Cadastro excluído com sucesso!'
                ]);
                exit;
            }
            
            $_SESSION['flash_success'] = 'Cadastro excluído com sucesso!';
            
        } catch (Exception $e) {
            file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | ❌ EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            // Resposta AJAX ou Redirect
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
            
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        file_put_contents('/tmp/delete-debug.log', date('Y-m-d H:i:s') . " | 🔄 REDIRECT\n", FILE_APPEND);
        header('Location: /pre-cadastros/prestadores');
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
                "SELECT * FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if (!$cadastro) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Nova data de validade: hoje + 1 ano
            $newValidFrom = date('Y-m-d');
            $newValidUntil = date('Y-m-d', strtotime('+1 year'));
            
            // Atualizar
            $sql = "UPDATE prestadores_cadastro 
                    SET valid_from = ?, valid_until = ?, ativo = true
                    WHERE id = ?";
            
            $this->db->query($sql, [$newValidFrom, $newValidUntil, $id]);
            
            // Auditoria
            $this->auditService->log(
                'renovar',
                'pre_cadastros_prestadores',
                $id,
                null,
                ['valid_until' => $newValidUntil]
            );
            
            $_SESSION['flash_success'] = 'Cadastro renovado até ' . 
                                          date('d/m/Y', strtotime($newValidUntil));
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /pre-cadastros/prestadores');
        exit;
    }
    
    /**
     * API: Renovar validade via AJAX (+1 ano)
     */
    public function renovarJson() {
        header('Content-Type: application/json');
        
        try {
            $this->authService->requirePermission('pre_cadastros.update');
            
            // Ler dados JSON da requisição
            $requestBody = file_get_contents('php://input');
            $data = json_decode($requestBody, true);
            $id = $data['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            // Buscar cadastro atual
            $cadastro = $this->db->fetch(
                "SELECT nome FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if (!$cadastro) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Nova data de validade: hoje + 1 ano
            $newValidFrom = date('Y-m-d');
            $newValidUntil = date('Y-m-d', strtotime('+1 year'));
            
            // Atualizar
            $sql = "UPDATE prestadores_cadastro 
                    SET valid_from = ?, valid_until = ?, ativo = true
                    WHERE id = ?";
            
            $this->db->query($sql, [$newValidFrom, $newValidUntil, $id]);
            
            // Auditoria
            $this->auditService->log(
                'renovar',
                'pre_cadastros_prestadores',
                $id,
                null,
                ['valid_until' => $newValidUntil, 'method' => 'ajax']
            );
            
            echo json_encode([
                'success' => true,
                'message' => "Cadastro de {$cadastro['nome']} renovado até " . 
                            date('d/m/Y', strtotime($newValidUntil))
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }
    
    /**
     * API: Obter estatísticas em JSON
     */
    public function statsJson() {
        $this->authService->requirePermission('pre_cadastros.read');
        
        header('Content-Type: application/json');
        
        try {
            // Contar por status
            $stats = [
                'total' => 0,
                'validos' => 0,
                'expirando' => 0,
                'expirados' => 0
            ];
            
            $sql = "SELECT status_validade, COUNT(*) as total 
                    FROM vw_prestadores_cadastro_status 
                    GROUP BY status_validade";
            
            $results = $this->db->fetchAll($sql);
            
            foreach ($results as $row) {
                $status = $row['status_validade'];
                $count = (int)$row['total'];
                
                if ($status === 'valido') {
                    $stats['validos'] = $count;
                } elseif ($status === 'expirando') {
                    $stats['expirando'] = $count;
                } elseif ($status === 'expirado') {
                    $stats['expirados'] = $count;
                }
                
                $stats['total'] += $count;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
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
            $sql = "SELECT * FROM vw_prestadores_cadastro_status WHERE 1=1";
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
        $sql = "SELECT id, nome FROM prestadores_cadastro 
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
                "SELECT COUNT(*) as count FROM prestadores_cadastro 
                 WHERE deleted_at IS NULL AND ativo = true"
            )['count'],
            
            'validos' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_prestadores_cadastro_status 
                 WHERE status_validade = 'valido'"
            )['count'],
            
            'expirando' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_prestadores_cadastro_status 
                 WHERE status_validade = 'expirando'"
            )['count'],
            
            'expirados' => $this->db->fetch(
                "SELECT COUNT(*) as count FROM vw_prestadores_cadastro_status 
                 WHERE status_validade = 'expirado'"
            )['count']
        ];
    }
}
