<?php

require_once __DIR__ . '/../services/AuthorizationService.php';

class ImportacaoController {
    private $db;
    private $authService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->checkPermission();
        $this->db = new Database();
        $this->authService = new AuthorizationService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    private function checkPermission() {
        $authService = new AuthorizationService();
        if (!$authService->hasPermission('importacao.visualizar')) {
            http_response_code(403);
            die('Acesso negado. Você não tem permissão para acessar esta página.');
        }
    }
    
    public function index() {
        $action = $_GET['action'] ?? 'index';
        
        switch ($action) {
            case 'upload':
                $this->processUpload();
                break;
            case 'process':
                $this->processImport();
                break;
            default:
                $this->showImportPage();
                break;
        }
    }
    
    private function showImportPage() {
        // Buscar estatísticas de profissionais
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN fre IS NOT NULL THEN 1 END) as com_fre,
                COUNT(CASE WHEN data_admissao IS NOT NULL THEN 1 END) as com_data_admissao
            FROM profissionais_renner
        ");
        
        include '../views/importacao/index.php';
    }
    
    private function processUpload() {
        header('Content-Type: application/json');
        
        try {
            CSRFProtection::verifyRequest();
            
            if (!isset($_FILES['file'])) {
                throw new Exception('Nenhum arquivo foi enviado');
            }
            
            $file = $_FILES['file'];
            
            // Validar arquivo
            $allowedExtensions = ['csv', 'xlsx', 'xls'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Formato de arquivo inválido. Use CSV ou XLSX');
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }
            
            // Processar arquivo temporariamente
            $uploadDir = __DIR__ . '/../../uploads/temp/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $tempFile = $uploadDir . uniqid('import_') . '.' . $fileExtension;
            
            if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                throw new Exception('Erro ao salvar arquivo temporário');
            }
            
            // Retornar informações do arquivo
            echo json_encode([
                'success' => true,
                'message' => 'Arquivo carregado com sucesso',
                'data' => [
                    'temp_file' => basename($tempFile),
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'extension' => $fileExtension
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function processImport() {
        header('Content-Type: application/json');
        
        try {
            CSRFProtection::verifyRequest();
            
            $tempFile = $_POST['temp_file'] ?? '';
            
            if (empty($tempFile)) {
                throw new Exception('Arquivo temporário não especificado');
            }
            
            $uploadDir = __DIR__ . '/../../uploads/temp/';
            $filePath = $uploadDir . basename($tempFile);
            
            if (!file_exists($filePath)) {
                throw new Exception('Arquivo não encontrado');
            }
            
            // TODO: Implementar parser CSV/XLSX e inserção de dados
            // Por enquanto, retornar sucesso simulado
            
            echo json_encode([
                'success' => true,
                'message' => 'Importação concluída',
                'data' => [
                    'imported' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
