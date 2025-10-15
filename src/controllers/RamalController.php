<?php

require_once __DIR__ . '/../services/AuthorizationService.php';

class RamalController {
    private $db;
    private $authService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
        $this->authService = new AuthorizationService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    public function index() {
        $pageTitle = 'Consulta de Ramais';
        $currentPage = 'ramais';
        
        require_once __DIR__ . '/../services/LayoutService.php';
        require_once __DIR__ . '/../services/NavigationService.php';
        include __DIR__ . '/../../views/ramais/index.php';
    }
    
    public function buscar() {
        header('Content-Type: application/json');
        
        try {
            $termo = $_GET['q'] ?? '';
            $setor = $_GET['setor'] ?? null;
            
            $query = "
                SELECT 
                    r.id as ramal_id,
                    pr.id as professional_id,
                    pr.nome,
                    pr.setor,
                    pr.empresa,
                    r.ramal,
                    r.ramal_direto,
                    r.observacao,
                    CASE WHEN b.id IS NOT NULL THEN true ELSE false END as is_brigadista
                FROM ramais r
                INNER JOIN profissionais_renner pr ON pr.id = r.professional_id
                LEFT JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($termo)) {
                $query .= " AND (
                    LOWER(pr.nome) LIKE LOWER(?) OR
                    LOWER(pr.setor) LIKE LOWER(?) OR
                    r.ramal LIKE ?
                )";
                $params[] = "%$termo%";
                $params[] = "%$termo%";
                $params[] = "%$termo%";
            }
            
            if (!empty($setor)) {
                $query .= " AND pr.setor = ?";
                $params[] = $setor;
            }
            
            $query .= " ORDER BY pr.nome LIMIT 100";
            
            $ramais = $this->db->fetchAll($query, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $ramais
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function setores() {
        header('Content-Type: application/json');
        
        try {
            $setores = $this->db->fetchAll("
                SELECT DISTINCT pr.setor
                FROM ramais r
                INNER JOIN profissionais_renner pr ON pr.id = r.professional_id
                WHERE pr.setor IS NOT NULL
                ORDER BY pr.setor
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $setores
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function adicionar() {
        header('Content-Type: application/json');
        
        if (!$this->authService->hasPermission('brigada.manage')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $professionalId = $_POST['professional_id'] ?? null;
            $ramal = trim($_POST['ramal'] ?? '');
            $ramalDireto = isset($_POST['ramal_direto']) ? (bool)$_POST['ramal_direto'] : false;
            $observacao = trim($_POST['observacao'] ?? '');
            
            if (empty($professionalId) || empty($ramal)) {
                throw new Exception('Profissional e ramal são obrigatórios');
            }
            
            $this->db->query("
                INSERT INTO ramais (professional_id, ramal, ramal_direto, observacao)
                VALUES (?, ?, ?, ?)
            ", [$professionalId, $ramal, $ramalDireto, $observacao]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal adicionado com sucesso'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function editar($id) {
        header('Content-Type: application/json');
        
        if (!$this->authService->hasPermission('brigada.manage')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $data = [];
            parse_str(file_get_contents('php://input'), $data);
            if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = $_POST;
            }
            
            $ramal = trim($data['ramal'] ?? '');
            $ramalDireto = isset($data['ramal_direto']) ? (bool)$data['ramal_direto'] : false;
            $observacao = trim($data['observacao'] ?? '');
            
            if (empty($ramal)) {
                throw new Exception('Ramal é obrigatório');
            }
            
            $this->db->query("
                UPDATE ramais
                SET ramal = ?, ramal_direto = ?, observacao = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$ramal, $ramalDireto, $observacao, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal atualizado com sucesso'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function remover($id) {
        header('Content-Type: application/json');
        
        if (!$this->authService->hasPermission('brigada.manage')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $this->db->query("DELETE FROM ramais WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ramal removido com sucesso'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function exportar() {
        if (!$this->authService->hasPermission('brigada.manage')) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }
        
        try {
            $ramais = $this->db->fetchAll("
                SELECT 
                    pr.nome,
                    pr.setor,
                    pr.empresa,
                    r.ramal,
                    CASE WHEN r.ramal_direto THEN 'Sim' ELSE 'Não' END as ramal_direto,
                    CASE WHEN b.id IS NOT NULL THEN 'Sim' ELSE 'Não' END as brigadista,
                    r.observacao
                FROM ramais r
                INNER JOIN profissionais_renner pr ON pr.id = r.professional_id
                LEFT JOIN brigadistas b ON b.professional_id = pr.id AND b.active = true
                ORDER BY pr.nome
            ");
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="ramais_' . date('Y-m-d_His') . '.csv"');
            
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nome', 'Setor', 'Empresa', 'Ramal', 'Ramal Direto', 'Brigadista', 'Observação'], ';');
            
            foreach ($ramais as $ramal) {
                fputcsv($output, [
                    $ramal['nome'],
                    $ramal['setor'],
                    $ramal['empresa'],
                    $ramal['ramal'],
                    $ramal['ramal_direto'],
                    $ramal['brigadista'],
                    $ramal['observacao'] ?? ''
                ], ';');
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Erro ao exportar: ' . $e->getMessage();
        }
    }
}
