<?php

require_once __DIR__ . '/../services/DuplicityValidationService.php';
require_once __DIR__ . '/../utils/CpfValidator.php';
require_once __DIR__ . '/../utils/DateTimeValidator.php';

class PrestadoresServicoController {
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
            return "../views/reports/prestadores_servico/{$viewFile}";
        }
        return "../views/prestadores_servico/{$viewFile}";
    }
    
    private function getBaseRoute() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/reports/') !== false) {
            return "/reports/prestadores-servico";
        }
        return "/prestadores-servico";
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
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
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
                $entradaValidation = DateTimeValidator::validateEntryDateTime($entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $entrada = $entradaValidation['normalized'];
                
                // Validar hora de saída
                $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $entrada);
                if (!$saidaValidation['isValid']) {
                    throw new Exception($saidaValidation['message']);
                }
                $saida = $saidaValidation['normalized'] ?: null;
                // ==========================================
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'entrada' => $entrada
                ];
                
                $validacao = $this->duplicityService->validateNewEntry($dadosValidacao, 'prestador');
                
                if (!$validacao['isValid']) {
                    $errorMessages = implode(' ', $validacao['errors']);
                    throw new Exception("Erro de duplicidade: " . $errorMessages);
                }
                // ===============================================
                
                $this->db->query("
                    INSERT INTO prestadores_servico (nome, cpf, empresa, funcionario_responsavel, setor, observacao, placa_veiculo, entrada, saida)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo,
                    $entrada,
                    $saida ?: null
                ]);
                
                header('Location: ' . $this->getBaseRoute() . '?success=1');
                exit;
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    $error = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    $error = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
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
        
        $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
        if (!$prestador) {
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
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
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
                $entradaValidation = DateTimeValidator::validateEntryDateTime($entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $entrada = $entradaValidation['normalized'];
                
                // Validar hora de saída
                $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $entrada);
                if (!$saidaValidation['isValid']) {
                    throw new Exception($saidaValidation['message']);
                }
                $saida = $saidaValidation['normalized'] ?: null;
                // ==========================================
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'entrada' => $entrada,
                    'saida' => $saida
                ];
                
                $validacao = $this->duplicityService->validateEditEntry($dadosValidacao, $id, 'prestadores_servico');
                
                if (!$validacao['isValid']) {
                    $errorMessages = implode(' ', $validacao['errors']);
                    throw new Exception("Erro de duplicidade: " . $errorMessages);
                }
                // ===============================================
                
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET nome = ?, cpf = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, observacao = ?, placa_veiculo = ?, 
                        entrada = ?, saida = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo,
                    $entrada ?: null,
                    $saida ?: null,
                    $id
                ]);
                
                header('Location: ' . $this->getBaseRoute() . '?updated=1');
                exit;
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    $error = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    $error = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
                    $error = "A hora de saída não pode ser anterior à hora de entrada.";
                } else {
                    $error = $errorMessage;
                }
                
                $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$_POST['id']]);
                include $this->getViewPath('form.php');
            }
        }
    }
    
    public function registrarEntrada() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                // Buscar dados do prestador para validação
                $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
                
                if ($prestador) {
                    // Normalizar dados para validação
                    $cpf = preg_replace('/\D/', '', $prestador['cpf']);
                    $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($prestador['placa_veiculo'])));
                    
                    // Validar se não há entrada em aberto
                    $cpfValidation = $this->duplicityService->validateCpfNotOpen($cpf, $id, 'prestadores_servico');
                    if (!$cpfValidation['isValid']) {
                        header('Location: ' . $this->getBaseRoute() . '?error=' . urlencode($cpfValidation['message']));
                        exit;
                    }
                    
                    if (!empty($placa_veiculo)) {
                        $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, $id, 'prestadores_servico');
                        if (!$placaValidation['isValid']) {
                            header('Location: ' . $this->getBaseRoute() . '?error=' . urlencode($placaValidation['message']));
                            exit;
                        }
                    }
                }
                
                $this->db->query("UPDATE prestadores_servico SET entrada = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
            }
        }
        
        header('Location: ' . $this->getBaseRoute());
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
        
        header('Location: ' . $this->getBaseRoute());
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
                $setor = trim($_POST['setor'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $entrada_input = trim($_POST['entrada'] ?? '');
                
                // ========== NORMALIZAR DADOS ==========
                // CPF: apenas dígitos
                $cpf = preg_replace('/\D/', '', $cpf);
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // =====================================
                
                // Validações obrigatórias
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($cpf)) {
                    echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Validar CPF
                $cpfValidator = new CpfValidator();
                if (!$cpfValidator->isValid($cpf)) {
                    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
                    return;
                }
                $cpf = $cpfValidator->normalize($cpf);
                
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
                
                // ========== VALIDAÇÕES DE DUPLICIDADE ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'entrada' => $entrada
                ];
                
                $validacao = $this->duplicityService->validateNewEntry($dadosValidacao, 'prestador');
                
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
                    INSERT INTO prestadores_servico (nome, cpf, empresa, funcionario_responsavel, setor, observacao, placa_veiculo, entrada)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo, $entrada
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
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados para AJAX
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
                    echo json_encode(['success' => false, 'message' => 'A hora de saída não pode ser anterior à hora de entrada.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                }
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
                
                // Validações obrigatórias
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($cpf)) {
                    echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Validar e normalizar CPF
                $cpfValidator = new CpfValidator();
                if (!$cpfValidator->isValid($cpf)) {
                    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
                    return;
                }
                $cpf = $cpfValidator->normalize($cpf);
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                // Buscar dados atuais do prestador
                $prestadorAtual = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
                if (!$prestadorAtual) {
                    echo json_encode(['success' => false, 'message' => 'Prestador não encontrado']);
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
                
                // ========== VALIDAÇÕES DE DUPLICIDADE PARA EDIÇÃO ==========
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'entrada' => $prestadorAtual['entrada'],
                    'saida' => $saida_parsed
                ];
                
                $validacao = $this->duplicityService->validateEditEntry($dadosValidacao, $id, 'prestadores_servico');
                
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
                    UPDATE prestadores_servico 
                    SET nome = ?, cpf = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, observacao = ?, placa_veiculo = ?, saida = ?
                    WHERE id = ?
                ", [$nome, $cpf, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo, $saida_parsed, $id]);
                
                // Buscar dados atualizados para retornar
                $prestadorAtualizado = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prestador atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Prestador',
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'funcionario_responsavel' => '',
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $prestadorAtualizado['entrada'] ?? null,
                        'saida' => $prestadorAtualizado['saida'] ?? null
                    ]
                ]);
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados para AJAX
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
                    echo json_encode(['success' => false, 'message' => 'A hora de saída não pode ser anterior à hora de entrada.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                }
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
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados para AJAX
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
                    echo json_encode(['success' => false, 'message' => 'A hora de saída não pode ser anterior à hora de entrada.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                }
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
                $errorMessage = $e->getMessage();
                
                // Tratar erros específicos do banco de dados para AJAX
                if (strpos($errorMessage, 'ux_prestadores_cpf_ativo') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'ux_prestadores_placa_ativa') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.']);
                } elseif (strpos($errorMessage, 'chk_prestadores_horario_valido') !== false) {
                    echo json_encode(['success' => false, 'message' => 'A hora de saída não pode ser anterior à hora de entrada.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }
}