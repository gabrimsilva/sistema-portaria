<?php

class VisitantesNovoController {
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
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $hora_entrada = $_POST['hora_entrada'] ?? null;
                $hora_saida = $_POST['hora_saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                // Se não foi especificada hora de entrada, registra automaticamente a hora atual
                if (empty($hora_entrada)) {
                    $hora_entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO visitantes_novo (nome, cpf, empresa, funcionario_responsavel, setor, placa_veiculo, hora_entrada, hora_saida)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $placa_veiculo,
                    $hora_entrada,
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
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $hora_entrada = $_POST['hora_entrada'] ?? null;
                $hora_saida = $_POST['hora_saida'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                
                $this->db->query("
                    UPDATE visitantes_novo 
                    SET nome = ?, cpf = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, placa_veiculo = ?, 
                        hora_entrada = ?, hora_saida = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $placa_veiculo,
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
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $id = trim($_GET['id'] ?? $_POST['id'] ?? '');
                
                if (empty($id)) {
                    echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                    return;
                }
                
                // Buscar o visitante
                $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
                
                if (!$visitante) {
                    echo json_encode(['success' => false, 'message' => 'Visitante não encontrado']);
                    return;
                }
                
                // Registrar saída
                $this->db->query("
                    UPDATE visitantes_novo 
                    SET hora_saida = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Saída registrada com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $visitante['nome']
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
    
    public function saveAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                CSRFProtection::verifyRequest();
                $nome = trim($_POST['nome'] ?? '');
                $cpf = trim($_POST['cpf'] ?? '');
                $empresa = trim($_POST['empresa'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $hora_entrada_input = trim($_POST['hora_entrada'] ?? '');
                
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                
                // Usar hora especificada ou hora atual se não fornecida
                if (!empty($hora_entrada_input)) {
                    $timestamp = strtotime($hora_entrada_input);
                    if ($timestamp === false) {
                        echo json_encode(['success' => false, 'message' => 'Data/hora inválida fornecida']);
                        return;
                    }
                    $hora_entrada = date('Y-m-d H:i:s', $timestamp);
                } else {
                    $hora_entrada = date('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    INSERT INTO visitantes_novo (nome, cpf, empresa, funcionario_responsavel, setor, placa_veiculo, hora_entrada)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $placa_veiculo, $hora_entrada
                ]);
                
                $id = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Visitante cadastrado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Visitante',
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'funcionario_responsavel' => $funcionario_responsavel,
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $hora_entrada
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
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $hora_saida = trim($_POST['hora_saida'] ?? '');
                
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                
                // Processar hora de saída
                $hora_saida_final = null;
                if (!empty($hora_saida)) {
                    // Tentar diferentes formatos de datetime-local
                    $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $hora_saida);
                    if (!$datetime) {
                        $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $hora_saida);
                    }
                    
                    if (!$datetime) {
                        echo json_encode(['success' => false, 'message' => 'Formato de hora de saída inválido']);
                        return;
                    }
                    $hora_saida_final = $datetime->format('Y-m-d H:i:s');
                }
                
                $this->db->query("
                    UPDATE visitantes_novo 
                    SET nome = ?, cpf = ?, empresa = ?, setor = ?, funcionario_responsavel = ?, placa_veiculo = ?, hora_saida = ?
                    WHERE id = ?
                ", [$nome, $cpf, $empresa, $setor, $funcionario_responsavel, $placa_veiculo, $hora_saida_final, $id]);
                
                // Buscar dados atualizados para retornar
                $visitanteAtualizado = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Visitante atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Visitante',
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'funcionario_responsavel' => $funcionario_responsavel,
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $visitanteAtualizado['hora_entrada'] ?? null,
                        'hora_saida' => $visitanteAtualizado['hora_saida'] ?? null
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
                
                // Buscar o visitante
                $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
                
                if (!$visitante) {
                    echo json_encode(['success' => false, 'message' => 'Visitante não encontrado']);
                    return;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $visitante['id'],
                        'nome' => $visitante['nome'],
                        'cpf' => $visitante['cpf'],
                        'empresa' => $visitante['empresa'],
                        'setor' => $visitante['setor'],
                        'funcionario_responsavel' => $visitante['funcionario_responsavel'],
                        'placa_veiculo' => $visitante['placa_veiculo'],
                        'hora_entrada' => $visitante['hora_entrada'],
                        'hora_saida' => $visitante['hora_saida']
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