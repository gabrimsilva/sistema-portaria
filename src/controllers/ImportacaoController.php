<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportacaoController {
    private $db;
    private $authService;
    private $auditService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->checkPermission();
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->auditService = new AuditService();
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
            
            $maxFileSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxFileSize) {
                throw new Exception('Arquivo muito grande. Tamanho máximo: 10 MB');
            }
            
            $allowedExtensions = ['csv', 'xlsx', 'xls'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Formato de arquivo inválido. Use CSV ou XLSX');
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimeTypes = [
                'text/plain',
                'text/csv',
                'application/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new Exception('Tipo de arquivo inválido');
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
        
        $filePath = null;
        
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
            
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (count($rows) < 2) {
                throw new Exception('Arquivo vazio ou sem dados');
            }
            
            $header = array_map(function($col) {
                $col = trim($col);
                $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
                $col = strtolower($col);
                $col = $this->normalizeString($col);
                return $col;
            }, $rows[0]);
            
            $requiredColumns = ['nome', 'setor', 'data de admissao', 'fre'];
            $missingColumns = [];
            
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $header)) {
                    $missingColumns[] = $col;
                }
            }
            
            if (!empty($missingColumns)) {
                throw new Exception("Colunas obrigatórias não encontradas: " . implode(', ', $missingColumns) . ". Esperado: Nome, Setor, Data de Admissão, FRE");
            }
            
            $nomeIndex = array_search('nome', $header);
            $setorIndex = array_search('setor', $header);
            $dataAdmissaoIndex = array_search('data de admissao', $header);
            $freIndex = array_search('fre', $header);
            
            $imported = 0;
            $skipped = 0;
            $errors = 0;
            $errorDetails = [];
            
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $linha = $i + 1; // Linha no arquivo (começando do 1, contando header)
                
                if (empty($row[$nomeIndex])) {
                    $errors++;
                    $errorDetails[] = "Linha $linha: Nome vazio";
                    continue;
                }
                
                $nome = trim($row[$nomeIndex]);
                $setor = trim($row[$setorIndex] ?? '');
                $fre = trim($row[$freIndex] ?? '');
                
                // Validar se FRE está vazio
                if (empty($fre)) {
                    $errors++;
                    $errorDetails[] = "Linha $linha ($nome): Campo FRE obrigatório está vazio";
                    continue;
                }
                
                // Validar se Setor está vazio
                if (empty($setor)) {
                    $errors++;
                    $errorDetails[] = "Linha $linha ($nome): Campo Setor obrigatório está vazio";
                    continue;
                }
                
                $dataAdmissao = null;
                if (!empty($row[$dataAdmissaoIndex])) {
                    $dataAdmissao = $this->parseDate($row[$dataAdmissaoIndex]);
                    if ($dataAdmissao === null) {
                        $errors++;
                        $errorDetails[] = "Linha $linha ($nome): Data de Admissão em formato inválido";
                        continue;
                    }
                }
                
                $exists = $this->db->fetch(
                    "SELECT id FROM profissionais_renner WHERE LOWER(nome) = LOWER(?)",
                    [$nome]
                );
                
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                try {
                    $this->db->query(
                        "INSERT INTO profissionais_renner (nome, setor, fre, data_admissao, data_entrada) VALUES (?, ?, ?, ?, NOW())",
                        [$nome, $setor, $fre, $dataAdmissao]
                    );
                    $imported++;
                } catch (Exception $e) {
                    $errors++;
                    $errorDetails[] = "Linha $linha ($nome): Erro ao inserir - " . $e->getMessage();
                    error_log("❌ Erro importação linha $linha: " . $e->getMessage());
                }
            }
            
            // Registrar auditoria da importação
            if ($imported > 0 || $skipped > 0) {
                $this->auditService->log(
                    'import',
                    'profissionais_renner',
                    null,
                    null,
                    [
                        'arquivo' => $_POST['temp_file'] ?? 'unknown',
                        'total_linhas' => count($rows) - 1,
                        'importados' => $imported,
                        'duplicados_ignorados' => $skipped,
                        'erros' => $errors
                    ]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Importação concluída',
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'error_details' => array_slice($errorDetails, 0, 50) // Limitar a 50 primeiros erros para não sobrecarregar
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } finally {
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
    
    private function normalizeString($str) {
        $unwanted = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n'
        ];
        return strtr($str, $unwanted);
    }
    
    private function parseDate($value) {
        if (empty($value)) {
            return null;
        }
        
        if (is_numeric($value)) {
            try {
                $date = Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                return null;
            }
        }
        
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }
}
