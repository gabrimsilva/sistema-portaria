<?php

require_once __DIR__ . '/../services/DuplicityValidationService.php';
require_once __DIR__ . '/../utils/DateTimeValidator.php';

class ProfissionaisRennerController {
    private $db;
    private $duplicityService;
    private $auditService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
        $this->duplicityService = new DuplicityValidationService();
        $this->auditService = new AuditService();
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
        $data_inicial = $_GET['data_inicial'] ?? '';
        $data_final = $_GET['data_final'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $pageSize = max(1, min(100, intval($_GET['pageSize'] ?? 20)));
        $offset = ($page - 1) * $pageSize;
        
        $isReport = strpos($_SERVER['REQUEST_URI'] ?? '', '/reports/') !== false;
        
        if ($isReport) {
            $query = "SELECT r.id, p.nome, p.setor, r.placa_veiculo, r.entrada_at as data_entrada, r.saida_at as saida, r.retorno, r.saida_final 
                      FROM registro_acesso r 
                      JOIN profissionais_renner p ON p.id = r.profissional_renner_id 
                      WHERE r.tipo = 'profissional_renner'";
        } else {
            $query = "SELECT p.* FROM profissionais_renner p WHERE 1=1";
        }
        $params = [];
        
        if ($isReport) {
            // Filtro por período (data inicial e/ou final)
            if (!empty($data_inicial) && !empty($data_final)) {
                // Ambas as datas: filtrar entre elas
                $query .= " AND DATE(r.entrada_at) BETWEEN ? AND ?";
                $params[] = $data_inicial;
                $params[] = $data_final;
            } elseif (!empty($data_inicial)) {
                // Apenas data inicial: a partir dela
                $query .= " AND DATE(r.entrada_at) >= ?";
                $params[] = $data_inicial;
            } elseif (!empty($data_final)) {
                // Apenas data final: até ela
                $query .= " AND DATE(r.entrada_at) <= ?";
                $params[] = $data_final;
            }
        }
        
        if (!empty($search)) {
            if ($isReport) {
                $query .= " AND (p.nome ILIKE ? OR p.doc_number ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            } else {
                $query .= " AND (nome ILIKE ? OR doc_number ILIKE ? OR setor ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
        }
        
        if (!empty($setor)) {
            if ($isReport) {
                $query .= " AND p.setor = ?";
            } else {
                $query .= " AND setor = ?";
            }
            $params[] = $setor;
        }
        
        if ($status === 'ativo') {
            if ($isReport) {
                $query .= " AND r.saida_final IS NULL";
            }
        } elseif ($status === 'saiu') {
            if ($isReport) {
                $query .= " AND r.saida_final IS NOT NULL";
            }
        }
        
        $total = 0;
        $pagination = null;
        if ($isReport) {
            $countQuery = "SELECT COUNT(*) 
                           FROM registro_acesso r 
                           JOIN profissionais_renner p ON p.id = r.profissional_renner_id 
                           WHERE r.tipo = 'profissional_renner'";
            $countParams = [];
            
            // Filtro por período no count também
            if (!empty($data_inicial) && !empty($data_final)) {
                $countQuery .= " AND DATE(r.entrada_at) BETWEEN ? AND ?";
                $countParams[] = $data_inicial;
                $countParams[] = $data_final;
            } elseif (!empty($data_inicial)) {
                $countQuery .= " AND DATE(r.entrada_at) >= ?";
                $countParams[] = $data_inicial;
            } elseif (!empty($data_final)) {
                $countQuery .= " AND DATE(r.entrada_at) <= ?";
                $countParams[] = $data_final;
            }
            if (!empty($search)) {
                $countQuery .= " AND p.nome ILIKE ?";
                $countParams[] = "%$search%";
            }
            if (!empty($setor)) {
                $countQuery .= " AND p.setor = ?";
                $countParams[] = $setor;
            }
            if ($status === 'ativo') {
                $countQuery .= " AND r.saida_final IS NULL";
            } elseif ($status === 'saiu') {
                $countQuery .= " AND r.saida_final IS NOT NULL";
            }
            
            $totalResult = $this->db->fetch($countQuery, $countParams);
            $total = $totalResult['count'] ?? 0;
            
            $pagination = [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize)
            ];
        }
        
        if ($isReport) {
            $query .= " ORDER BY r.entrada_at DESC LIMIT ? OFFSET ?";
            $params[] = $pageSize;
            $params[] = $offset;
        } else {
            $query .= " ORDER BY created_at DESC";
        }
        
        $profissionais = $this->db->fetchAll($query, $params);
        
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
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, null, 'registro_acesso');
                    if (!$placaValidation['isValid']) {
                        throw new Exception($placaValidation['message']);
                    }
                }
                
                $entradaValidation = DateTimeValidator::validateEntryDateTime($data_entrada);
                if (!$entradaValidation['isValid']) {
                    throw new Exception($entradaValidation['message']);
                }
                $data_entrada = $entradaValidation['normalized'];
                
                if (!empty($saida)) {
                    $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $data_entrada);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $saida = $saidaValidation['normalized'];
                }
                
                if (!empty($retorno)) {
                    $retornoValidation = DateTimeValidator::validateEntryDateTime($retorno);
                    if (!$retornoValidation['isValid']) {
                        throw new Exception($retornoValidation['message']);
                    }
                    $retorno = $retornoValidation['normalized'];
                }
                
                if (!empty($saida_final)) {
                    $saidaFinalValidation = DateTimeValidator::validateExitDateTime($saida_final, $retorno ?: $data_entrada);
                    if (!$saidaFinalValidation['isValid']) {
                        throw new Exception($saidaFinalValidation['message']);
                    }
                    $saida_final = $saidaFinalValidation['normalized'];
                }
                
                $this->db->beginTransaction();
                
                $profissional = $this->db->fetch("SELECT id FROM profissionais_renner WHERE nome = ? AND setor = ?", [$nome, $setor]);
                
                if ($profissional) {
                    $profissional_id = $profissional['id'];
                } else {
                    $this->db->query("INSERT INTO profissionais_renner (nome, setor) VALUES (?, ?)", [$nome, $setor]);
                    $profissional_id = $this->db->lastInsertId();
                }
                
                $this->db->query("
                    INSERT INTO registro_acesso (tipo, nome, setor, placa_veiculo, entrada_at, saida_at, retorno, saida_final, profissional_renner_id, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    'profissional_renner',
                    $nome,
                    $setor,
                    $placa_veiculo,
                    $data_entrada,
                    $saida ?: null,
                    $retorno ?: null,
                    $saida_final ?: null,
                    $profissional_id,
                    $_SESSION['user_id'] ?? null
                ]);
                
                $this->db->commit();
                
                // Registrar auditoria
                $registro_id = $this->db->lastInsertId();
                $this->auditService->log(
                    'create',
                    'registro_acesso',
                    $registro_id,
                    null,
                    [
                        'tipo' => 'profissional_renner',
                        'nome' => $nome,
                        'setor' => $setor,
                        'placa_veiculo' => $placa_veiculo,
                        'profissional_renner_id' => $profissional_id
                    ]
                );
                
                header('Location: ' . $this->getBaseRoute() . '?success=1');
                exit;
            } catch (Exception $e) {
                $this->db->rollback();
                $error = $e->getMessage();
                include $this->getViewPath('form.php');
            }
        }
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $id = $_POST['id'] ?? null;
                $registro_id = $_POST['registro_id'] ?? null;
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $data_entrada = $_POST['data_entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                $retorno = $_POST['retorno'] ?? null;
                $saida_final = $_POST['saida_final'] ?? null;
                
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, $registro_id, 'registro_acesso');
                    if (!$placaValidation['isValid']) {
                        throw new Exception($placaValidation['message']);
                    }
                }
                
                $registroAtual = null;
                if ($registro_id) {
                    $registroAtual = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$registro_id]);
                }
                
                if (!empty($data_entrada)) {
                    $entradaValidation = DateTimeValidator::validateEntryDateTime($data_entrada);
                    if (!$entradaValidation['isValid']) {
                        throw new Exception($entradaValidation['message']);
                    }
                    $data_entrada = $entradaValidation['normalized'];
                }
                
                if (!empty($saida)) {
                    $entradaBaseline = $data_entrada ?: ($registroAtual['entrada_at'] ?? null);
                    $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $entradaBaseline);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $saida = $saidaValidation['normalized'];
                }
                
                if (!empty($retorno)) {
                    $retornoValidation = DateTimeValidator::validateEntryDateTime($retorno);
                    if (!$retornoValidation['isValid']) {
                        throw new Exception($retornoValidation['message']);
                    }
                    $retorno = $retornoValidation['normalized'];
                }
                
                if (!empty($saida_final)) {
                    $retornoBaseline = $retorno ?: ($registroAtual['retorno'] ?? null);
                    $entradaBaseline = $data_entrada ?: ($registroAtual['entrada_at'] ?? null);
                    $baselineParaSaidaFinal = $retornoBaseline ?: $entradaBaseline;
                    
                    $saidaFinalValidation = DateTimeValidator::validateExitDateTime($saida_final, $baselineParaSaidaFinal);
                    if (!$saidaFinalValidation['isValid']) {
                        throw new Exception($saidaFinalValidation['message']);
                    }
                    $saida_final = $saidaFinalValidation['normalized'];
                }
                
                $this->db->beginTransaction();
                
                $this->db->query("
                    UPDATE profissionais_renner 
                    SET nome = ?, setor = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$nome, $setor, $id]);
                
                if ($registro_id) {
                    $this->db->query("
                        UPDATE registro_acesso 
                        SET nome = ?, setor = ?, placa_veiculo = ?, entrada_at = ?, saida_at = ?, retorno = ?, saida_final = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ", [
                        $nome,
                        $setor,
                        $placa_veiculo,
                        $data_entrada ?: $registroAtual['entrada_at'],
                        $saida ?: null,
                        $retorno ?: null,
                        $saida_final ?: null,
                        $_SESSION['user_id'] ?? null,
                        $registro_id
                    ]);
                }
                
                $this->db->commit();
                
                header('Location: ' . $this->getBaseRoute() . '?updated=1');
                exit;
            } catch (Exception $e) {
                $this->db->rollback();
                $error = $e->getMessage();
                $profissional = $this->db->fetch("
                    SELECT p.*, r.placa_veiculo, r.entrada_at as data_entrada, r.saida_at as saida, r.retorno, r.saida_final, r.id as registro_id
                    FROM profissionais_renner p
                    LEFT JOIN registro_acesso r ON r.profissional_renner_id = p.id AND r.tipo = 'profissional_renner'
                    WHERE p.id = ?
                    ORDER BY r.entrada_at DESC
                    LIMIT 1
                ", [$_POST['id']]);
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
        
        // Buscar dados do registro
        $registro = $this->db->fetch("
            SELECT r.*, p.nome, p.setor
            FROM registro_acesso r
            LEFT JOIN profissionais_renner p ON p.id = r.profissional_renner_id
            WHERE r.id = ? AND r.tipo = 'profissional_renner'
        ", [$id]);
        
        if (!$registro) {
            header('Location: ' . $this->getBaseRoute());
            exit;
        }
        
        // Buscar informação de entrada retroativa no audit log
        $auditLog = $this->db->fetch("
            SELECT justificativa, is_retroactive
            FROM audit_log
            WHERE entidade = 'registro_acesso' 
              AND entidade_id = ?
              AND acao = 'create'
              AND is_retroactive = true
            ORDER BY timestamp DESC
            LIMIT 1
        ", [$id]);
        
        $is_retroativa = false;
        $observacao_retroativa = null;
        
        if ($auditLog && $auditLog['is_retroactive']) {
            $is_retroativa = true;
            $observacao_retroativa = $auditLog['justificativa'] ?? null;
        }
        
        // Passar dados para a view
        $profissional = $registro;
        $profissional['is_retroativa'] = $is_retroativa;
        $profissional['observacao_retroativa'] = $observacao_retroativa;
        
        include $this->getViewPath('edit.php');
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            
            require_once __DIR__ . '/../services/AuthorizationService.php';
            $authService = new AuthorizationService();
            
            if (!$authService->hasPermission('profissionais_renner.excluir')) {
                $_SESSION['error'] = 'Você não tem permissão para excluir profissionais';
                header('Location: /profissionais-renner');
                exit;
            }
            
            $id = $_POST['id'] ?? null;
            
            if ($id) {
                try {
                    $hasHistory = $this->db->fetch(
                        "SELECT COUNT(*) as count FROM registro_acesso WHERE profissional_renner_id = ?", 
                        [$id]
                    );
                    
                    if ($hasHistory && $hasHistory['count'] > 0) {
                        $_SESSION['error'] = 'Não é possível excluir este profissional pois existem ' . $hasHistory['count'] . ' registro(s) de acesso associados. Para preservar o histórico de auditoria, a exclusão foi bloqueada.';
                    } else {
                        // Capturar dados antes da exclusão
                        $profissionalAntes = $this->db->fetch("SELECT * FROM profissionais_renner WHERE id = ?", [$id]);
                        
                        $this->db->query("DELETE FROM profissionais_renner WHERE id = ?", [$id]);
                        
                        // Registrar auditoria
                        $this->auditService->log(
                            'delete',
                            'profissionais_renner',
                            $id,
                            $profissionalAntes,
                            null
                        );
                        
                        $_SESSION['success'] = 'Profissional excluído com sucesso';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Erro ao excluir profissional: ' . $e->getMessage();
                }
            }
        }
        
        header('Location: /profissionais-renner');
        exit;
    }
    
    public function saveAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'])) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $data_entrada_input = trim($_POST['data_entrada'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                
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
                
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, null, 'registro_acesso');
                    if (!$placaValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $placaValidation['message']]);
                        return;
                    }
                }
                
                // Processar data de entrada
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
                
                // Detectar entrada retroativa (data no passado)
                $agora = time();
                $timestamp_entrada = strtotime($data_entrada);
                $is_retroativa = $timestamp_entrada < $agora;
                
                // Validar observação obrigatória para entradas retroativas
                if ($is_retroativa && empty($observacao)) {
                    echo json_encode(['success' => false, 'message' => 'Observação/justificativa é obrigatória para entradas retroativas']);
                    return;
                }
                
                $this->db->beginTransaction();
                
                $profissional = $this->db->fetch("SELECT id FROM profissionais_renner WHERE nome = ? AND setor = ?", [$nome, $setor]);
                
                if ($profissional) {
                    $profissional_id = $profissional['id'];
                } else {
                    $this->db->query("INSERT INTO profissionais_renner (nome, setor) VALUES (?, ?)", [$nome, $setor]);
                    $profissional_id = $this->db->lastInsertId();
                }
                
                // Inserir com doc_type e doc_number explicitamente NULL para evitar defaults que violam constraint
                $this->db->query("
                    INSERT INTO registro_acesso (tipo, nome, setor, placa_veiculo, entrada_at, profissional_renner_id, created_by, doc_type, doc_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)
                ", [
                    'profissional_renner',
                    $nome,
                    $setor,
                    $placa_veiculo,
                    $data_entrada,
                    $profissional_id,
                    $_SESSION['user_id'] ?? null
                ]);
                
                $registro_id = $this->db->lastInsertId();
                
                $this->db->commit();
                
                // Registrar auditoria (incluir observação se entrada retroativa)
                $auditData = [
                    'tipo' => 'profissional_renner',
                    'nome' => $nome,
                    'setor' => $setor,
                    'placa_veiculo' => $placa_veiculo,
                    'profissional_renner_id' => $profissional_id,
                    'entrada_at' => $data_entrada
                ];
                
                if ($is_retroativa) {
                    $auditData['entrada_retroativa'] = true;
                    $auditData['justificativa'] = $observacao;
                    $auditData['diferenca_tempo'] = $agora - $timestamp_entrada;
                }
                
                $this->auditService->log(
                    'create',
                    'registro_acesso',
                    $registro_id,
                    null,
                    $auditData
                );
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional cadastrado com sucesso',
                    'data' => [
                        'id' => $registro_id,
                        'nome' => $nome,
                        'tipo' => 'Profissional Renner',
                        'setor' => $setor,
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $data_entrada
                    ]
                ]);
            } catch (Exception $e) {
                $this->db->rollback();
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
                if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'])) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                
                $id = trim($_POST['id'] ?? '');
                $nome = trim($_POST['nome'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $saida_final = trim($_POST['saida_final'] ?? '');
                $saida = trim($_POST['saida'] ?? '');
                $retorno = trim($_POST['retorno'] ?? '');
                
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
                
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
                
                $registro = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ? AND tipo = 'profissional_renner'", [$id]);
                if (!$registro) {
                    echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
                    return;
                }
                
                if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                    $placaValidation = $this->duplicityService->validatePlacaNotOpen($placa_veiculo, $id, 'registro_acesso');
                    if (!$placaValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $placaValidation['message']]);
                        return;
                    }
                }
                
                $saida_parsed = null;
                if (!empty($saida)) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $saida);
                    if (!$date) {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $saida);
                    }
                    if ($date) {
                        $saida_parsed = $date->format('Y-m-d H:i:s');
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de saída intermediária inválido']);
                        return;
                    }
                }
                
                $retorno_parsed = null;
                if (!empty($retorno)) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $retorno);
                    if (!$date) {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $retorno);
                    }
                    if ($date) {
                        $retorno_parsed = $date->format('Y-m-d H:i:s');
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de retorno inválido']);
                        return;
                    }
                }
                
                $saida_final_parsed = null;
                if (!empty($saida_final)) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $saida_final);
                    if (!$date) {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $saida_final);
                    }
                    if ($date) {
                        $saida_final_parsed = $date->format('Y-m-d H:i:s');
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de saída final inválido']);
                        return;
                    }
                }
                
                $this->db->beginTransaction();
                
                $profissional_id = $registro['profissional_renner_id'];
                
                if ($profissional_id) {
                    $this->db->query("
                        UPDATE profissionais_renner 
                        SET nome = ?, setor = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ", [$nome, $setor, $profissional_id]);
                }
                
                $this->db->query("
                    UPDATE registro_acesso 
                    SET nome = ?, setor = ?, placa_veiculo = ?, saida_at = ?, retorno = ?, saida_final = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$nome, $setor, $placa_veiculo, $saida_parsed, $retorno_parsed, $saida_final_parsed, $_SESSION['user_id'] ?? null, $id]);
                
                $this->db->commit();
                
                $registroAtualizado = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profissional atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $nome,
                        'tipo' => 'Profissional Renner',
                        'cpf' => '',
                        'empresa' => '',
                        'setor' => $setor,
                        'funcionario_responsavel' => '',
                        'placa_veiculo' => $placa_veiculo,
                        'hora_entrada' => $registroAtualizado['retorno'] ?? $registroAtualizado['entrada_at'] ?? null,
                        'saida' => $registroAtualizado['saida_at'] ?? null,
                        'retorno' => $registroAtualizado['retorno'] ?? null,
                        'saida_final' => $registroAtualizado['saida_final'] ?? null
                    ]
                ]);
            } catch (Exception $e) {
                $this->db->rollback();
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
                
                $registro = $this->db->fetch("
                    SELECT r.*, p.nome, p.setor
                    FROM registro_acesso r
                    LEFT JOIN profissionais_renner p ON p.id = r.profissional_renner_id
                    WHERE r.id = ? AND r.tipo = 'profissional_renner'
                ", [$id]);
                
                if (!$registro) {
                    echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
                    return;
                }
                
                // Buscar informação de entrada retroativa no audit log
                $auditLog = $this->db->fetch("
                    SELECT justificativa, is_retroactive
                    FROM audit_log
                    WHERE entidade = 'registro_acesso' 
                      AND entidade_id = ?
                      AND acao = 'create'
                      AND is_retroactive = true
                    ORDER BY timestamp DESC
                    LIMIT 1
                ", [$id]);
                
                $is_retroativa = false;
                $observacao_retroativa = null;
                
                if ($auditLog && $auditLog['is_retroactive']) {
                    $is_retroativa = true;
                    $observacao_retroativa = $auditLog['justificativa'] ?? null;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $registro['id'],
                        'nome' => $registro['nome'],
                        'setor' => $registro['setor'],
                        'data_entrada' => $registro['entrada_at'],
                        'saida' => $registro['saida_at'],
                        'retorno' => $registro['retorno'],
                        'saida_final' => $registro['saida_final'],
                        'is_retroativa' => $is_retroativa,
                        'observacao_retroativa' => $observacao_retroativa
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
                
                $registro = $this->db->fetch("
                    SELECT * FROM registro_acesso 
                    WHERE id = ? AND tipo = 'profissional_renner'
                ", [$id]);
                
                if (!$registro) {
                    echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
                    return;
                }
                
                $this->db->query("
                    UPDATE registro_acesso 
                    SET saida_final = CURRENT_TIMESTAMP, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$_SESSION['user_id'] ?? null, $id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Saída registrada com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $registro['nome']
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
    
    /**
     * Editar registro inline (usado nos relatórios)
     * Requer permissão: relatorios.editar_linha
     */
    public function editInlineAjax() {
        header('Content-Type: application/json');
        
        // Verificar autenticação
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        // Verificar permissão
        require_once __DIR__ . '/../services/AuthorizationService.php';
        $authService = new AuthorizationService();
        
        if (!$authService->hasPermission('relatorios.editar_linha')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar registros inline']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID é obrigatório');
            }
            
            $registro = $this->db->fetch("
                SELECT r.*, p.id as profissional_id 
                FROM registro_acesso r
                LEFT JOIN profissionais_renner p ON p.id = r.profissional_renner_id
                WHERE r.id = ? AND r.tipo = 'profissional_renner'
            ", [$id]);
            
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            $this->db->beginTransaction();
            
            $updateProfissionalFields = [];
            $updateProfissionalParams = [];
            
            $updateRegistroFields = [];
            $updateRegistroParams = [];
            
            if (isset($_POST['nome'])) {
                $nome = trim($_POST['nome']);
                $updateProfissionalFields[] = 'nome = ?';
                $updateProfissionalParams[] = $nome;
                $updateRegistroFields[] = 'nome = ?';
                $updateRegistroParams[] = $nome;
            }
            
            if (isset($_POST['setor'])) {
                $setor = trim($_POST['setor']);
                $updateProfissionalFields[] = 'setor = ?';
                $updateProfissionalParams[] = $setor;
                $updateRegistroFields[] = 'setor = ?';
                $updateRegistroParams[] = $setor;
            }
            
            if (isset($_POST['placa_veiculo'])) {
                $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa_veiculo']));
                $updateRegistroFields[] = 'placa_veiculo = ?';
                $updateRegistroParams[] = $placa;
            }
            
            if (isset($_POST['data_entrada'])) {
                $updateRegistroFields[] = 'entrada_at = ?';
                $updateRegistroParams[] = $_POST['data_entrada'] ?: null;
            }
            
            if (isset($_POST['saida'])) {
                $updateRegistroFields[] = 'saida_at = ?';
                $updateRegistroParams[] = $_POST['saida'] ?: null;
            }
            
            if (isset($_POST['retorno'])) {
                $updateRegistroFields[] = 'retorno = ?';
                $updateRegistroParams[] = $_POST['retorno'] ?: null;
            }
            
            if (isset($_POST['saida_final'])) {
                $updateRegistroFields[] = 'saida_final = ?';
                $updateRegistroParams[] = $_POST['saida_final'] ?: null;
            }
            
            if (empty($updateRegistroFields)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            if (!empty($updateProfissionalFields) && $registro['profissional_id']) {
                $updateProfissionalFields[] = 'updated_at = CURRENT_TIMESTAMP';
                $updateProfissionalParams[] = $registro['profissional_id'];
                $query = "UPDATE profissionais_renner SET " . implode(', ', $updateProfissionalFields) . " WHERE id = ?";
                $this->db->query($query, $updateProfissionalParams);
            }
            
            $updateRegistroFields[] = 'updated_by = ?';
            $updateRegistroParams[] = $_SESSION['user_id'] ?? null;
            $updateRegistroFields[] = 'updated_at = CURRENT_TIMESTAMP';
            $updateRegistroParams[] = $id;
            
            $query = "UPDATE registro_acesso SET " . implode(', ', $updateRegistroFields) . " WHERE id = ?";
            $this->db->query($query, $updateRegistroParams);
            
            $this->db->commit();
            
            $registroAtualizado = $this->db->fetch("
                SELECT r.*, r.entrada_at as data_entrada, r.saida_at as saida
                FROM registro_acesso r
                WHERE r.id = ?
            ", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro atualizado com sucesso',
                'data' => $registroAtualizado
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Excluir registro inline (usado nos relatórios)
     * Requer permissão: relatorios.excluir_linha
     */
    public function deleteInlineAjax() {
        header('Content-Type: application/json');
        
        // Verificar autenticação
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        // Verificar permissão
        require_once __DIR__ . '/../services/AuthorizationService.php';
        $authService = new AuthorizationService();
        
        if (!$authService->hasPermission('relatorios.excluir_linha')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir registros inline']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID é obrigatório');
            }
            
            $registro = $this->db->fetch("
                SELECT r.* FROM registro_acesso r 
                WHERE r.id = ? AND r.tipo = 'profissional_renner'
            ", [$id]);
            
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            // Excluir registro
            $this->db->query("DELETE FROM registro_acesso WHERE id = ?", [$id]);
            
            // Registrar auditoria
            $this->auditService->log(
                'delete',
                'registro_acesso',
                $id,
                $registro,
                null
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro excluído com sucesso',
                'data' => ['id' => $id, 'nome' => $registro['nome']]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    private function sanitizeForCsv($value) {
        // Proteção contra CSV Formula Injection
        if (empty($value)) return '';
        $value = (string)$value;
        
        // Se começa com caracteres perigosos, adiciona aspas simples no início
        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r", "\n"])) {
            $value = "'" . $value;
        }
        
        return $value;
    }
    
    public function export() {
        // Pegar os mesmos filtros do relatório
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? '';
        $data_inicial = $_GET['data_inicial'] ?? '';
        $data_final = $_GET['data_final'] ?? '';
        
        // Query base para exportação
        $query = "SELECT r.id, p.nome, p.doc_type, p.doc_number, p.doc_country, 
                         p.setor, r.placa_veiculo, r.entrada_at, r.saida_at, r.retorno, r.saida_final 
                  FROM registro_acesso r 
                  JOIN profissionais_renner p ON p.id = r.profissional_renner_id 
                  WHERE r.tipo = 'profissional_renner'";
        
        $params = [];
        
        // Filtro por período (data inicial e/ou final)
        if (!empty($data_inicial) && !empty($data_final)) {
            $query .= " AND DATE(r.entrada_at) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
        } elseif (!empty($data_inicial)) {
            $query .= " AND DATE(r.entrada_at) >= ?";
            $params[] = $data_inicial;
        } elseif (!empty($data_final)) {
            $query .= " AND DATE(r.entrada_at) <= ?";
            $params[] = $data_final;
        }
        
        // Busca geral
        if (!empty($search)) {
            $query .= " AND (p.nome ILIKE ? OR p.doc_number ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $query .= " AND p.setor = ?";
            $params[] = $setor;
        }
        
        // Filtro por status (ativo/saiu)
        if ($status === 'ativo') {
            $query .= " AND r.saida_final IS NULL";
        } elseif ($status === 'saiu') {
            $query .= " AND r.saida_final IS NOT NULL";
        }
        
        $query .= " ORDER BY r.entrada_at DESC";
        
        // Buscar TODOS os dados (sem paginação) para exportação
        $profissionais = $this->db->fetchAll($query, $params);
        
        // Configurar headers para download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_profissionais_renner_' . date('Y-m-d_His') . '.csv"');
        
        // Abrir output stream
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (compatibilidade Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos do CSV
        fputcsv($output, [
            'ID',
            'Nome',
            'Tipo Documento',
            'Número Documento',
            'País Documento',
            'Setor',
            'Placa do Veículo',
            'Entrada',
            'Saída',
            'Retorno',
            'Saída Final'
        ], ';');
        
        // Dados
        foreach ($profissionais as $profissional) {
            fputcsv($output, [
                $this->sanitizeForCsv($profissional['id']),
                $this->sanitizeForCsv($profissional['nome']),
                $this->sanitizeForCsv($profissional['doc_type'] ?? ''),
                $this->sanitizeForCsv($profissional['doc_number'] ?? ''),
                $this->sanitizeForCsv($profissional['doc_country'] ?? ''),
                $this->sanitizeForCsv($profissional['setor'] ?? ''),
                $this->sanitizeForCsv($profissional['placa_veiculo'] ?? ''),
                $this->sanitizeForCsv($profissional['entrada_at'] ?? ''),
                $this->sanitizeForCsv($profissional['saida_at'] ?? ''),
                $this->sanitizeForCsv($profissional['retorno'] ?? ''),
                $this->sanitizeForCsv($profissional['saida_final'] ?? '')
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    public function searchProfissionais() {
        header('Content-Type: application/json');
        
        try {
            $query = $_GET['q'] ?? '';
            
            if (empty($query) || strlen($query) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            
            $sql = "SELECT id, nome, setor, fre, doc_type, doc_number, doc_country
                    FROM profissionais_renner 
                    WHERE nome ILIKE ? OR doc_number ILIKE ? OR setor ILIKE ?
                    ORDER BY nome ASC
                    LIMIT 20";
            
            $searchTerm = "%{$query}%";
            $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
            
            echo json_encode(['success' => true, 'data' => $results]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar profissionais']);
        }
        exit;
    }
}