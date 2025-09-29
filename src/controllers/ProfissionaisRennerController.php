<?php

require_once __DIR__ . '/../services/DuplicityValidationService.php';
require_once __DIR__ . '/../utils/DateTimeValidator.php';

class ProfissionaisRennerController {
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
            return "../views/reports/profissionais_renner/{$viewFile}";
        }
        return "../views/profissionais_renner/{$viewFile}";
    }
    
    private function getBaseRoute() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/reports/') !== false) {
            return "/reports/profissionais-renner";
        }
        return "/profissionais-renner";
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? '';
        $data = $_GET['data'] ?? date('Y-m-d'); // Padrão: hoje
        $page = max(1, intval($_GET['page'] ?? 1));
        $pageSize = max(1, min(100, intval($_GET['pageSize'] ?? 20)));
        $offset = ($page - 1) * $pageSize;
        
        // Verificar se é contexto de relatório
        $isReport = strpos($_SERVER['REQUEST_URI'] ?? '', '/reports/') !== false;
        
        // Query otimizada: campos específicos para relatórios, todos para módulo normal
        if ($isReport) {
            $query = "SELECT id, nome, setor, placa_veiculo, data_entrada, saida, retorno, saida_final FROM profissionais_renner WHERE 1=1";
        } else {
            $query = "SELECT * FROM profissionais_renner WHERE 1=1";
        }
        $params = [];
        
        // Filtro por data (apenas para relatórios)
        if ($isReport && !empty($data)) {
            $query .= " AND DATE(data_entrada) = ?";
            $params[] = $data;
        }
        
        if (!empty($search)) {
            if ($isReport) {
                $query .= " AND nome ILIKE ?";
                $params[] = "%$search%";
            } else {
                $query .= " AND (nome ILIKE ? OR setor ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
        }
        
        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $params[] = $setor;
        }
        
        // Filtro de status ajustado
        if ($status === 'ativo') {
            if ($isReport) {
                // Para relatórios: apenas com entrada válida e sem saída final
                $query .= " AND data_entrada IS NOT NULL AND saida_final IS NULL";
            } else {
                // Para módulo normal: lógica original
                $query .= " AND (data_entrada IS NOT NULL OR retorno IS NOT NULL) AND saida_final IS NULL";
            }
        } elseif ($status === 'saiu') {
            $query .= " AND saida_final IS NOT NULL";
        }
        
        // Contar total para paginação (apenas para relatórios)
        $total = 0;
        $pagination = null;
        if ($isReport) {
            $countQuery = str_replace(
                "SELECT id, nome, setor, placa_veiculo, data_entrada, saida, retorno, saida_final", 
                "SELECT COUNT(*)", 
                $query
            );
            $totalResult = $this->db->fetch($countQuery, $params);
            $total = $totalResult['count'] ?? 0;
            
            $pagination = [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ];
        }
        
        // Ordenação e paginação
        if ($isReport) {
            $query .= " ORDER BY data_entrada DESC";
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $pageSize;
            $params[] = $offset;
        } else {
            $query .= " ORDER BY created_at DESC";
        }
        
        $profissionais = $this->db->fetchAll($query, $params);
        
        // Get unique sectors for filter
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM profissionais_renner WHERE setor IS NOT NULL ORDER BY setor");
        
        include $this->getViewPath('list.php');
    }
    
    public function create() {
        include $this->getViewPath('form.php');
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $data_entrada = $_POST['data_entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                $retorno = $_POST['retorno'] ?? null;
                $saida_final = $_POST['saida_final'] ?? null;
                
                // Validações obrigatórias
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                // Validar placa se não for "APE" (a pé)
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, null, 'profissionais_renner');
                    if (!$placaValidation['isValid']) {
                        throw new Exception($placaValidation['message']);
                    }
                }
                
                // ========== VALIDAÇÕES TEMPORAIS ==========
                // Validar data de entrada
                $entradaValidation = DateTimeValidator::validateEntryDateTime($data_entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $data_entrada = $entradaValidation['normalized'];
                
                // Validar saída se fornecida
                if (!empty($saida)) {
                    $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $data_entrada);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $saida = $saidaValidation['normalized'];
                }
                
                // Validar retorno se fornecido
                if (!empty($retorno)) {
                    $retornoValidation = DateTimeValidator::validateEntryDateTime($retorno);
                    if (!$retornoValidation['isValid']) {
                        throw new Exception($retornoValidation['message']);
                    }
                    $retorno = $retornoValidation['normalized'];
                }
                
                // Validar saída final se fornecida
                if (!empty($saida_final)) {
                    $saidaFinalValidation = DateTimeValidator::validateExitDateTime($saida_final, $retorno ?: $data_entrada);
                    if (!$saidaFinalValidation['isValid']) {
                        throw new Exception($saidaFinalValidation['message']);
                    }
                    $saida_final = $saidaFinalValidation['normalized'];
                }
                // ==========================================
                
                $this->db->query("
                    INSERT INTO profissionais_renner (nome, data_entrada, saida, retorno, saida_final, setor, placa_veiculo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome,
                    $data_entrada,
                    $saida ?: null,
                    $retorno ?: null,
                    $saida_final ?: null,
                    $setor,
                    $placa_veiculo
                ]);
                
                header('Location: ' . $this->getBaseRoute() . '?success=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
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
        
        $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
        if (!$profissional) {
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
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $data_entrada = $_POST['data_entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                $retorno = $_POST['retorno'] ?? null;
                $saida_final = $_POST['saida_final'] ?? null;
                
                // Validações obrigatórias
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                // Validar placa se não for "APE" (a pé)
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, $id, 'profissionais_renner');
                    if (!$placaValidation['isValid']) {
                        throw new Exception($placaValidation['message']);
                    }
                }
                
                // Buscar dados atuais para usar como baseline
                $profissionalAtual = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
                if (!$profissionalAtual) {
                    throw new Exception("Profissional não encontrado");
                }
                
                // ========== VALIDAÇÕES TEMPORAIS ==========
                // Validar data de entrada se fornecida
                if (!empty($data_entrada)) {
                    $entradaValidation = DateTimeValidator::validateEntryDateTime($data_entrada);
                    if (!$entradaValidation['isValid']) {
                        throw new Exception($entradaValidation['message']);
                    }
                    $data_entrada = $entradaValidation['normalized'];
                }
                
                // Validar saída se fornecida
                if (!empty($saida)) {
                    // Use data_entrada atual (modificada ou existente) como baseline
                    $entradaBaseline = $data_entrada ?: $profissionalAtual['data_entrada'];
                    $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $entradaBaseline);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $saida = $saidaValidation['normalized'];
                }
                
                // Validar retorno se fornecido
                if (!empty($retorno)) {
                    $retornoValidation = DateTimeValidator::validateEntryDateTime($retorno);
                    if (!$retornoValidation['isValid']) {
                        throw new Exception($retornoValidation['message']);
                    }
                    $retorno = $retornoValidation['normalized'];
                }
                
                // Validar saída final se fornecida
                if (!empty($saida_final)) {
                    // Use retorno atual (modificado ou existente) ou data_entrada como baseline
                    $retornoBaseline = $retorno ?: $profissionalAtual['retorno'];
                    $entradaBaseline = $data_entrada ?: $profissionalAtual['data_entrada'];
                    $baselineParaSaidaFinal = $retornoBaseline ?: $entradaBaseline;
                    
                    $saidaFinalValidation = DateTimeValidator::validateExitDateTime($saida_final, $baselineParaSaidaFinal);
                    if (!$saidaFinalValidation['isValid']) {
                        throw new Exception($saidaFinalValidation['message']);
                    }
                    $saida_final = $saidaFinalValidation['normalized'];
                }
                // ==========================================
                
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET nome = ?, data_entrada = ?, saida = ?, retorno = ?, saida_final = ?, setor = ?, placa_veiculo = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome,
                    $data_entrada ?: null,
                    $saida ?: null,
                    $retorno ?: null,
                    $saida_final ?: null,
                    $setor,
                    $placa_veiculo,
                    $id
                ]);
                
                header('Location: ' . $this->getBaseRoute() . '?updated=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$_POST['id']]);
                include $this->getViewPath('form.php');
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
                // Verificação CSRF manual para retorno JSON adequado
                if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'])) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $data_entrada_input = trim($_POST['data_entrada'] ?? '');
                
                // Validações obrigatórias
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                // Validar placa se não for "APE" (a pé)
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, null, 'profissionais_renner');
                    if (!$placaValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $placaValidation['message']]);
                        return;
                    }
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
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                $this->db->query("
                    INSERT INTO profissionais_renner (nome, data_entrada, setor, placa_veiculo)
                    VALUES (?, ?, ?, ?)
                ", [
                    $nome, $data_entrada, $setor, $placa_veiculo
                ]);
                
                $id = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional cadastrado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Profissional Renner',

                        'setor' => $setor,
                        'placa_veiculo' => $placa_veiculo,
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
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $saida_final = trim($_POST['saida_final'] ?? '');
                
                // Validações obrigatórias
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                // Validar placa se não for "APE" (a pé)
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, $id, 'profissionais_renner');
                    if (!$placaValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $placaValidation['message']]);
                        return;
                    }
                }
                
                // Parse saida_final if provided
                $saida_final_parsed = null;
                if (!empty($saida_final)) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $saida_final);
                    if (!$date) {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $saida_final);
                    }
                    if ($date) {
                        $saida_final_parsed = $date->format('Y-m-d H:i:s');
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de saída inválido']);
                        return;
                    }
                }
                
                // Normalizar placa de veículo
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET nome = ?, setor = ?, placa_veiculo = ?, saida_final = ?
                    WHERE id = ?
                ", [$nome, $setor, $placa_veiculo, $saida_final ?: null, $id]);
                
                // Buscar dados atualizados para retornar
                $profissionalAtualizado = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Profissional Renner',
                        'cpf' => $cpf,
                        'empresa' => $empresa,
                        'setor' => $setor,
                        'funcionario_responsavel' => '',
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $profissionalAtualizado['retorno'] ?? $profissionalAtualizado['data_entrada'] ?? null,
                        'saida_final' => $profissionalAtualizado['saida_final'] ?? null
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
                
                // Buscar o profissional
                $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
                
                if (!$profissional) {
                    echo json_encode(['success' => false, 'message' => 'Profissional não encontrado']);
                    return;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $profissional['id'],
                        'nome' => $profissional['nome'],
                        'setor' => $profissional['setor'],
                        'data_entrada' => $profissional['data_entrada'],
                        'saida' => $profissional['saida'],
                        'retorno' => $profissional['retorno'],
                        'saida_final' => $profissional['saida_final']
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
                
                // Buscar o profissional
                $profissional = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
                
                if (!$profissional) {
                    echo json_encode(['success' => false, 'message' => 'Profissional não encontrado']);
                    return;
                }
                
                // Registrar saída final
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET saida_final = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Saída registrada com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $profissional['nome']
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