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
        // Detectar se estamos em contexto de relatórios
        $isReport = strpos($_SERVER['REQUEST_URI'], '/reports/') !== false;
        
        if ($isReport) {
            $this->handleReportsIndex();
        } else {
            $this->handleRegularIndex();
        }
    }
    
    private function handleRegularIndex() {
        // Código atual da listagem regular
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $empresa = $_GET['empresa'] ?? '';
        
        $query = "SELECT id, nome, cpf, empresa, setor, funcionario_responsavel, placa_veiculo, entrada, saida, observacao, created_at, updated_at FROM prestadores_servico WHERE 1=1";
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
    
    private function handleReportsIndex() {
        // Configurar timezone
        date_default_timezone_set('America/Sao_Paulo');
        
        // Parâmetros de filtro
        $data_inicial = $_GET['data_inicial'] ?? '';
        $data_final = $_GET['data_final'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? 'todos'; // todos|aberto|finalizado
        $empresa = $_GET['empresa'] ?? '';
        $responsavel = $_GET['responsavel'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        // Query base para relatórios (usando view consolidada - BUG FIX v2.0.0)
        $query = "
            SELECT 
                id,
                nome,
                setor,
                CASE 
                    WHEN placa_veiculo IS NULL OR placa_veiculo = '' OR placa_veiculo = 'APE' THEN 'A pé'
                    ELSE UPPER(placa_veiculo)
                END as placa_ou_ape,
                empresa,
                funcionario_responsavel,
                doc_type,
                doc_number,
                doc_country,
                cpf,
                entrada,
                saida_consolidada,
                validity_status
            FROM vw_prestadores_consolidado 
            WHERE entrada IS NOT NULL";
        
        $countQuery = "SELECT COUNT(*) as total FROM vw_prestadores_consolidado WHERE entrada IS NOT NULL";
        $params = [];
        $countParams = [];
        
        // Filtro por período (data inicial e/ou final)
        if (!empty($data_inicial) && !empty($data_final)) {
            // Ambas as datas: filtrar entre elas
            $query .= " AND DATE(entrada) BETWEEN ? AND ?";
            $countQuery .= " AND DATE(entrada) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
            $countParams[] = $data_inicial;
            $countParams[] = $data_final;
        } elseif (!empty($data_inicial)) {
            // Apenas data inicial: a partir dela
            $query .= " AND DATE(entrada) >= ?";
            $countQuery .= " AND DATE(entrada) >= ?";
            $params[] = $data_inicial;
            $countParams[] = $data_inicial;
        } elseif (!empty($data_final)) {
            // Apenas data final: até ela
            $query .= " AND DATE(entrada) <= ?";
            $countQuery .= " AND DATE(entrada) <= ?";
            $params[] = $data_final;
            $countParams[] = $data_final;
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $countQuery .= " AND setor = ?";
            $params[] = $setor;
            $countParams[] = $setor;
        }
        
        // Filtro por status (usando saida - campo correto para prestadores)
        if ($status === 'aberto') {
            $query .= " AND saida IS NULL";
            $countQuery .= " AND saida IS NULL";
        } elseif ($status === 'finalizado') {
            $query .= " AND saida IS NOT NULL";
            $countQuery .= " AND saida IS NOT NULL";
        }
        
        // Filtro por empresa
        if (!empty($empresa)) {
            $query .= " AND empresa ILIKE ?";
            $countQuery .= " AND empresa ILIKE ?";
            $params[] = "%$empresa%";
            $countParams[] = "%$empresa%";
        }
        
        // Filtro por responsável
        if (!empty($responsavel)) {
            $query .= " AND funcionario_responsavel ILIKE ?";
            $countQuery .= " AND funcionario_responsavel ILIKE ?";
            $params[] = "%$responsavel%";
            $countParams[] = "%$responsavel%";
        }
        
        // Ordenação e paginação
        $query .= " ORDER BY entrada DESC LIMIT ? OFFSET ?";
        $params[] = $pageSize;
        $params[] = $offset;
        
        // Executar consultas
        $prestadores = $this->db->fetchAll($query, $params);
        $totalResult = $this->db->fetch($countQuery, $countParams);
        $total = $totalResult['total'];
        
        // Mascarar CPFs se necessário
        $canViewFullCpf = $this->canViewFullCpf();
        foreach ($prestadores as &$prestador) {
            if (!$canViewFullCpf) {
                $prestador['cpf'] = $this->maskCpf($prestador['cpf']);
            }
        }
        
        // Dados para filtros
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM prestadores_servico WHERE setor IS NOT NULL ORDER BY setor");
        $empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM prestadores_servico WHERE empresa IS NOT NULL ORDER BY empresa");
        $responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM prestadores_servico WHERE funcionario_responsavel IS NOT NULL ORDER BY funcionario_responsavel");
        
        // Dados de paginação
        $totalPages = ceil($total / $pageSize);
        $pagination = [
            'current' => $page,
            'total' => $totalPages,
            'pageSize' => $pageSize,
            'totalItems' => $total
        ];
        
        include $this->getViewPath('list.php');
    }
    
    private function canViewFullCpf() {
        // LGPD: Mascarar CPF na seção de relatórios
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUri, '/reports/') !== false) {
            return false; // Mascarar CPF em relatórios
        }
        
        // Em outras seções, permitir visualização completa (RBAC futuro)
        return true;
    }
    
    private function maskCpf($cpf) {
        if (empty($cpf)) return '';
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return '***.***.***-' . substr($cpf, -2);
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
                $doc_type = $_POST['doc_type'] ?? '';
                $doc_number = $_POST['doc_number'] ?? '';
                $doc_country = $_POST['doc_country'] ?? 'Brasil';
                $empresa = $_POST['empresa'] ?? '';
                $funcionario_responsavel = $_POST['funcionario_responsavel'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
                // ========== VALIDAR E NORMALIZAR DOCUMENTOS ==========
                $effectiveDocType = empty($doc_type) ? 'CPF' : strtoupper($doc_type);
                
                // Normalizar documento baseado no tipo
                if (!empty($doc_number)) {
                    if (in_array($effectiveDocType, ['CPF', 'RG', 'CNH'])) {
                        // Documentos brasileiros: somente dígitos
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        // Documentos internacionais: alfanuméricos
                        $doc_number = strtoupper(trim($doc_number));
                    }
                }
                
                // Sincronizar CPF se tipo for CPF
                if ($effectiveDocType === 'CPF' && !empty($doc_number)) {
                    $cpf = $doc_number;
                }
                
                // Validar CPF apenas se for tipo CPF
                if ($effectiveDocType === 'CPF' && !empty($cpf)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $cpf = $cpfValidation['normalized'];
                    $doc_number = $cpf; // Sincronizar de volta
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ====================================================
                
                // Validações obrigatórias
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($setor)) {
                    throw new Exception("Setor é obrigatório");
                }
                if (empty($doc_number)) {
                    throw new Exception("Número do documento é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Validar país para documentos internacionais
                if (in_array($effectiveDocType, ['PASSAPORTE', 'RNE', 'DNI', 'CI', 'OUTROS']) && empty($doc_country)) {
                    throw new Exception("País é obrigatório para documentos internacionais");
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
                    INSERT INTO prestadores_servico (nome, cpf, empresa, funcionario_responsavel, setor, observacao, placa_veiculo, entrada, saida, doc_type, doc_number, doc_country)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, 
                    $cpf, 
                    $empresa, 
                    $funcionario_responsavel, 
                    $setor, 
                    $observacao, 
                    $placa_veiculo,
                    $entrada,
                    $saida ?: null,
                    empty($doc_type) ? null : $doc_type,
                    $doc_number,
                    $doc_country
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
        
        include __DIR__ . '/../../views/reports/prestadores_servico/edit.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $id = $_POST['id'] ?? null;
                $nome = $_POST['nome'] ?? '';
                $cpf = $_POST['cpf'] ?? '';
                $doc_type = $_POST['doc_type'] ?? '';
                $doc_number = $_POST['doc_number'] ?? '';
                $doc_country = $_POST['doc_country'] ?? 'Brasil';
                $empresa = $_POST['empresa'] ?? '';
                $funcionario_responsavel = $_POST['funcionario_responsavel'] ?? '';
                $setor = $_POST['setor'] ?? '';
                $observacao = $_POST['observacao'] ?? '';
                $placa_veiculo = $_POST['placa_veiculo'] ?? '';
                $entrada = $_POST['entrada'] ?? null;
                $saida = $_POST['saida'] ?? null;
                
                // ========== VALIDAR E NORMALIZAR DOCUMENTOS ==========
                $effectiveDocType = empty($doc_type) ? 'CPF' : strtoupper($doc_type);
                
                // Normalizar documento baseado no tipo
                if (!empty($doc_number)) {
                    if (in_array($effectiveDocType, ['CPF', 'RG', 'CNH'])) {
                        // Documentos brasileiros: somente dígitos
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        // Documentos internacionais: alfanuméricos
                        $doc_number = strtoupper(trim($doc_number));
                    }
                }
                
                // Sincronizar CPF se tipo for CPF
                if ($effectiveDocType === 'CPF' && !empty($doc_number)) {
                    $cpf = $doc_number;
                }
                
                // Validar CPF apenas se for tipo CPF
                if ($effectiveDocType === 'CPF' && !empty($cpf)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $cpf = $cpfValidation['normalized'];
                    $doc_number = $cpf; // Sincronizar de volta
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ====================================================
                
                // Validações obrigatórias (simplificadas para edição)
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($doc_number)) {
                    throw new Exception("Número do documento é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Validar país para documentos internacionais
                if (in_array($effectiveDocType, ['PASSAPORTE', 'RNE', 'DNI', 'CI', 'OUTROS']) && empty($doc_country)) {
                    throw new Exception("País é obrigatório para documentos internacionais");
                }
                
                // ========== VALIDAÇÕES TEMPORAIS (opcionais para edição) ==========
                // Normalizar entrada apenas se fornecida
                if (!empty($entrada)) {
                    $entradaValidation = DateTimeValidator::validateEntryDateTime($entrada);
                    if (!$entradaValidation['isValid']) {
                        throw new Exception($entradaValidation['message']);
                    }
                    $entrada = $entradaValidation['normalized'];
                }
                
                // Normalizar saída apenas se fornecida
                if (!empty($saida)) {
                    $saidaValidation = DateTimeValidator::validateExitDateTime($saida, $entrada);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $saida = $saidaValidation['normalized'];
                } else {
                    $saida = null;
                }
                // ==========================================
                
                // Validação de duplicidade removida para permitir edição livre de saídas
                
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET nome = ?, cpf = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, observacao = ?, placa_veiculo = ?, 
                        entrada = ?, saida = ?, doc_type = ?, doc_number = ?, doc_country = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [
                    $nome, 
                    $cpf, 
                    $empresa, 
                    $funcionario_responsavel, 
                    $setor, 
                    $observacao, 
                    $placa_veiculo,
                    $entrada ?: null,
                    $saida ?: null,
                    empty($doc_type) ? null : $doc_type,
                    $doc_number,
                    $doc_country,
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
                    
                    if (!empty($placa_veiculo) && $placa_veiculo !== 'APE') {
                        $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, $id, 'prestadores_servico');
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
                // Verificação CSRF manual para retorno JSON adequado
                if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'])) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                
                $nome = trim($_POST['nome'] ?? '');
                $cpf = trim($_POST['cpf'] ?? '');
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? 'Brasil');
                $empresa = trim($_POST['empresa'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $entrada_input = trim($_POST['entrada'] ?? '');
                
                // ========== SISTEMA MULTI-DOCUMENTO ==========
                // Se doc_number foi fornecido, usa ele; senão usa CPF legado
                if (!empty($doc_number)) {
                    $effectiveDocType = !empty($doc_type) ? $doc_type : 'CPF';
                    
                    // Normalizar documento baseado no tipo
                    if (in_array($effectiveDocType, ['CPF', 'RG', 'CNH', ''])) {
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        $doc_number = strtoupper(trim($doc_number));
                    }
                    
                    // Sincronizar com CPF para compatibilidade
                    if ($effectiveDocType === 'CPF' || $effectiveDocType === '') {
                        $cpf = $doc_number;
                    }
                    
                    // Converter string vazia para NULL (ENUM do PostgreSQL)
                    if ($doc_type === '') {
                        $doc_type = null;
                    }
                } else {
                    // Fallback para CPF legado
                    $cpf = preg_replace('/\D/', '', $cpf);
                    $doc_type = null; // NULL para ENUM do PostgreSQL
                    $doc_number = $cpf;
                }
                // ===========================================
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                
                // Validações obrigatórias
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($doc_number)) {
                    echo json_encode(['success' => false, 'message' => 'Número do documento é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Validar CPF se o tipo de documento for CPF
                if ($doc_type === 'CPF' || $doc_type === '' || empty($doc_type)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                    if (!$cpfValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $cpfValidation['message']]);
                        return;
                    }
                    $cpf = $cpfValidation['normalized'];
                    $doc_number = $cpfValidation['normalized'];
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
                    INSERT INTO prestadores_servico (nome, cpf, doc_type, doc_number, doc_country, empresa, funcionario_responsavel, setor, observacao, placa_veiculo, entrada)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $nome, $cpf, $doc_type, $doc_number, $doc_country, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo, $entrada
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
                        'funcionario_responsavel' => $funcionario_responsavel,
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
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? 'Brasil');
                $empresa = trim($_POST['empresa'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $observacao = trim($_POST['observacao'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $saida = trim($_POST['saida'] ?? '');
                
                // ========== SISTEMA MULTI-DOCUMENTO ==========
                // Se doc_number foi fornecido, usa ele; senão usa CPF legado
                if (!empty($doc_number)) {
                    $effectiveDocType = !empty($doc_type) ? $doc_type : 'CPF';
                    
                    // Normalizar documento baseado no tipo
                    if (in_array($effectiveDocType, ['CPF', 'RG', 'CNH', ''])) {
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        $doc_number = strtoupper(trim($doc_number));
                    }
                    
                    // Sincronizar com CPF para compatibilidade
                    if ($effectiveDocType === 'CPF' || $effectiveDocType === '') {
                        $cpf = $doc_number;
                    }
                    
                    // Converter string vazia para NULL (ENUM do PostgreSQL)
                    if ($doc_type === '') {
                        $doc_type = null;
                    }
                } else {
                    // Fallback para CPF legado
                    $cpf = preg_replace('/\D/', '', $cpf);
                    $doc_type = null; // NULL para ENUM do PostgreSQL
                    $doc_number = $cpf;
                }
                // ===========================================
                
                // Validações obrigatórias
                if (empty($id) || empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'ID e Nome são obrigatórios']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($doc_number)) {
                    echo json_encode(['success' => false, 'message' => 'Número do documento é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Validar CPF se o tipo de documento for CPF
                if ($doc_type === 'CPF' || $doc_type === '' || empty($doc_type)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                    if (!$cpfValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $cpfValidation['message']]);
                        return;
                    }
                    $cpf = $cpfValidation['normalized'];
                    $doc_number = $cpfValidation['normalized'];
                }
                
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
                    SET nome = ?, cpf = ?, doc_type = ?, doc_number = ?, doc_country = ?, empresa = ?, funcionario_responsavel = ?, setor = ?, observacao = ?, placa_veiculo = ?, saida = ?
                    WHERE id = ?
                ", [$nome, $cpf, $doc_type, $doc_number, $doc_country, $empresa, $funcionario_responsavel, $setor, $observacao, $placa_veiculo, $saida_parsed, $id]);
                
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
                
                // BUG FIX v2.0.0: Atualizar AMBAS as tabelas para manter consistência
                // 1. Atualizar tabela legacy (prestadores_servico)
                $this->db->query("
                    UPDATE prestadores_servico 
                    SET saida = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
                
                // 2. Atualizar tabela de registro de acesso (se existir)
                $this->db->query("
                    UPDATE registro_acesso
                    SET saida_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE tipo = 'prestador_servico' 
                      AND entrada_at = ? 
                      AND nome = ?
                      AND COALESCE(doc_number, cpf) = COALESCE(?, ?)
                      AND saida_at IS NULL
                ", [
                    $prestador['entrada'],
                    $prestador['nome'],
                    $prestador['doc_number'],
                    $prestador['cpf']
                ]);
                
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
    
    /**
     * Editar registro inline (usado nos relatórios)
     * Requer permissão: relatorios.editar_linha
     */
    public function editInlineAjax() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
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
            
            $prestador = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
            if (!$prestador) {
                throw new Exception('Registro não encontrado');
            }
            
            $updateFields = [];
            $updateParams = [];
            
            if (isset($_POST['nome'])) {
                $updateFields[] = 'nome = ?';
                $updateParams[] = trim($_POST['nome']);
            }
            
            if (isset($_POST['cpf'])) {
                $cpf = preg_replace('/\D/', '', $_POST['cpf']);
                $updateFields[] = 'cpf = ?';
                $updateParams[] = $cpf;
            }
            
            if (isset($_POST['empresa'])) {
                $updateFields[] = 'empresa = ?';
                $updateParams[] = trim($_POST['empresa']);
            }
            
            if (isset($_POST['funcionario_responsavel'])) {
                $updateFields[] = 'funcionario_responsavel = ?';
                $updateParams[] = trim($_POST['funcionario_responsavel']);
            }
            
            if (isset($_POST['setor'])) {
                $updateFields[] = 'setor = ?';
                $updateParams[] = trim($_POST['setor']);
            }
            
            if (isset($_POST['placa_ou_ape'])) {
                $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa_ou_ape']));
                $updateFields[] = 'placa_ou_ape = ?';
                $updateParams[] = $placa;
            }
            
            if (isset($_POST['entrada'])) {
                $updateFields[] = 'entrada = ?';
                $updateParams[] = $_POST['entrada'] ?: null;
            }
            
            if (isset($_POST['saida'])) {
                $updateFields[] = 'saida = ?';
                $updateParams[] = $_POST['saida'] ?: null;
            }
            
            if (empty($updateFields)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
            $updateParams[] = $id;
            
            $query = "UPDATE prestadores_servico SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($query, $updateParams);
            
            $prestadorAtualizado = $this->db->fetch("SELECT * FROM prestadores_servico WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro atualizado com sucesso',
                'data' => $prestadorAtualizado
            ]);
            
        } catch (Exception $e) {
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
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
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
            
            $prestador = $this->db->fetch("SELECT nome FROM prestadores_servico WHERE id = ?", [$id]);
            if (!$prestador) {
                throw new Exception('Registro não encontrado');
            }
            
            $this->db->query("DELETE FROM prestadores_servico WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro excluído com sucesso',
                'data' => ['id' => $id, 'nome' => $prestador['nome']]
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
        $data_inicial = $_GET['data_inicial'] ?? '';
        $data_final = $_GET['data_final'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? 'todos';
        $empresa = $_GET['empresa'] ?? '';
        $responsavel = $_GET['responsavel'] ?? '';
        
        // Query base para exportação
        $query = "
            SELECT 
                id,
                nome,
                setor,
                CASE 
                    WHEN placa_veiculo IS NULL OR placa_veiculo = '' OR placa_veiculo = 'APE' THEN 'A pé'
                    ELSE UPPER(placa_veiculo)
                END as placa_ou_ape,
                empresa,
                funcionario_responsavel,
                cpf,
                doc_type,
                doc_number,
                doc_country,
                entrada as entrada,
                saida as saida_at
            FROM prestadores_servico 
            WHERE entrada IS NOT NULL";
        
        $params = [];
        
        // Filtro por período (data inicial e/ou final)
        if (!empty($data_inicial) && !empty($data_final)) {
            $query .= " AND DATE(entrada) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
        } elseif (!empty($data_inicial)) {
            $query .= " AND DATE(entrada) >= ?";
            $params[] = $data_inicial;
        } elseif (!empty($data_final)) {
            $query .= " AND DATE(entrada) <= ?";
            $params[] = $data_final;
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $params[] = $setor;
        }
        
        // Filtro por status (aberto/finalizado)
        if ($status === 'aberto') {
            $query .= " AND saida IS NULL";
        } elseif ($status === 'finalizado') {
            $query .= " AND saida IS NOT NULL";
        }
        
        // Filtro por empresa
        if (!empty($empresa)) {
            $query .= " AND empresa ILIKE ?";
            $params[] = "%$empresa%";
        }
        
        // Filtro por funcionário responsável
        if (!empty($responsavel)) {
            $query .= " AND funcionario_responsavel ILIKE ?";
            $params[] = "%$responsavel%";
        }
        
        $query .= " ORDER BY entrada DESC";
        
        // Buscar TODOS os dados (sem paginação) para exportação
        $prestadores = $this->db->fetchAll($query, $params);
        
        // Configurar headers para download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_prestadores_servico_' . date('Y-m-d_His') . '.csv"');
        
        // Abrir output stream
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (compatibilidade Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos do CSV
        fputcsv($output, [
            'ID',
            'Nome',
            'Tipo de Documento',
            'Documento',
            'Setor',
            'Empresa',
            'Funcionário Responsável',
            'Placa/Veículo',
            'Entrada',
            'Saída'
        ], ';');
        
        // Dados
        foreach ($prestadores as $prestador) {
            // Determinar tipo de documento e aplicar mascaramento LGPD
            $docType = !empty($prestador['doc_type']) ? strtoupper($prestador['doc_type']) : 'CPF';
            $docNumber = $prestador['doc_number'] ?? $prestador['cpf'] ?? '';
            
            // Mascaramento LGPD: mostrar apenas últimos 4 caracteres
            $maskedDoc = '';
            if (!empty($docNumber)) {
                $cleanDoc = preg_replace('/[^0-9A-Za-z]/', '', $docNumber);
                $lastFour = substr($cleanDoc, -4);
                $maskedDoc = '**** ' . $lastFour;
            }
            
            fputcsv($output, [
                $this->sanitizeForCsv($prestador['id']),
                $this->sanitizeForCsv($prestador['nome']),
                $this->sanitizeForCsv($docType),
                $this->sanitizeForCsv($maskedDoc),
                $this->sanitizeForCsv($prestador['setor'] ?? ''),
                $this->sanitizeForCsv($prestador['empresa'] ?? ''),
                $this->sanitizeForCsv($prestador['funcionario_responsavel'] ?? ''),
                $this->sanitizeForCsv($prestador['placa_ou_ape']),
                $this->sanitizeForCsv($prestador['entrada'] ?? ''),
                $this->sanitizeForCsv($prestador['saida_at'] ?? '')
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}