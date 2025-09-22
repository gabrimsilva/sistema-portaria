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
        $status = $_GET['status'] ?? '';
        
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
        
        if ($status === 'ativo') {
            // Ativos: têm data_entrada ou retorno, mas não saída_final (estão atualmente na empresa)
            $query .= " AND (data_entrada IS NOT NULL OR retorno IS NOT NULL) AND saida_final IS NULL";
        } elseif ($status === 'saiu') {
            // Saíram: têm saída_final registrada (independente do dia)
            $query .= " AND saida_final IS NOT NULL";
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
                
                // Se não foi especificada data de entrada, registra automaticamente a data/hora atual
                if (empty($data_entrada)) {
                    $data_entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO profissionais_renner (nome, data_entrada, saida, retorno, saida_final, setor)
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $nome,
                    $data_entrada,
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
    
    public function saveAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $data_entrada_input = trim($_POST['data_entrada'] ?? '');
                
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                
                // Usar data especificada ou data/hora atual se não fornecida
                if (!empty($data_entrada_input)) {
                    $timestamp = strtotime($data_entrada_input);
                    if ($timestamp === false) {
                        echo json_encode(['success' => false, 'message' => 'Data/hora inválida fornecida']);
                        return;
                    }
                    $data_entrada = date('Y-m-d H:i:s', $timestamp);
                } else {
                    $data_entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO profissionais_renner (nome, data_entrada, setor)
                    VALUES (?, ?, ?)
                ", [
                    $nome, $data_entrada, $setor
                ]);
                
                $id = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional cadastrado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Profissional Renner',
                        'cpf' => '',
                        'empresa' => '',
                        'setor' => $setor,
                        'hora_entrada' => $data_entrada
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }
    
    public function updateAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $id = trim($_POST['id'] ?? '');
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET nome = ?, setor = ?
                    WHERE id = ?
                ", [$nome, $setor, $id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'setor' => $setor
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }
}