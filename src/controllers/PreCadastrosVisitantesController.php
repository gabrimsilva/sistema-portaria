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
            
            // Verificar se é requisição AJAX (para upload de foto)
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax || isset($_GET['ajax'])) {
                // Responder com JSON para upload de foto
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Pré-cadastro criado com sucesso!',
                    'cadastro_id' => $cadastro_id
                ]);
                exit;
            }
            
            // Redirect tradicional
            $_SESSION['flash_success'] = 'Pré-cadastro criado com sucesso! Válido até ' . 
                                          date('d/m/Y', strtotime($valid_until));
            header('Location: /pre-cadastros/visitantes');
            exit;
            
        } catch (Exception $e) {
            // Verificar se é requisição AJAX
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax || isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
            
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
            $sql = "UPDATE visitantes_cadastro 
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
        
        // Detectar se é requisição AJAX
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            !empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );
        
        try {
            // Verificar se tem registros vinculados
            $count = $this->db->fetch(
                "SELECT COUNT(*) as total FROM visitantes_registros 
                 WHERE cadastro_id = ?",
                [$id]
            );
            
            if ($count['total'] > 0) {
                throw new Exception(
                    "Não é possível excluir este cadastro pois existem {$count['total']} " .
                    "entrada(s) vinculada(s). Desative-o ao invés de excluir."
                );
            }
            
            // Buscar dados antes de deletar (para auditoria)
            $cadastro = $this->db->fetch(
                "SELECT nome FROM visitantes_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            // Soft delete
            $sql = "UPDATE visitantes_cadastro 
                    SET deleted_at = NOW(), 
                        ativo = false
                    WHERE id = ?";
            
            $this->db->query($sql, [$id]);
            
            // Auditoria
            $this->auditService->log(
                'delete',
                'pre_cadastros_visitantes',
                $id,
                ['nome' => $cadastro['nome'] ?? 'N/A'],
                null
            );
            
            // Resposta AJAX ou Redirect
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Cadastro excluído com sucesso!'
                ]);
                exit;
            }
            
            $_SESSION['flash_success'] = 'Cadastro excluído com sucesso!';
            
        } catch (Exception $e) {
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
            
            $this->db->query($sql, [$newValidFrom, $newValidUntil, $id]);
            
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
                "SELECT nome FROM visitantes_cadastro WHERE id = ? AND deleted_at IS NULL",
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
            
            $this->db->query($sql, [$newValidFrom, $newValidUntil, $id]);
            
            // Auditoria
            $this->auditService->log(
                'renovar',
                'pre_cadastros_visitantes',
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
                    FROM vw_visitantes_cadastro_status 
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
    
    /**
     * API: Buscar pré-cadastros para autocomplete (Dashboard)
     * GET /api/pre-cadastros/visitantes/search?q=termo
     */
    public function search() {
        header('Content-Type: application/json');
        
        try {
            $query = $_GET['q'] ?? '';
            
            if (strlen($query) < 2) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                exit;
            }
            
            // Buscar apenas cadastros VÁLIDOS e ATIVOS
            $sql = "SELECT 
                        id,
                        nome,
                        empresa,
                        doc_type,
                        doc_number,
                        doc_country,
                        placa_veiculo,
                        valid_until,
                        foto_url
                    FROM visitantes_cadastro
                    WHERE deleted_at IS NULL 
                      AND ativo = true
                      AND valid_until >= CURRENT_DATE
                      AND (
                          nome ILIKE ? 
                          OR doc_number LIKE ?
                          OR empresa ILIKE ?
                      )
                    ORDER BY nome ASC
                    LIMIT 10";
            
            $searchTerm = "%$query%";
            $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $results
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
     * Upload de foto do pré-cadastro
     * POST /pre-cadastros/visitantes?action=upload_foto
     */
    public function uploadFoto() {
        header('Content-Type: application/json');
        
        try {
            // Validação CSRF
            require_once __DIR__ . '/../../config/csrf.php';
            CSRFProtection::verifyRequest();
            
            $cadastro_id = $_POST['cadastro_id'] ?? null;
            $photo_data = $_POST['photo'] ?? null;
            
            if (!$cadastro_id || !$photo_data) {
                throw new Exception('Dados inválidos');
            }
            
            // Verificar se cadastro existe
            $cadastro = $this->db->fetch(
                "SELECT id FROM visitantes_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$cadastro_id]
            );
            
            if (!$cadastro) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Validar base64
            if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $photo_data)) {
                throw new Exception('Formato de imagem inválido');
            }
            
            // Extrair dados da imagem
            list($type, $data) = explode(';', $photo_data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);
            
            // Validar tamanho (2MB)
            if (strlen($data) > 2 * 1024 * 1024) {
                throw new Exception('Imagem muito grande (máximo 2MB)');
            }
            
            // Determinar extensão
            $extension = 'jpg';
            if (strpos($type, 'png') !== false) $extension = 'png';
            if (strpos($type, 'webp') !== false) $extension = 'webp';
            
            // Criar diretório se não existir
            $uploadDir = __DIR__ . '/../../public/uploads/visitantes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Nome do arquivo
            $filename = 'visitante_' . $cadastro_id . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Salvar arquivo
            file_put_contents($filepath, $data);
            
            // Atualizar banco de dados
            $foto_url = '/uploads/visitantes/' . $filename;
            $this->db->query(
                "UPDATE visitantes_cadastro SET foto_url = ? WHERE id = ?",
                [$foto_url, $cadastro_id]
            );
            
            // Auditoria
            $this->auditService->log(
                'upload_foto',
                'pre_cadastros_visitantes',
                $cadastro_id,
                null,
                ['foto_url' => $foto_url]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Foto salva com sucesso',
                'foto_url' => $foto_url
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Buscar visitante por placa de veículo (autocomplete)
     */
    public function searchByPlaca() {
        header('Content-Type: application/json');
        
        try {
            $placa = strtoupper(trim($_GET['placa'] ?? ''));
            
            if (empty($placa) || strlen($placa) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            
            // Buscar últimos cadastros com essa placa
            $sql = "SELECT DISTINCT ON (c.placa_veiculo)
                        c.id as cadastro_id, c.nome, c.empresa, c.doc_type, c.doc_number, c.doc_country,
                        c.placa_veiculo, c.foto_url
                    FROM visitantes_cadastro c
                    WHERE c.placa_veiculo ILIKE ?
                      AND c.placa_veiculo IS NOT NULL 
                      AND c.placa_veiculo != ''
                      AND c.deleted_at IS NULL
                      AND c.ativo = true
                    ORDER BY c.placa_veiculo, c.created_at DESC
                    LIMIT 10";
            
            $searchTerm = "%{$placa}%";
            $results = $this->db->fetchAll($sql, [$searchTerm]);
            
            echo json_encode(['success' => true, 'data' => $results]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar por placa']);
        }
        exit;
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
