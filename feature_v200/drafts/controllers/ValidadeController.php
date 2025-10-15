<?php
// ================================================
// CONTROLLER: VALIDADE DE CADASTROS
// Versão: 2.0.0
// Objetivo: Gerenciar validade de cadastros de visitantes e prestadores
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para src/ sem aprovação!

class ValidadeController {
    private $db;
    
    public function __construct() {
        $this->checkAuthentication();
        require_once __DIR__ . '/../../src/config/database.php';
        $this->db = new Database();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
    }
    
    /**
     * GET /api/cadastros/validade/expirando
     * Cadastros próximos de expirar (7 dias)
     */
    public function expirando() {
        header('Content-Type: application/json');
        
        try {
            $tipo = $_GET['tipo'] ?? null; // 'visitante' ou 'prestador'
            
            $result = [
                'visitantes' => [],
                'prestadores' => []
            ];
            
            if (!$tipo || $tipo === 'visitante') {
                $result['visitantes'] = $this->db->query("
                    SELECT * FROM vw_visitantes_expirando
                ")->fetchAll();
            }
            
            if (!$tipo || $tipo === 'prestador') {
                $result['prestadores'] = $this->db->query("
                    SELECT * FROM vw_prestadores_expirando
                ")->fetchAll();
            }
            
            echo json_encode([
                'success' => true,
                'expirando' => $result,
                'total' => count($result['visitantes']) + count($result['prestadores'])
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
     * GET /api/cadastros/validade/expirados
     * Cadastros já expirados
     */
    public function expirados() {
        header('Content-Type: application/json');
        
        try {
            $tipo = $_GET['tipo'] ?? null;
            $limite = $_GET['limite'] ?? 50;
            
            $result = [
                'visitantes' => [],
                'prestadores' => []
            ];
            
            if (!$tipo || $tipo === 'visitante') {
                $result['visitantes'] = $this->db->query("
                    SELECT id, nome, doc_type, doc_number, empresa, pessoa_visitada,
                           valid_from, data_vencimento AS valid_until, validity_status,
                           DATE_PART('day', CURRENT_TIMESTAMP - data_vencimento) AS dias_expirado
                    FROM visitantes
                    WHERE validity_status = 'expirado'
                    ORDER BY data_vencimento DESC
                    LIMIT ?
                ", [$limite])->fetchAll();
            }
            
            if (!$tipo || $tipo === 'prestador') {
                $result['prestadores'] = $this->db->query("
                    SELECT id, nome, doc_type, doc_number, empresa, setor,
                           valid_from, valid_until, validity_status,
                           DATE_PART('day', CURRENT_TIMESTAMP - valid_until) AS dias_expirado
                    FROM prestadores_servico
                    WHERE validity_status = 'expirado'
                    ORDER BY valid_until DESC
                    LIMIT ?
                ", [$limite])->fetchAll();
            }
            
            echo json_encode([
                'success' => true,
                'expirados' => $result,
                'total' => count($result['visitantes']) + count($result['prestadores'])
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
     * POST /api/cadastros/validade/renovar
     * Renovar validade de um cadastro
     * 
     * Body: {
     *   "tipo": "visitante|prestador",
     *   "id": 123,
     *   "dias": 90
     * }
     */
    public function renovar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $tipo = $data['tipo'] ?? null;
            $id = $data['id'] ?? null;
            $dias = $data['dias'] ?? null;
            
            if (!$tipo || !$id) {
                throw new Exception('Tipo e ID são obrigatórios');
            }
            
            $success = false;
            
            if ($tipo === 'visitante') {
                $dias = $dias ?? 90; // Padrão 90 dias
                $result = $this->db->query("
                    SELECT renovar_cadastro_visitante(?, ?) AS success
                ", [$id, $dias])->fetch();
                $success = $result['success'];
            } elseif ($tipo === 'prestador') {
                $dias = $dias ?? 30; // Padrão 30 dias
                $result = $this->db->query("
                    SELECT renovar_cadastro_prestador(?, ?) AS success
                ", [$id, $dias])->fetch();
                $success = $result['success'];
            } else {
                throw new Exception('Tipo inválido. Use "visitante" ou "prestador"');
            }
            
            if (!$success) {
                throw new Exception('Erro ao renovar cadastro');
            }
            
            // Registrar auditoria
            require_once __DIR__ . '/../../src/services/AuditService.php';
            AuditService::log(
                'renovar_validade',
                $tipo === 'visitante' ? 'visitantes' : 'prestadores_servico',
                $id,
                null,
                ['dias_renovados' => $dias]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro renovado com sucesso',
                'dias_adicionados' => $dias
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
     * POST /api/cadastros/validade/bloquear
     * Bloquear cadastro manualmente
     */
    public function bloquear() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $tipo = $data['tipo'] ?? null;
            $id = $data['id'] ?? null;
            $motivo = $data['motivo'] ?? null;
            
            if (!$tipo || !$id || !$motivo) {
                throw new Exception('Tipo, ID e motivo são obrigatórios');
            }
            
            if ($tipo === 'visitante') {
                $this->db->query("
                    UPDATE visitantes
                    SET validity_status = 'bloqueado',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
            } elseif ($tipo === 'prestador') {
                $this->db->query("
                    UPDATE prestadores_servico
                    SET validity_status = 'bloqueado',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
            } else {
                throw new Exception('Tipo inválido');
            }
            
            // Registrar auditoria
            require_once __DIR__ . '/../../src/services/AuditService.php';
            AuditService::log(
                'bloquear_cadastro',
                $tipo === 'visitante' ? 'visitantes' : 'prestadores_servico',
                $id,
                null,
                ['motivo' => $motivo]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro bloqueado com sucesso'
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
     * POST /api/cadastros/validade/desbloquear
     * Desbloquear cadastro
     */
    public function desbloquear() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $tipo = $data['tipo'] ?? null;
            $id = $data['id'] ?? null;
            
            if (!$tipo || !$id) {
                throw new Exception('Tipo e ID são obrigatórios');
            }
            
            if ($tipo === 'visitante') {
                $this->db->query("
                    UPDATE visitantes
                    SET validity_status = 'ativo',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
            } elseif ($tipo === 'prestador') {
                $this->db->query("
                    UPDATE prestadores_servico
                    SET validity_status = 'ativo',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
            } else {
                throw new Exception('Tipo inválido');
            }
            
            // Registrar auditoria
            require_once __DIR__ . '/../../src/services/AuditService.php';
            AuditService::log(
                'desbloquear_cadastro',
                $tipo === 'visitante' ? 'visitantes' : 'prestadores_servico',
                $id,
                null,
                null
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro desbloqueado com sucesso'
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
     * GET /api/cadastros/validade/configuracoes
     * Obter configurações de validade
     */
    public function configuracoes() {
        header('Content-Type: application/json');
        
        try {
            $configs = $this->db->query("
                SELECT * FROM validity_configurations
                ORDER BY entity_type
            ")->fetchAll();
            
            echo json_encode([
                'success' => true,
                'configuracoes' => $configs
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
     * PUT /api/cadastros/validade/configuracoes
     * Atualizar configurações de validade
     */
    public function atualizarConfiguracoes() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            require_once __DIR__ . '/../../src/services/AuthorizationService.php';
            $authService = new AuthorizationService();
            
            if (!$authService->hasPermission('config.manage')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Sem permissão para alterar configurações'
                ]);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $entityType = $data['entity_type'] ?? null;
            $defaultDays = $data['default_days'] ?? null;
            $autoRenew = $data['auto_renew'] ?? false;
            $notifyDaysBefore = $data['notify_days_before'] ?? 7;
            
            if (!$entityType || !$defaultDays) {
                throw new Exception('entity_type e default_days são obrigatórios');
            }
            
            $this->db->query("
                UPDATE validity_configurations
                SET default_days = ?,
                    auto_renew = ?,
                    notify_days_before = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE entity_type = ?
            ", [$defaultDays, $autoRenew, $notifyDaysBefore, $entityType]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso'
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
