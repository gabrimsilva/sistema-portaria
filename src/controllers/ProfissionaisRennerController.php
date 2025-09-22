<?php

class ProfissionaisRennerController {
    private $db;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        
        $query = "SELECT * FROM profissionais_renner WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (nome ILIKE ? OR setor ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $params[] = $setor;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $profissionais = $this->db->fetchAll($query, $params);
        
        // Get unique sectors for filter
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM profissionais_renner WHERE setor IS NOT NULL ORDER BY setor");
        
        include '../views/profissionais_renner/list.php';
    }
    
    public function create() {
        include '../views/profissionais_renner/form.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = $_POST['nome'] ?? '';
                $data_entrada = $_POST['data_entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                $retorno = $_POST['retorno'] ?? null;
                $saida_final = $_POST['saida_final'] ?? null;
                $setor = $_POST['setor'] ?? '';
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    INSERT INTO profissionais_renner (nome, data_entrada, saida, retorno, saida_final, setor)
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $nome,
                    $data_entrada ?: null,
                    $saida ?: null,
                    $retorno ?: null,
                    $saida_final ?: null,
                    $setor
                ]);
                
                header('Location: /profissionais-renner?success=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                include '../views/profissionais_renner/form.php';
            }
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /profissionais-renner');
            exit;
        }
        
        $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
        if (!$profissional) {
            header('Location: /profissionais-renner');
            exit;
        }
        
        include '../views/profissionais_renner/form.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $id = $_POST['id'] ?? null;
                $nome = $_POST['nome'] ?? '';
                $data_entrada = $_POST['data_entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                $retorno = $_POST['retorno'] ?? null;
                $saida_final = $_POST['saida_final'] ?? null;
                $setor = $_POST['setor'] ?? '';
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET nome = ?, data_entrada = ?, saida = ?, retorno = ?, saida_final = ?, setor = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome,
                    $data_entrada ?: null,
                    $saida ?: null,
                    $retorno ?: null,
                    $saida_final ?: null,
                    $setor,
                    $id
                ]);
                
                header('Location: /profissionais-renner?updated=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$_POST['id']]);
                include '../views/profissionais_renner/form.php';
            }
        }
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("DELETE FROM profissionais_renner WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /profissionais-renner');
        exit;
    }
}