<?php

class PrestadoresServicoController {
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
        $empresa = $_GET['empresa'] ?? '';
        
        $query = "SELECT * FROM prestadores_servico WHERE 1=1";
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
        
        if (!empty($empresa)) {
            $query .= " AND empresa = ?";
            $params[] = $empresa;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $prestadores = $this->db->fetchAll($query, $params);
        
        // Get unique sectors and companies for filters
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM prestadores_servico WHERE setor IS NOT NULL ORDER BY setor");
        $empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM prestadores_servico WHERE empresa IS NOT NULL ORDER BY empresa");
        
        include '../views/prestadores_servico/list.php';
    }
    
    public function create() {
        include '../views/prestadores_servico/form.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $empresa = $_POST['empresa'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                // Se não foi especificada hora de entrada, registra automaticamente a hora atual
                if (empty($entrada)) {
                    $entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO prestadores_servico (nome, cpf, empresa, setor, observacao, placa_veiculo, entrada, saida)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $setor, $observacao, $placa_veiculo,
                    $entrada,
                    $saida ?: null
                ]);
                
                header('Location: /prestadores-servico?success=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                include '../views/prestadores_servico/form.php';
            }
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /prestadores-servico');
            exit;
        }
        
        $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
        if (!$prestador) {
            header('Location: /prestadores-servico');
            exit;
        }
        
        include '../views/prestadores_servico/form.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $id = $_POST['id'] ?? null;
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $empresa = $_POST['empresa'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET nome = ?, cpf = ?, empresa = ?, setor = ?, observacao = ?, placa_veiculo = ?, 
                        entrada = ?, saida = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome, $cpf, $empresa, $setor, $observacao, $placa_veiculo,
                    $entrada ?: null,
                    $saida ?: null,
                    $id
                ]);
                
                header('Location: /prestadores-servico?updated=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$_POST['id']]);
                include '../views/prestadores_servico/form.php';
            }
        }
    }
    
    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("UPDATE prestadores_servico SET entrada = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /prestadores-servico');
        exit;
    }
    
    public function registrarSaida() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("UPDATE prestadores_servico SET saida = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /prestadores-servico');
        exit;
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                $this->db->query("DELETE FROM prestadores_servico WHERE id = ?", [$id]);
            }
        }
        
        header('Location: /prestadores-servico');
        exit;
    }
    
    public function saveAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $nome = trim($_POST['nome'] ?? '');
                $cpf = trim($_POST['cpf'] ?? '');
                $empresa = trim($_POST['empresa'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $entrada_input = trim($_POST['entrada'] ?? '');
                
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                
                // Usar hora especificada ou hora atual se não fornecida
                if (!empty($entrada_input)) {
                    $timestamp = strtotime($entrada_input);
                    if ($timestamp === false) {
                        echo json_encode(['success' => false, 'message' => 'Data/hora inválida fornecida']);
                        return;
                    }
                    $entrada = date('Y-m-d H:i:s', $timestamp);
                } else {
                    $entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO prestadores_servico (nome, cpf, empresa, setor, observacao, placa_veiculo, entrada)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $setor, $observacao, $placa_veiculo, $entrada
                ]);
                
                $id = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prestador cadastrado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Prestador',
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $entrada
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
                $cpf = trim($_POST['cpf'] ?? '');
                $empresa = trim($_POST['empresa'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $saida = trim($_POST['saida'] ?? '');
                
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                
                // Parse saida if provided
                $saida_parsed = null;
                if (!empty($saida)) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $saida);
                    if (!$date) {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $saida);
                    }
                    if ($date) {
                        $saida_parsed = $date->format('Y-m-d H:i:s');
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de saída inválido']);
                        return;
                    }
                }
                
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET nome = ?, cpf = ?, empresa = ?, setor = ?, observacao = ?, placa_veiculo = ?, saida = ?
                    WHERE id = ?
                ", [$nome, $cpf, $empresa, $setor, $observacao, $placa_veiculo, $saida_parsed, $id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prestador atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'placa_veiculo' => $placa_veiculo,
                        'saida' => $saida_parsed
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
    
    public function getData() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: application/json');
            
            try {
                $id = trim($_GET['id'] ?? '');
                
                if (empty($id)) {
                    echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                    return;
                }
                
                // Buscar o prestador
                $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
                
                if (!$prestador) {
                    echo json_encode(['success' => false, 'message' => 'Prestador não encontrado']);
                    return;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $prestador['id'],
                        'nome' => $prestador['nome'],
                        'cpf' => $prestador['cpf'],
                        'empresa' => $prestador['empresa'],
                        'setor' => $prestador['setor'],
                        'observacao' => $prestador['observacao'],
                        'placa_veiculo' => $prestador['placa_veiculo'],
                        'entrada' => $prestador['entrada'],
                        'saida' => $prestador['saida']
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
    
    public function saida() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $id = trim($_GET['id'] ?? $_POST['id'] ?? '');
                
                if (empty($id)) {
                    echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                    return;
                }
                
                // Buscar o prestador
                $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
                
                if (!$prestador) {
                    echo json_encode(['success' => false, 'message' => 'Prestador não encontrado']);
                    return;
                }
                
                // Registrar saída
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET saida = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Saída registrada com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $prestador['nome']
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