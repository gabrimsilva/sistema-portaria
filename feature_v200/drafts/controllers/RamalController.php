<?php
// ================================================
// CONTROLLER: RAMAIS
// Versão: 2.0.0
// Objetivo: Gerenciar e consultar ramais de profissionais
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para src/ sem aprovação!

class RamalController {
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
     * GET /ramais
     * Página principal de consulta de ramais
     */
    public function index() {
        require_once __DIR__ . '/../../src/services/LayoutService.php';
        require_once __DIR__ . '/../../src/services/NavigationService.php';
        require_once __DIR__ . '/../../views/ramais/index.php';
    }
    
    /**
     * GET /api/ramais/buscar?q=termo
     * Busca ramais por nome, setor ou número
     */
    public function buscar() {
        header('Content-Type: application/json');
        
        try {
            $termo = $_GET['q'] ?? '';
            $setor = $_GET['setor'] ?? null;
            
            if (empty($termo) && empty($setor)) {
                // Retornar todos os ramais (limitado)
                $query = "
                    SELECT 
                        pr.id,
                        pr.nome,
                        pr.setor,
                        pr.empresa,
                        b.ramal,
                        CASE WHEN b.id IS NOT NULL THEN true ELSE false END as is_brigadista
                    FROM profissionais_renner pr
                    LEFT JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                    WHERE b.ramal IS NOT NULL
                    ORDER BY pr.nome
                    LIMIT 50
                ";
                $params = [];
            } else {
                $query = "
                    SELECT 
                        pr.id,
                        pr.nome,
                        pr.setor,
                        pr.empresa,
                        b.ramal,
                        CASE WHEN b.id IS NOT NULL THEN true ELSE false END as is_brigadista
                    FROM profissionais_renner pr
                    LEFT JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                    WHERE b.ramal IS NOT NULL AND (
                        LOWER(pr.nome) LIKE LOWER(?) OR
                        LOWER(pr.setor) LIKE LOWER(?) OR
                        b.ramal LIKE ?
                ";
                
                $params = ["%$termo%", "%$termo%", "%$termo%"];
                
                if ($setor) {
                    $query .= " AND LOWER(pr.setor) = LOWER(?)";
                    $params[] = $setor;
                }
                
                $query .= ") ORDER BY pr.nome LIMIT 50";
            }
            
            $ramais = $this->db->query($query, $params)->fetchAll();
            
            echo json_encode([
                'success' => true,
                'ramais' => $ramais,
                'total' => count($ramais)
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
     * GET /api/ramais/setores
     * Lista setores com ramais cadastrados
     */
    public function setores() {
        header('Content-Type: application/json');
        
        try {
            $setores = $this->db->query("
                SELECT DISTINCT pr.setor
                FROM profissionais_renner pr
                INNER JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                WHERE b.ramal IS NOT NULL AND pr.setor IS NOT NULL
                ORDER BY pr.setor
            ")->fetchAll();
            
            echo json_encode([
                'success' => true,
                'setores' => array_column($setores, 'setor')
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
     * POST /api/ramais/adicionar
     * Adicionar ramal a um profissional
     * 
     * Body: {
     *   "profissional_id": 123,
     *   "ramal": "1234"
     * }
     */
    public function adicionar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            require_once __DIR__ . '/../../src/services/AuthorizationService.php';
            $authService = new AuthorizationService();
            
            if (!$authService->hasPermission('brigada.manage')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Você não tem permissão para gerenciar ramais'
                ]);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $profissionalId = $data['profissional_id'] ?? null;
            $ramal = $data['ramal'] ?? null;
            
            if (!$profissionalId || !$ramal) {
                throw new Exception('Profissional e ramal são obrigatórios');
            }
            
            // Verificar se profissional já está na brigada
            $brigadista = $this->db->query("
                SELECT id FROM brigadistas 
                WHERE professional_id = ? AND active = true
            ", [$profissionalId])->fetch();
            
            if ($brigadista) {
                // Atualizar ramal existente
                $this->db->query("
                    UPDATE brigadistas 
                    SET ramal = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$ramal, $brigadista['id']]);
            } else {
                // Adicionar à brigada com ramal
                $this->db->query("
                    INSERT INTO brigadistas (professional_id, ramal, active, created_at, updated_at)
                    VALUES (?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ", [$profissionalId, $ramal]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal adicionado com sucesso'
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
     * PUT /api/ramais/{profissional_id}
     * Atualizar ramal de um profissional
     */
    public function atualizar($profissionalId) {
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
            
            if (!$authService->hasPermission('brigada.manage')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Você não tem permissão para gerenciar ramais'
                ]);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $ramal = $data['ramal'] ?? null;
            
            if (!$ramal) {
                throw new Exception('Ramal é obrigatório');
            }
            
            $this->db->query("
                UPDATE brigadistas 
                SET ramal = ?, updated_at = CURRENT_TIMESTAMP
                WHERE professional_id = ? AND active = true
            ", [$ramal, $profissionalId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal atualizado com sucesso'
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
     * DELETE /api/ramais/{profissional_id}
     * Remover ramal de um profissional
     */
    public function remover($profissionalId) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            require_once __DIR__ . '/../../src/services/AuthorizationService.php';
            $authService = new AuthorizationService();
            
            if (!$authService->hasPermission('brigada.manage')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Você não tem permissão para gerenciar ramais'
                ]);
                return;
            }
            
            $this->db->query("
                UPDATE brigadistas 
                SET ramal = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE professional_id = ? AND active = true
            ", [$profissionalId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal removido com sucesso'
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
     * GET /api/ramais/export
     * Exportar lista de ramais em CSV
     */
    public function exportar() {
        try {
            require_once __DIR__ . '/../../src/services/AuthorizationService.php';
            $authService = new AuthorizationService();
            
            if (!$authService->hasPermission('relatorios.exportar')) {
                http_response_code(403);
                echo 'Sem permissão para exportar';
                return;
            }
            
            $ramais = $this->db->query("
                SELECT 
                    pr.nome,
                    pr.setor,
                    pr.empresa,
                    b.ramal,
                    CASE WHEN b.id IS NOT NULL THEN 'Sim' ELSE 'Não' END as brigadista
                FROM profissionais_renner pr
                LEFT JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                WHERE b.ramal IS NOT NULL
                ORDER BY pr.nome
            ")->fetchAll();
            
            // Headers para download CSV
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="ramais_' . date('Y-m-d_His') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // BOM para UTF-8 (Excel)
            echo "\xEF\xBB\xBF";
            
            // Cabeçalhos
            echo "Nome;Setor;Empresa;Ramal;Brigadista\n";
            
            // Dados
            foreach ($ramais as $ramal) {
                echo implode(';', [
                    $this->sanitizeForCsv($ramal['nome']),
                    $this->sanitizeForCsv($ramal['setor']),
                    $this->sanitizeForCsv($ramal['empresa']),
                    $this->sanitizeForCsv($ramal['ramal']),
                    $ramal['brigadista']
                ]) . "\n";
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo 'Erro ao exportar: ' . $e->getMessage();
        }
    }
    
    private function sanitizeForCsv($value) {
        if (empty($value)) return '';
        $value = (string)$value;
        
        // Proteção contra CSV Injection
        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r", "\n"])) {
            $value = "'" . $value;
        }
        
        return $value;
    }
}
