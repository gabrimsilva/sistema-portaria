<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/AuditService.php';

/**
 * Controlador de Ramais
 * Gerencia lista telefônica interna da empresa
 */
class RamaisController {
    private $db;
    private $authService;
    private $auditService;
    
    public function __construct() {
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->auditService = new AuditService();
    }
    
    /**
     * Verificar permissão
     */
    private function checkPermission($permission) {
        if (!$this->authService->hasPermission($permission)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar esta funcionalidade';
            header('Location: /ramais');
            exit;
        }
    }
    
    /**
     * Listar todos os ramais (agrupados por área)
     */
    public function index() {
        try {
            // Buscar todas as áreas distintas
            $areas = $this->db->fetchAll("
                SELECT DISTINCT area 
                FROM ramais 
                WHERE ativo = true
                ORDER BY area ASC
            ");
            
            // Buscar ramais agrupados por área
            $ramaisPorArea = [];
            foreach ($areas as $area) {
                $ramais = $this->db->fetchAll("
                    SELECT id, area, nome, ramal_celular as ramal, tipo, observacoes
                    FROM ramais
                    WHERE area = ? AND ativo = true
                    ORDER BY nome ASC
                ", [$area['area']]);
                
                if (!empty($ramais)) {
                    $ramaisPorArea[$area['area']] = $ramais;
                }
            }
            
            // Estatísticas
            $stats = [
                'total' => $this->db->fetch("SELECT COUNT(*) as count FROM ramais WHERE ativo = true")['count'] ?? 0,
                'internos' => $this->db->fetch("SELECT COUNT(*) as count FROM ramais WHERE ativo = true AND tipo = 'interno'")['count'] ?? 0,
                'externos' => $this->db->fetch("SELECT COUNT(*) as count FROM ramais WHERE ativo = true AND tipo = 'externo'")['count'] ?? 0,
                'areas' => count($areas)
            ];
            
            // Verificar se usuário pode editar (permissão RH)
            $canEdit = $this->authService->hasPermission('ramais.write');
            
            // DEBUG TEMPORÁRIO - Remover depois
            error_log("=== DEBUG RAMAIS ===");
            error_log("User ID: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO'));
            error_log("User Profile: " . ($_SESSION['user_profile'] ?? 'NÃO DEFINIDO'));
            error_log("Can Edit: " . ($canEdit ? 'TRUE' : 'FALSE'));
            error_log("===================");
            
            include __DIR__ . '/../../views/ramais/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao listar ramais: ' . $e->getMessage();
            include __DIR__ . '/../../views/ramais/index.php';
        }
    }
    
    /**
     * Visualização pública (somente leitura)
     */
    public function view() {
        $this->index(); // Mesma interface, mas permissões controlam edição
    }
    
    /**
     * Formulário de cadastro
     */
    public function create() {
        $this->checkPermission('ramais.write');
        
        $ramal = null;
        $error = null;
        
        include __DIR__ . '/../../views/ramais/form.php';
    }
    
    /**
     * Salvar novo ramal
     */
    public function store() {
        $this->checkPermission('ramais.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ramais');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $area = trim($_POST['area'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $ramal = trim($_POST['ramal'] ?? '');
            $tipo = trim($_POST['tipo'] ?? 'interno');
            $observacoes = trim($_POST['observacoes'] ?? '');
            
            // Validações
            if (empty($area) || empty($nome) || empty($ramal)) {
                throw new Exception('Área, nome e ramal são obrigatórios');
            }
            
            // Inserir
            $this->db->query("
                INSERT INTO ramais (area, nome, ramal_celular, tipo, observacoes, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [$area, $nome, $ramal, $tipo, $observacoes ?: null, $_SESSION['user_id'] ?? null]);
            
            // Auditoria
            $this->auditService->log(
                'ramais',
                'create',
                null,
                ['area' => $area, 'nome' => $nome, 'ramal' => $ramal],
                "Cadastrou ramal: $nome ($area)"
            );
            
            $_SESSION['success'] = 'Ramal cadastrado com sucesso!';
            header('Location: /ramais');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao cadastrar ramal: ' . $e->getMessage();
            header('Location: /ramais/novo');
            exit;
        }
    }
    
    /**
     * Formulário de edição
     */
    public function edit() {
        $this->checkPermission('ramais.write');
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /ramais');
            exit;
        }
        
        $ramal = $this->db->fetch("SELECT id, area, nome, ramal_celular as ramal, tipo, observacoes, ativo, created_at, updated_at FROM ramais WHERE id = ?", [$id]);
        
        if (!$ramal) {
            $_SESSION['error'] = 'Ramal não encontrado';
            header('Location: /ramais');
            exit;
        }
        
        $error = null;
        include __DIR__ . '/../../views/ramais/form.php';
    }
    
    /**
     * Atualizar ramal
     */
    public function update() {
        $this->checkPermission('ramais.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ramais');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $id = $_POST['id'] ?? null;
            $area = trim($_POST['area'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $ramal = trim($_POST['ramal'] ?? '');
            $tipo = trim($_POST['tipo'] ?? 'interno');
            $observacoes = trim($_POST['observacoes'] ?? '');
            
            // Validações
            if (empty($id) || empty($area) || empty($nome) || empty($ramal)) {
                throw new Exception('ID, área, nome e ramal são obrigatórios');
            }
            
            // Buscar dados antigos para auditoria
            $ramalAntigo = $this->db->fetch("SELECT * FROM ramais WHERE id = ?", [$id]);
            
            // Atualizar
            $this->db->query("
                UPDATE ramais 
                SET area = ?, nome = ?, ramal_celular = ?, tipo = ?, observacoes = ?, 
                    updated_at = CURRENT_TIMESTAMP, updated_by = ?
                WHERE id = ?
            ", [$area, $nome, $ramal, $tipo, $observacoes ?: null, $_SESSION['user_id'] ?? null, $id]);
            
            // Auditoria
            $this->auditService->log(
                'ramais',
                'update',
                $id,
                ['de' => $ramalAntigo, 'para' => compact('area', 'nome', 'ramal', 'tipo')],
                "Editou ramal: $nome"
            );
            
            $_SESSION['success'] = 'Ramal atualizado com sucesso!';
            header('Location: /ramais');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao atualizar ramal: ' . $e->getMessage();
            header('Location: /ramais/editar?id=' . ($_POST['id'] ?? ''));
            exit;
        }
    }
    
    /**
     * Excluir ramal (soft delete)
     */
    public function delete() {
        $this->checkPermission('ramais.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ramais');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $id = $_POST['id'] ?? null;
            
            if (empty($id)) {
                throw new Exception('ID do ramal é obrigatório');
            }
            
            // Buscar dados para auditoria
            $ramal = $this->db->fetch("SELECT id, area, nome, ramal_celular as ramal, tipo, observacoes, ativo, created_at, updated_at FROM ramais WHERE id = ?", [$id]);
            
            if (!$ramal) {
                throw new Exception('Ramal não encontrado');
            }
            
            // Soft delete
            $this->db->query("
                UPDATE ramais 
                SET ativo = false, updated_at = CURRENT_TIMESTAMP, updated_by = ?
                WHERE id = ?
            ", [$_SESSION['user_id'] ?? null, $id]);
            
            // Auditoria
            $this->auditService->log(
                'ramais',
                'delete',
                $id,
                $ramal,
                "Excluiu ramal: {$ramal['nome']} ({$ramal['area']})"
            );
            
            $_SESSION['success'] = 'Ramal excluído com sucesso!';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao excluir ramal: ' . $e->getMessage();
        }
        
        header('Location: /ramais');
        exit;
    }
    
    /**
     * Página de importação
     */
    public function import() {
        $this->checkPermission('ramais.write');
        
        include __DIR__ . '/../../views/ramais/import.php';
    }
    
    /**
     * Processar importação de Excel/CSV
     */
    public function processImport() {
        $this->checkPermission('ramais.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ramais/importar');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo foi enviado ou ocorreu um erro no upload');
            }
            
            $file = $_FILES['arquivo'];
            $fileName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validar extensão
            if (!in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
                throw new Exception('Formato de arquivo inválido. Use Excel (.xlsx, .xls) ou CSV (.csv)');
            }
            
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            // Carregar arquivo com PhpSpreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Processar linhas
            $importados = 0;
            $erros = [];
            $ignorarPrimeiraLinha = isset($_POST['tem_cabecalho']) && $_POST['tem_cabecalho'] === '1';
            
            $this->db->beginTransaction();
            
            foreach ($rows as $index => $row) {
                // Ignorar cabeçalho
                if ($index === 0 && $ignorarPrimeiraLinha) {
                    continue;
                }
                
                // Ignorar linhas vazias
                if (empty(array_filter($row))) {
                    continue;
                }
                
                try {
                    // Espera-se: Área | Nome | Ramal
                    $area = trim($row[0] ?? '');
                    $nome = trim($row[1] ?? '');
                    $ramal = trim($row[2] ?? '');
                    
                    if (empty($area) || empty($nome) || empty($ramal)) {
                        $erros[] = "Linha " . ($index + 1) . ": Dados incompletos";
                        continue;
                    }
                    
                    // Detectar tipo automaticamente
                    $tipo = (strlen($ramal) == 4 && is_numeric($ramal)) ? 'interno' : 'externo';
                    
                    // Inserir
                    $this->db->query("
                        INSERT INTO ramais (area, nome, ramal_celular, tipo, created_by)
                        VALUES (?, ?, ?, ?, ?)
                    ", [$area, $nome, $ramal, $tipo, $_SESSION['user_id'] ?? null]);
                    
                    $importados++;
                    
                } catch (Exception $e) {
                    $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            // Auditoria
            $this->auditService->log(
                'ramais',
                'import',
                null,
                ['arquivo' => $fileName, 'importados' => $importados, 'erros' => count($erros)],
                "Importou $importados ramais do arquivo $fileName"
            );
            
            if ($importados > 0) {
                $_SESSION['success'] = "Importação concluída! $importados ramais importados.";
                if (!empty($erros)) {
                    $_SESSION['warning'] = count($erros) . " erro(s) encontrado(s): " . implode(', ', array_slice($erros, 0, 5));
                }
            } else {
                $_SESSION['warning'] = 'Nenhum ramal foi importado. Verifique o formato do arquivo.';
            }
            
            header('Location: /ramais');
            exit;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            $_SESSION['error'] = 'Erro na importação: ' . $e->getMessage();
            header('Location: /ramais/importar');
            exit;
        }
    }
    
    /**
     * API: Buscar ramais (para autocomplete/busca)
     */
    public function search() {
        header('Content-Type: application/json');
        
        try {
            $query = trim($_GET['q'] ?? '');
            
            if (empty($query) || strlen($query) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            
            $sql = "SELECT id, area, nome, ramal_celular as ramal, tipo
                    FROM ramais 
                    WHERE ativo = true 
                      AND (nome ILIKE ? OR area ILIKE ? OR ramal_celular ILIKE ?)
                    ORDER BY area ASC, nome ASC
                    LIMIT 20";
            
            $searchTerm = "%{$query}%";
            $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
            
            echo json_encode(['success' => true, 'data' => $results]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar ramais']);
        }
        exit;
    }
}
