<?php
// ================================================
// CONTROLLER: ENTRADA RETROATIVA
// Versão: 2.0.0
// Objetivo: Gerenciar entradas retroativas com auditoria completa
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para src/ sem aprovação!

class EntradaRetroativaController {
    private $db;
    private $authService;
    
    public function __construct() {
        $this->checkAuthentication();
        require_once __DIR__ . '/../../src/config/database.php';
        require_once __DIR__ . '/../../src/services/AuthorizationService.php';
        require_once __DIR__ . '/../../src/services/AuditService.php';
        
        $this->db = new Database();
        $this->authService = new AuthorizationService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
    }
    
    private function checkPermission() {
        if (!$this->authService->hasPermission('acesso.retroativo')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para registrar entradas retroativas'
            ]);
            exit;
        }
    }
    
    /**
     * POST /api/profissionais/entrada-retroativa
     * Registra entrada retroativa para profissional
     * 
     * Body: {
     *   "profissional_id": 123,
     *   "data_entrada": "2025-10-10 08:00:00",
     *   "motivo": "Colaborador esqueceu de registrar entrada na segunda-feira"
     * }
     */
    public function registrar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        $this->checkPermission();
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $profissionalId = $data['profissional_id'] ?? null;
            $dataEntrada = $data['data_entrada'] ?? null;
            $motivo = $data['motivo'] ?? null;
            
            // Validações básicas
            if (!$profissionalId || !$dataEntrada || !$motivo) {
                throw new Exception('Profissional, data de entrada e motivo são obrigatórios');
            }
            
            if (strlen(trim($motivo)) < 10) {
                throw new Exception('Motivo deve ter no mínimo 10 caracteres');
            }
            
            // Validar data
            $dataEntradaObj = new DateTime($dataEntrada);
            $agora = new DateTime();
            
            if ($dataEntradaObj >= $agora) {
                throw new Exception('Data de entrada deve ser no passado');
            }
            
            // Executar função do banco que faz todas as validações
            $result = $this->db->query("
                SELECT * FROM registrar_entrada_retroativa_profissional(?, ?, ?, ?, ?, ?)
            ", [
                $profissionalId,
                $dataEntrada,
                $motivo,
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ])->fetch();
            
            if (!$result['sucesso']) {
                throw new Exception($result['mensagem']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Entrada retroativa registrada com sucesso',
                'data' => [
                    'retroativa_id' => $result['retroativa_id'],
                    'registro_acesso_id' => $result['registro_acesso_id']
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/entradas-retroativas
     * Lista todas as entradas retroativas (com filtros)
     */
    public function listar() {
        header('Content-Type: application/json');
        
        $this->checkPermission();
        
        try {
            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d');
            $usuarioId = $_GET['usuario_id'] ?? null;
            $profissionalId = $_GET['profissional_id'] ?? null;
            
            $query = "SELECT * FROM vw_entradas_retroativas WHERE 1=1";
            $params = [];
            
            if ($dataInicio) {
                $query .= " AND DATE(data_registro) >= ?";
                $params[] = $dataInicio;
            }
            
            if ($dataFim) {
                $query .= " AND DATE(data_registro) <= ?";
                $params[] = $dataFim;
            }
            
            if ($usuarioId) {
                $query .= " AND usuario_id = ?";
                $params[] = $usuarioId;
            }
            
            if ($profissionalId && $profissionalId !== 'null') {
                $query .= " AND tabela = 'profissionais_renner' AND registro_id = ?";
                $params[] = $profissionalId;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT 100";
            
            $retroativas = $this->db->query($query, $params)->fetchAll();
            
            echo json_encode([
                'success' => true,
                'retroativas' => $retroativas,
                'total' => count($retroativas)
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/entradas-retroativas/stats
     * Estatísticas de entradas retroativas
     */
    public function estatisticas() {
        header('Content-Type: application/json');
        
        $this->checkPermission();
        
        try {
            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d');
            
            $stats = $this->db->query("
                SELECT * FROM stats_entradas_retroativas(?, ?)
            ", [$dataInicio, $dataFim])->fetch();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * POST /api/entradas-retroativas/{id}/aprovar
     * Aprovar entrada retroativa (se sistema de aprovação estiver ativo)
     */
    public function aprovar($id) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        // Verificar se tem permissão de supervisor
        if (!$this->authService->hasPermission('acesso.aprovar_retroativo')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para aprovar entradas retroativas'
            ]);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $this->db->query("
                UPDATE entradas_retroativas
                SET aprovado_por = ?,
                    aprovado_em = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$_SESSION['user_id'], $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Entrada retroativa aprovada'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
