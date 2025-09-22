<?php

class VisitantesNovoController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "SELECT * FROM visitantes_novo WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (nome ILIKE ? OR cpf ILIKE ? OR empresa ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $params[] = $setor;
        }
        
        if ($status === 'ativo') {
            $query .= " AND hora_entrada IS NOT NULL AND hora_saida IS NULL";
        } elseif ($status === 'saiu') {
            $query .= " AND hora_saida IS NOT NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $visitantes = $this->db->fetchAll($query, $params);
        
        // Get unique sectors for filter
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM visitantes_novo WHERE setor IS NOT NULL ORDER BY setor");
        
        include '../views/visitantes_novo/list.php';
    }
    
    public function create() {
        include '../views/visitantes_novo/form.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $empresa = $_POST['empresa'] ?? '';
                $funcionario_responsavel = $_POST['funcionario_responsavel'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $hora_entrada = $_POST['hora_entrada'] ?? null;
                $hora_saida = $_POST['hora_saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    INSERT INTO visitantes_novo (nome, cpf, empresa, funcionario_responsavel, setor, hora_entrada, hora_saida)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor,
                    $hora_entrada ?: null,
                    $hora_saida ?: null
                ]);
                
                header('Location: /visitantes?success=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                include '../views/visitantes_novo/form.php';
            }
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /visitantes');
            exit;
        }
        
        $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
        if (!$visitante) {
            header('Location: /visitantes');
            exit;
        }
        
        include '../views/visitantes_novo/form.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $id = $_POST['id'] ?? null;
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $empresa = $_POST['empresa'] ?? '';
                $funcionario_responsavel = $_POST['funcionario_responsavel'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $hora_entrada = $_POST['hora_entrada'] ?? null;
                $hora_saida = $_POST['hora_saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    UPDATE visitantes_novo 
                    SET nome = ?, cpf = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, 
                        hora_entrada = ?, hora_saida = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor,
                    $hora_entrada ?: null,
                    $hora_saida ?: null,
                    $id
                ]);
                
                header('Location: /visitantes?updated=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$_POST['id']]);
                include '../views/visitantes_novo/form.php';
            }
        }
    }
    
    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("UPDATE visitantes_novo SET hora_entrada = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /visitantes');
        exit;
    }
    
    public function registrarSaida() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("UPDATE visitantes_novo SET hora_saida = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /visitantes');
        exit;
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("DELETE FROM visitantes_novo WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /visitantes');
        exit;
    }
}