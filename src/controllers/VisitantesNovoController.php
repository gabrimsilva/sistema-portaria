<?php

require_once __DIR__ . '/../services/DuplicityValidationService.php';
require_once __DIR__ . '/../utils/CpfValidator.php';
require_once __DIR__ . '/../utils/DateTimeValidator.php';

class VisitantesNovoController {
    private $db;
    private $duplicityService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
        $this->duplicityService = new DuplicityValidationService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    private function getViewPath($viewFile) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/reports/') !== false) {
            return "../views/reports/visitantes/{$viewFile}";
        }
        return "../views/visitantes_novo/{$viewFile}";
    }
    
    private function getBaseRoute() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/reports/') !== false) {
            return "/reports/visitantes";
        }
        return "/visitantes";
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
        
        include $this->getViewPath('list.php');
    }
    
    public function create() {
        include $this->getViewPath('form.php');
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
                
                // ========== VALIDAR E NORMALIZAR DADOS ==========
                // Validar CPF com dígitos verificadores
                if (!empty($cpf)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $cpf = $cpfValidation['normalized'];
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ================================================
                
                // Validações obrigatórias
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($cpf)) {
                    throw new Exception("CPF é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // ========== VALIDAÇÕES TEMPORAIS ==========
                // Validar hora de entrada
                $entradaValidation = DateTimeValidator::validateEntryDateTime($hora_entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $hora_entrada = $entradaValidation['normalized'];
                
                // Validar hora de saída
                $saidaValidation = DateTimeValidator::validateExitDateTime($hora_saida, $hora_entrada);
                if (!$saidaValidation['isValid']) {
                    throw new Exception($saidaValidation['message']);
                }
                $hora_saida = $saidaValidation['normalized'] ?: null;
                // ==========================================
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'hora_entrada' => $hora_entrada
                ];
                
                $validacao = $this->duplicityService->validateNewEntry($dadosValidacao, 'visitante');
                
                if (!$validacao['isValid']) {
                    $errorMessages = implode(' ', $validacao['errors']);
                    throw new Exception("Erro de duplicidade: " . $errorMessages);
                }
                // ===============================================
                
                $this->db->query("
                    INSERT INTO visitantes_novo (nome, cpf, empresa, funcionario_responsavel, setor, placa_veiculo, hora_entrada, hora_saida)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $placa_veiculo,
                    $hora_entrada,
                    $hora_saida ?: null
                ]);
                
                header('Location: ' . $this->getBaseRoute() . '?success=1');
                exit;
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados
                if (strpos($errorMessage, 'ux_visitantes_cpf_ativo') !== false) {
                    $error = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'ux_visitantes_placa_ativa') !== false) {
                    $error = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'chk_visitantes_horario_valido') !== false) {
                    $error = "A hora de saída não pode ser anterior à hora de entrada.";
                } else {
                    $error = $errorMessage;
                }
                
                include $this->getViewPath('form.php');
            }
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . $this->getBaseRoute());
            exit;
        }
        
        $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
        if (!$visitante) {
            header('Location: ' . $this->getBaseRoute());
            exit;
        }
        
        include $this->getViewPath('form.php');
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
                
                // ========== VALIDAR E NORMALIZAR DADOS ==========
                // Validar CPF com dígitos verificadores
                if (!empty($cpf)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $cpf = $cpfValidation['normalized'];
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ================================================
                
                // Validações obrigatórias
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($cpf)) {
                    throw new Exception("CPF é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // ========== VALIDAÇÕES TEMPORAIS ==========
                // Validar hora de entrada
                $entradaValidation = DateTimeValidator::validateEntryDateTime($hora_entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $hora_entrada = $entradaValidation['normalized'];
                
                // Validar hora de saída
                $saidaValidation = DateTimeValidator::validateExitDateTime($hora_saida, $hora_entrada);
                if (!$saidaValidation['isValid']) {
                    throw new Exception($saidaValidation['message']);
                }
                $hora_saida = $saidaValidation['normalized'] ?: null;
                // ==========================================
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'hora_entrada' => $hora_entrada,
                    'hora_saida' => $hora_saida
                ];
                
                $validacao = $this->duplicityService->validateEditEntry($dadosValidacao, $id, 'visitantes_novo');
                
                if (!$validacao['isValid']) {
                    $errorMessages = implode(' ', $validacao['errors']);
                    throw new Exception("Erro de duplicidade: " . $errorMessages);
                }
                // ===============================================
                
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
                
                header('Location: ' . $this->getBaseRoute() . '?updated=1');
                exit;
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados
                if (strpos($errorMessage, 'ux_visitantes_cpf_ativo') !== false) {
                    $error = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'ux_visitantes_placa_ativa') !== false) {
                    $error = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'chk_visitantes_horario_valido') !== false) {
                    $error = "A hora de saída não pode ser anterior à hora de entrada.";
                } else {
                    $error = $errorMessage;
                }
                
                $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$_POST['id']]);
                include $this->getViewPath('form.php');
            }
        }
    }
    
    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                // Buscar dados do visitante para validação
                $visitante = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
                
                if ($visitante) {
                    // Normalizar dados para validação
                    $cpf = preg_replace('/\D/', '', $visitante['cpf']);
                    $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($visitante['placa_veiculo'])));
                    
                    // Validar se não há entrada em aberto
                    $cpfValidation = $this->duplicityService->validateCpfNotOpen($cpf, $id, 'visitantes_novo');
                    if (!$cpfValidation['isValid']) {
                        header('Location: ' . $this->getBaseRoute() . '?error=' . urlencode($cpfValidation['message']));
                        exit;
                    }
                    
                    if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                        $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, $id, 'visitantes_novo');
                        if (!$placaValidation['isValid']) {
                            header('Location: ' . $this->getBaseRoute() . '?error=' . urlencode($placaValidation['message']));
                            exit;
                        }
                    }
                }
                
                $this->db->query("UPDATE visitantes_novo SET hora_entrada = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: ' . $this->getBaseRoute());
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
        
        header('Location: ' . $this->getBaseRoute());
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
                
                // ========== NORMALIZAR DADOS ==========
                // CPF: apenas dígitos
                $cpf = preg_replace('/\D/', '', $cpf);
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // =====================================
                
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
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'hora_entrada' => $hora_entrada
                ];
                
                $validacao = $this->duplicityService->validateNewEntry($dadosValidacao, 'visitante');
                
                if (!$validacao['isValid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro de duplicidade',
                        'errors' => $validacao['errors']
                    ]);
                    return;
                }
                // ===============================================
                
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
                
                // Buscar dados atuais do visitante
                $visitanteAtual = $this->db->fetch("SELECT * FROM visitantes_novo WHERE id = ?", [$id]);
                if (!$visitanteAtual) {
                    echo json_encode(['success' => false, 'message' => 'Visitante não encontrado']);
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
                
                // ========== VALIDAÇÕES DE DUPLICIDADE PARA EDIÇÃO ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'hora_entrada' => $visitanteAtual['hora_entrada'],
                    'hora_saida' => $hora_saida_final
                ];
                
                $validacao = $this->duplicityService->validateEditEntry($dadosValidacao, $id, 'visitantes_novo');
                
                if (!$validacao['isValid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro de duplicidade',
                        'errors' => $validacao['errors']
                    ]);
                    return;
                }
                // ==========================================================
                
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