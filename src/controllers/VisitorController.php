<?php

class VisitorController {
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
            case 'delete':
                $this->delete();
                break;
            default:
                $this->list();
                break;
        }
    }
    
    public function list() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT * FROM visitantes WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (nome ILIKE ? OR empresa ILIKE ? OR cpf ILIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY data_cadastro DESC";
        
        $visitors = $this->db->fetchAll($sql, $params);
        
        include '../views/visitors/list.php';
    }
    
    public function create() {
        include '../views/visitors/form.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $empresa = $_POST['empresa'] ?? '';
                $pessoa_visitada = $_POST['pessoa_visitada'] ?? '';
                $email = $_POST['email'] ?? '';
                $telefone = $_POST['telefone'] ?? '';
                
                // Handle photo upload
                $fotoPath = '';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $fotoPath = $this->uploadPhoto($_FILES['foto']);
                }
                
                // Handle base64 photo from camera
                if (!empty($_POST['photo_data'])) {
                    $fotoPath = $this->saveBase64Photo($_POST['photo_data']);
                }
                
                // Generate QR Code
                $qrCode = $this->generateVisitorCode();
                
                $this->db->query("
                    INSERT INTO visitantes (nome, cpf, empresa, pessoa_visitada, email, telefone, foto, qr_code, data_vencimento) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $pessoa_visitada, $email, $telefone, 
                    $fotoPath, $qrCode, date('Y-m-d H:i:s', strtotime('+8 hours'))
                ]);
                
                header('Location: /visitors?success=1');
                exit;
                
            } catch (Exception $e) {
                $error = "Erro ao cadastrar visitante: " . $e->getMessage();
                include '../views/visitors/form.php';
            }
        }
    }
    
    private function uploadPhoto($file) {
        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("Arquivo muito grande. Máximo 2MB");
        }
        
        $uploadDir = UPLOAD_PATH . '/visitors/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate secure filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'uploads/visitors/' . $filename;
        }
        
        throw new Exception("Erro ao fazer upload da foto");
    }
    
    private function saveBase64Photo($base64Data) {
        $uploadDir = UPLOAD_PATH . '/visitors/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Remove data URL prefix
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
        $data = base64_decode($data);
        
        $filename = uniqid() . '.jpg';
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $data)) {
            return 'uploads/visitors/' . $filename;
        }
        
        throw new Exception("Erro ao salvar foto da câmera");
    }
    
    private function generateVisitorCode() {
        return 'VIS' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}