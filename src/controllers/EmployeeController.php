<?php

class EmployeeController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'new':
                $this->create();
                break;
            case 'save':
                $this->save();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'update':
                $this->update();
                break;
            case 'toggle':
                $this->toggleStatus();
                break;
            case 'history':
                $this->accessHistory();
                break;
            default:
                $this->list();
                break;
        }
    }
    
    public function list() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT * FROM funcionarios WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (nome ILIKE ? OR cpf ILIKE ? OR cargo ILIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($status !== '') {
            $sql .= " AND ativo = ?";
            $params[] = $status === '1';
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $employees = $this->db->fetchAll($sql, $params);
        
        include '../views/employees/list.php';
    }
    
    public function create() {
        include '../views/employees/form.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $cargo = $_POST['cargo'] ?? '';
                $email = $_POST['email'] ?? '';
                $telefone = $_POST['telefone'] ?? '';
                $data_admissao = $_POST['data_admissao'] ?? date('Y-m-d');
                
                // Handle photo upload
                $fotoPath = '';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $fotoPath = $this->uploadPhoto($_FILES['foto']);
                }
                
                $this->db->query("
                    INSERT INTO funcionarios (nome, cpf, cargo, email, telefone, foto, data_admissao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $cargo, $email, $telefone, $fotoPath, $data_admissao
                ]);
                
                header('Location: /employees?success=1');
                exit;
                
            } catch (Exception $e) {
                $error = "Erro ao cadastrar funcionário: " . $e->getMessage();
                include '../views/employees/form.php';
            }
        }
    }
    
    public function toggleStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }
        
        CSRFProtection::verifyRequest();
        
        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            
            $employee = $this->db->fetch("SELECT ativo FROM funcionarios WHERE id = ?", [$id]);
            if ($employee) {
                $newStatus = !$employee['ativo'];
                $this->db->query("UPDATE funcionarios SET ativo = ? WHERE id = ?", [$newStatus, $id]);
                
                $message = $newStatus ? 'ativado' : 'desativado';
                header("Location: /employees?status_changed=Funcionário {$message} com sucesso");
                exit;
            }
        }
        
        header('Location: /employees');
        exit;
    }
    
    public function accessHistory() {
        $employee_id = $_GET['id'] ?? null;
        if (!$employee_id) {
            header('Location: /employees');
            exit;
        }
        
        $employee = $this->db->fetch("SELECT * FROM funcionarios WHERE id = ?", [$employee_id]);
        if (!$employee) {
            header('Location: /employees?error=Funcionário não encontrado');
            exit;
        }
        
        $accessLogs = $this->db->fetchAll("
            SELECT a.*, u.nome as usuario_nome
            FROM acessos a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.funcionario_id = ?
            ORDER BY a.data_hora DESC
            LIMIT 50
        ", [$employee_id]);
        
        include '../views/employees/history.php';
    }
    
    private function uploadPhoto($file) {
        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if ($file['size'] > $maxSize) {
            throw new Exception("Arquivo muito grande. Máximo 2MB");
        }
        
        // Server-side MIME validation with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP");
        }
        
        $uploadDir = UPLOAD_PATH . '/employees/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate secure filename with safe extension based on MIME type
        $safeExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png', 
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        $extension = $safeExtensions[$mimeType] ?? 'jpg';
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'uploads/employees/' . $filename;
        }
        
        throw new Exception("Erro ao fazer upload da foto");
    }
}