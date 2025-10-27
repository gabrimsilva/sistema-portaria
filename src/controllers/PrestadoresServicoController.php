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
        
        // Query base para relatórios (PRÉ-CADASTROS V2.0.0)
        $query = "
            SELECT 
                r.id,
                c.nome,
                r.setor,
                c.placa_veiculo,
                CASE 
                    WHEN c.placa_veiculo IS NULL OR c.placa_veiculo = '' OR c.placa_veiculo = 'APE' THEN 'A pé'
                    ELSE UPPER(c.placa_veiculo)
                END as placa_ou_ape,
                c.empresa,
                r.funcionario_responsavel,
                c.doc_type,
                c.doc_number,
                c.doc_country,
                c.doc_number as cpf,
                r.entrada_at,
                r.saida_at as saida,
                CASE 
                    WHEN c.valid_until < CURRENT_DATE THEN 'expirado'
                    WHEN c.valid_until <= CURRENT_DATE + INTERVAL '30 days' THEN 'expirando'
                    ELSE 'valido'
                END as validity_status
            FROM prestadores_registros r
            JOIN prestadores_cadastro c ON c.id = r.cadastro_id
            WHERE r.entrada_at IS NOT NULL AND c.deleted_at IS NULL";
        
        $countQuery = "SELECT COUNT(*) as total FROM prestadores_registros r JOIN prestadores_cadastro c ON c.id = r.cadastro_id WHERE r.entrada_at IS NOT NULL AND c.deleted_at IS NULL";
        $params = [];
        $countParams = [];
        
        // Filtro por período (data inicial e/ou final)
        if (!empty($data_inicial) && !empty($data_final)) {
            // Ambas as datas: filtrar entre elas
            $query .= " AND DATE(r.entrada_at) BETWEEN ? AND ?";
            $countQuery .= " AND DATE(r.entrada_at) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
            $countParams[] = $data_inicial;
            $countParams[] = $data_final;
        } elseif (!empty($data_inicial)) {
            // Apenas data inicial: a partir dela
            $query .= " AND DATE(r.entrada_at) >= ?";
            $countQuery .= " AND DATE(r.entrada_at) >= ?";
            $params[] = $data_inicial;
            $countParams[] = $data_inicial;
        } elseif (!empty($data_final)) {
            // Apenas data final: até ela
            $query .= " AND DATE(r.entrada_at) <= ?";
            $countQuery .= " AND DATE(r.entrada_at) <= ?";
            $params[] = $data_final;
            $countParams[] = $data_final;
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $query .= " AND r.setor = ?";
            $countQuery .= " AND r.setor = ?";
            $params[] = $setor;
            $countParams[] = $setor;
        }
        
        // Filtro por status (usando saida_at)
        if ($status === 'aberto') {
            $query .= " AND r.saida_at IS NULL";
            $countQuery .= " AND r.saida_at IS NULL";
        } elseif ($status === 'finalizado') {
            $query .= " AND r.saida_at IS NOT NULL";
            $countQuery .= " AND r.saida_at IS NOT NULL";
        }
        
        // Filtro por empresa
        if (!empty($empresa)) {
            $query .= " AND c.empresa ILIKE ?";
            $countQuery .= " AND c.empresa ILIKE ?";
            $params[] = "%$empresa%";
            $countParams[] = "%$empresa%";
        }
        
        // Filtro por responsável
        if (!empty($responsavel)) {
            $query .= " AND r.funcionario_responsavel ILIKE ?";
            $countQuery .= " AND r.funcionario_responsavel ILIKE ?";
            $params[] = "%$responsavel%";
            $countParams[] = "%$responsavel%";
        }
        
        // Ordenação e paginação
        $query .= " ORDER BY r.entrada_at DESC LIMIT ? OFFSET ?";
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
        
        // Dados para filtros (PRÉ-CADASTROS V2.0.0)
        $setores = $this->db->fetchAll("SELECT DISTINCT r.setor FROM prestadores_registros r JOIN prestadores_cadastro c ON c.id = r.cadastro_id WHERE r.setor IS NOT NULL AND c.deleted_at IS NULL ORDER BY r.setor");
        $empresas = $this->db->fetchAll("SELECT DISTINCT c.empresa FROM prestadores_registros r JOIN prestadores_cadastro c ON c.id = r.cadastro_id WHERE c.empresa IS NOT NULL AND c.deleted_at IS NULL ORDER BY c.empresa");
        $responsaveis = $this->db->fetchAll("SELECT DISTINCT r.funcionario_responsavel FROM prestadores_registros r JOIN prestadores_cadastro c ON c.id = r.cadastro_id WHERE r.funcionario_responsavel IS NOT NULL AND c.deleted_at IS NULL ORDER BY r.funcionario_responsavel");
        
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
                
                // ========== NORMALIZAR TIPO DE DOCUMENTO (NULL se vazio) ==========
                if (empty($doc_type)) {
                    $doc_type = null;
                    $doc_number = null;
                } else {
                    $doc_type = strtoupper(trim($doc_type));
                }
                // =================================================================
                
                // ========== VALIDAR E NORMALIZAR DOCUMENTOS ==========
                // Normalizar documento baseado no tipo
                if ($doc_type !== null && !empty($doc_number)) {
                    if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                        // Documentos brasileiros: somente dígitos
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        // Documentos internacionais: alfanuméricos
                        $doc_number = strtoupper(trim($doc_number));
                    }
                }
                
                // Sincronizar CPF quando doc_type for CPF
                if ($doc_type === 'CPF' && !empty($doc_number)) {
                    $cpf = $doc_number;
                } elseif ($doc_type === null && !empty($cpf)) {
                    // Modo legado: normalizar CPF
                    $cpf = preg_replace('/\D/', '', $cpf);
                }
                
                // Validar CPF conforme o tipo
                if ($doc_type === null) {
                    // Modo legado: validar CPF
                    if (!empty($cpf)) {
                        $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                        if (!$cpfValidation['isValid']) {
                            throw new Exception($cpfValidation['message']);
                        }
                        $cpf = $cpfValidation['normalized'];
                    } else {
                        throw new Exception("CPF é obrigatório");
                    }
                } elseif ($doc_type === 'CPF') {
                    // CPF explícito: validar doc_number
                    if (!empty($doc_number)) {
                        $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                        if (!$cpfValidation['isValid']) {
                            throw new Exception($cpfValidation['message']);
                        }
                        $cpf = $cpfValidation['normalized'];
                        $doc_number = $cpfValidation['normalized'];
                    } else {
                        throw new Exception("Número do documento é obrigatório");
                    }
                } else {
                    // Outros documentos: validar presença
                    if (empty($doc_number)) {
                        throw new Exception("Número do documento é obrigatório");
                    }
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
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Validar país para documentos internacionais
                if ($doc_type !== null && in_array($doc_type, ['PASSAPORTE', 'RNE', 'DNI', 'CI', 'OUTROS']) && empty($doc_country)) {
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
                // Apenas validar duplicidade de CPF se o tipo de documento for CPF ou modo legado
                $dadosValidacao = [
                    'cpf' => ($doc_type === null || $doc_type === 'CPF') ? $cpf : null,
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
                
                // ========== NORMALIZAR TIPO DE DOCUMENTO (NULL se vazio) ==========
                if (empty($doc_type)) {
                    $doc_type = null;
                    $doc_number = null;
                } else {
                    $doc_type = strtoupper(trim($doc_type));
                }
                // =================================================================
                
                // ========== VALIDAR E NORMALIZAR DOCUMENTOS ==========
                // Normalizar documento baseado no tipo
                if ($doc_type !== null && !empty($doc_number)) {
                    if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                        // Documentos brasileiros: somente dígitos
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        // Documentos internacionais: alfanuméricos
                        $doc_number = strtoupper(trim($doc_number));
                    }
                }
                
                // Sincronizar CPF quando doc_type for CPF
                if ($doc_type === 'CPF' && !empty($doc_number)) {
                    $cpf = $doc_number;
                } elseif ($doc_type === null && !empty($cpf)) {
                    // Modo legado: normalizar CPF
                    $cpf = preg_replace('/\D/', '', $cpf);
                }
                
                // Validar CPF conforme o tipo
                if ($doc_type === null) {
                    // Modo legado: validar CPF
                    if (!empty($cpf)) {
                        $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                        if (!$cpfValidation['isValid']) {
                            throw new Exception($cpfValidation['message']);
                        }
                        $cpf = $cpfValidation['normalized'];
                    } else {
                        throw new Exception("CPF é obrigatório");
                    }
                } elseif ($doc_type === 'CPF') {
                    // CPF explícito: validar doc_number
                    if (!empty($doc_number)) {
                        $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                        if (!$cpfValidation['isValid']) {
                            throw new Exception($cpfValidation['message']);
                        }
                        $cpf = $cpfValidation['normalized'];
                        $doc_number = $cpfValidation['normalized'];
                    } else {
                        throw new Exception("Número do documento é obrigatório");
                    }
                } else {
                    // Outros documentos: validar presença
                    if (empty($doc_number)) {
                        throw new Exception("Número do documento é obrigatório");
                    }
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ====================================================
                
                // Validações obrigatórias (simplificadas para edição)
                if (empty($nome)) {
                    throw new Exception("Nome é obrigatório");
                }
                if (empty($placa_veiculo)) {
                    throw new Exception("Placa de veículo é obrigatória");
                }
                
                // Validar país para documentos internacionais
                if ($doc_type !== null && in_array($doc_type, ['PASSAPORTE', 'RNE', 'DNI', 'CI', 'OUTROS']) && empty($doc_country)) {
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
                
                // ========== PRÉ-CADASTROS V2.0.0 INTEGRATION ==========
                $cadastro_id = !empty($_POST['cadastro_id']) ? intval($_POST['cadastro_id']) : null;
                
                $nome = trim($_POST['nome'] ?? '');
                $empresa = trim($_POST['empresa'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $observacao_entrada = trim($_POST['observacao'] ?? '');
                $entrada_input = trim($_POST['entrada'] ?? '');
                
                // Processar entrada
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
                
                // Validações obrigatórias
                if (empty($nome)) {
                    echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
                    return;
                }
                if (empty($setor)) {
                    echo json_encode(['success' => false, 'message' => 'Setor é obrigatório']);
                    return;
                }
                if (empty($funcionario_responsavel)) {
                    echo json_encode(['success' => false, 'message' => 'Funcionário responsável é obrigatório']);
                    return;
                }
                
                // MODO 1: Pré-Cadastro Existente (entrada rápida)
                if ($cadastro_id) {
                    $cadastro = $this->db->fetch(
                        "SELECT * FROM prestadores_cadastro 
                         WHERE id = ? AND deleted_at IS NULL AND ativo = true",
                        [$cadastro_id]
                    );
                    
                    if (!$cadastro) {
                        echo json_encode(['success' => false, 'message' => 'Pré-cadastro não encontrado ou inativo']);
                        return;
                    }
                    
                    // Verificar validade
                    if (strtotime($cadastro['valid_until']) < time()) {
                        echo json_encode(['success' => false, 'message' => 'Pré-cadastro expirado. Por favor, renove o cadastro.']);
                        return;
                    }
                    
                    // Criar registro de entrada
                    $this->db->query("
                        INSERT INTO prestadores_registros 
                        (cadastro_id, funcionario_responsavel, setor, entrada_at, observacao_entrada, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ", [
                        $cadastro_id,
                        $funcionario_responsavel,
                        $setor,
                        $entrada,
                        $observacao_entrada
                    ]);
                    
                    $registro_id = $this->db->lastInsertId();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Entrada registrada com sucesso (Pré-Cadastro)',
                        'data' => [
                            'id' => $registro_id,
                            'cadastro_id' => $cadastro_id,
                            'nome' => $cadastro['nome'],
                            'empresa' => $cadastro['empresa'],
                            'tipo' => 'Prestador',
                            'setor' => $setor,
                            'funcionario_responsavel' => $funcionario_responsavel,
                            'hora_entrada' => $entrada
                        ]
                    ]);
                    return;
                }
                
                // MODO 2: Novo Cadastro (criar pré-cadastro automaticamente + registro)
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? 'Brasil');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                
                // Normalização de documento
                if (!empty($doc_type)) {
                    if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        $doc_number = strtoupper(trim($doc_number));
                    }
                }
                
                // Validações
                if (empty($doc_number)) {
                    echo json_encode(['success' => false, 'message' => 'Número do documento é obrigatório']);
                    return;
                }
                
                // Verificar duplicidade no pré-cadastro
                $existingCadastro = $this->db->fetch(
                    "SELECT id FROM prestadores_cadastro 
                     WHERE doc_type = ? AND doc_number = ? 
                       AND deleted_at IS NULL AND ativo = true",
                    [$doc_type, $doc_number]
                );
                
                if ($existingCadastro) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Já existe um pré-cadastro ativo para este documento. Use o autocomplete para encontrá-lo.'
                    ]);
                    return;
                }
                
                // Criar novo pré-cadastro (válido por 1 ano)
                $valid_from = date('Y-m-d');
                $valid_until = date('Y-m-d', strtotime('+1 year'));
                
                $this->db->query("
                    INSERT INTO prestadores_cadastro
                    (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, valid_from, valid_until, ativo, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, true, NOW(), NOW())
                ", [
                    $nome,
                    $empresa,
                    $doc_type,
                    $doc_number,
                    $doc_country,
                    $placa_veiculo,
                    $valid_from,
                    $valid_until
                ]);
                
                $novo_cadastro_id = $this->db->lastInsertId();
                
                // Criar registro de entrada
                $this->db->query("
                    INSERT INTO prestadores_registros
                    (cadastro_id, funcionario_responsavel, setor, entrada_at, observacao_entrada, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ", [
                    $novo_cadastro_id,
                    $funcionario_responsavel,
                    $setor,
                    $entrada,
                    $observacao_entrada
                ]);
                
                $registro_id = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Pré-Cadastro criado e entrada registrada com sucesso!',
                    'data' => [
                        'id' => $registro_id,
                        'cadastro_id' => $novo_cadastro_id,
                        'nome' => $nome,
                        'empresa' => $empresa,
                        'tipo' => 'Prestador',
                        'setor' => $setor,
                        'funcionario_responsavel' => $funcionario_responsavel,
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
                // Lógica: se doc_type está vazio/null, usar modo legado (apenas CPF)
                // Constraint: doc_type e doc_number devem ser AMBOS NULL ou AMBOS preenchidos
                
                if (!empty($doc_type)) {
                    // Tipo de documento especificado (RG, CNH, Passaporte, etc)
                    
                    // Normalizar documento baseado no tipo
                    if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                        $doc_number = preg_replace('/\D/', '', $doc_number);
                    } else {
                        $doc_number = strtoupper(trim($doc_number));
                    }
                    
                    // Se for CPF, sincronizar com campo legado
                    if ($doc_type === 'CPF') {
                        $cpf = $doc_number;
                    }
                } else {
                    // Modo legado: CPF padrão
                    // Constraint exige: se doc_type é NULL, doc_number também deve ser NULL
                    $cpf = preg_replace('/\D/', '', $doc_number ?: $cpf);
                    $doc_type = null;
                    $doc_number = null; // NULL para respeitar constraint
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
                // Validar documento: se doc_type é NULL (modo legado), validar CPF; senão validar doc_number
                if ($doc_type === null && empty($cpf)) {
                    echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
                    return;
                } elseif ($doc_type !== null && empty($doc_number)) {
                    echo json_encode(['success' => false, 'message' => 'Número do documento é obrigatório']);
                    return;
                }
                if (empty($placa_veiculo)) {
                    echo json_encode(['success' => false, 'message' => 'Placa de veículo é obrigatória']);
                    return;
                }
                
                // Validar CPF se o tipo de documento for CPF ou modo legado (NULL)
                if ($doc_type === null) {
                    // Modo legado: validar CPF
                    $cpfValidation = CpfValidator::validateAndNormalize($cpf);
                    if (!$cpfValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $cpfValidation['message']]);
                        return;
                    }
                    $cpf = $cpfValidation['normalized'];
                } elseif ($doc_type === 'CPF') {
                    // CPF explícito: validar doc_number
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
                
                // Buscar registro atual (PRÉ-CADASTROS V2.0.0)
                $registro = $this->db->fetch("
                    SELECT r.*, c.* 
                    FROM prestadores_registros r
                    JOIN prestadores_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ? AND c.deleted_at IS NULL
                ", [$id]);
                
                if (!$registro) {
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
                        echo json_encode(['success' => false, 'message' => 'Formato de data/hora de saída inválido: ' . $saida]);
                        return;
                    }
                }
                
                // Atualizar registro de saída
                $this->db->query("
                    UPDATE prestadores_registros 
                    SET setor = ?, funcionario_responsavel = ?, saida_at = ?, observacao_saida = ?, updated_at = NOW()
                    WHERE id = ?
                ", [$setor, $funcionario_responsavel, $saida_parsed, $observacao, $id]);
                
                // Buscar dados atualizados para retornar
                $registroAtualizado = $this->db->fetch("
                    SELECT r.*, c.nome, c.empresa, c.doc_number as cpf, c.placa_veiculo
                    FROM prestadores_registros r
                    JOIN prestadores_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Prestador atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $registroAtualizado['nome'],
                        'tipo' => 'Prestador',
                        'cpf' => $registroAtualizado['cpf'] ?? '',
                        'empresa' => $registroAtualizado['empresa'],
                        'setor' => $registroAtualizado['setor'],
                        'funcionario_responsavel' => $registroAtualizado['funcionario_responsavel'],
                        'placa_veiculo' => $registroAtualizado['placa_veiculo'],
                        'hora_entrada' => $registroAtualizado['entrada_at'] ?? null,
                        'saida' => $registroAtualizado['saida_at'] ?? null
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
                
                // Buscar o registro (PRÉ-CADASTROS V2.0.0)
                $registro = $this->db->fetch("
                    SELECT r.id, r.cadastro_id, r.setor, r.funcionario_responsavel,
                           r.entrada_at, r.saida_at, r.observacao_entrada, r.observacao_saida,
                           c.nome, c.doc_type, c.doc_number, c.doc_country, 
                           c.empresa, c.placa_veiculo
                    FROM prestadores_registros r
                    JOIN prestadores_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ? AND c.deleted_at IS NULL
                ", [$id]);
                
                if (!$registro) {
                    echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
                    return;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $registro['id'],
                        'cadastro_id' => $registro['cadastro_id'],
                        'nome' => $registro['nome'],
                        'cpf' => $registro['doc_number'] ?? '',
                        'doc_type' => $registro['doc_type'],
                        'doc_number' => $registro['doc_number'],
                        'doc_country' => $registro['doc_country'],
                        'empresa' => $registro['empresa'],
                        'setor' => $registro['setor'],
                        'funcionario_responsavel' => $registro['funcionario_responsavel'],
                        'observacao' => $registro['observacao_entrada'] ?? '',
                        'placa_veiculo' => $registro['placa_veiculo'],
                        'entrada' => $registro['entrada_at'],
                        'saida' => $registro['saida_at']
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
                
                // Buscar o registro (PRÉ-CADASTROS V2.0.0)
                $registro = $this->db->fetch("
                    SELECT r.*, c.nome 
                    FROM prestadores_registros r
                    JOIN prestadores_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ? AND c.deleted_at IS NULL
                ", [$id]);
                
                if (!$registro) {
                    echo json_encode(['success' => false, 'message' => 'Registro não encontrado']);
                    return;
                }
                
                // Atualizar saída no registro
                $this->db->query("
                    UPDATE prestadores_registros 
                    SET saida_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Saída registrada com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $registro['nome']
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
     * Buscar registro por ID (usado para edição inline)
     * Requer permissão: relatorios.editar_linha
     */
    public function getByIdAjax() {
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
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para visualizar este registro']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID é obrigatório');
            }
            
            // Buscar registro com dados do cadastro
            $registro = $this->db->fetch("
                SELECT 
                    r.id,
                    r.cadastro_id,
                    r.entrada_at,
                    r.saida_at,
                    r.observacao_entrada,
                    r.observacao_saida,
                    r.setor,
                    r.funcionario_responsavel,
                    c.nome,
                    c.doc_type,
                    c.doc_number,
                    c.doc_country,
                    c.empresa,
                    c.placa_veiculo
                FROM prestadores_registros r
                INNER JOIN prestadores_cadastro c ON r.cadastro_id = c.id
                WHERE r.id = ?
            ", [$id]);
            
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $registro
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
            
            // Buscar registro com cadastro_id
            $registro = $this->db->fetch("SELECT cadastro_id FROM prestadores_registros WHERE id = ?", [$id]);
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            $cadastroId = $registro['cadastro_id'];
            
            // Atualizar cadastro se houver campos relacionados
            $updateCadastroFields = [];
            $updateCadastroParams = [];
            
            if (isset($_POST['nome'])) {
                $updateCadastroFields[] = 'nome = ?';
                $updateCadastroParams[] = trim($_POST['nome']);
            }
            
            if (isset($_POST['doc_type'])) {
                $updateCadastroFields[] = 'doc_type = ?';
                $updateCadastroParams[] = $_POST['doc_type'] ?: null;
            }
            
            if (isset($_POST['doc_number'])) {
                $docNumber = trim($_POST['doc_number']);
                $docType = $_POST['doc_type'] ?? null;
                
                // Normalização baseada no tipo
                if (in_array($docType, ['CPF', 'RG', 'CNH'])) {
                    $docNumber = preg_replace('/\D/', '', $docNumber);
                } else {
                    $docNumber = strtoupper($docNumber);
                }
                
                $updateCadastroFields[] = 'doc_number = ?';
                $updateCadastroParams[] = $docNumber ?: null;
            }
            
            if (isset($_POST['doc_country'])) {
                $updateCadastroFields[] = 'doc_country = ?';
                $updateCadastroParams[] = trim($_POST['doc_country']) ?: null;
            }
            
            if (isset($_POST['empresa'])) {
                $updateCadastroFields[] = 'empresa = ?';
                $updateCadastroParams[] = trim($_POST['empresa']);
            }
            
            if (isset($_POST['placa_veiculo'])) {
                $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa_veiculo']));
                $updateCadastroFields[] = 'placa_veiculo = ?';
                $updateCadastroParams[] = $placa ?: null;
            }
            
            // Executar update do cadastro se houver campos
            if (!empty($updateCadastroFields)) {
                $updateCadastroFields[] = 'updated_at = CURRENT_TIMESTAMP';
                $updateCadastroParams[] = $cadastroId;
                
                $queryCadastro = "UPDATE prestadores_cadastro SET " . implode(', ', $updateCadastroFields) . " WHERE id = ?";
                $this->db->query($queryCadastro, $updateCadastroParams);
            }
            
            // Atualizar registro se houver campos relacionados
            $updateRegistroFields = [];
            $updateRegistroParams = [];
            
            if (isset($_POST['setor'])) {
                $updateRegistroFields[] = 'setor = ?';
                $updateRegistroParams[] = trim($_POST['setor']) ?: null;
            }
            
            if (isset($_POST['funcionario_responsavel'])) {
                $updateRegistroFields[] = 'funcionario_responsavel = ?';
                $updateRegistroParams[] = trim($_POST['funcionario_responsavel']) ?: null;
            }
            
            if (isset($_POST['entrada_at'])) {
                $updateRegistroFields[] = 'entrada_at = ?';
                $updateRegistroParams[] = $_POST['entrada_at'] ?: null;
            }
            
            if (isset($_POST['saida_at'])) {
                $updateRegistroFields[] = 'saida_at = ?';
                $updateRegistroParams[] = $_POST['saida_at'] ?: null;
            }
            
            if (isset($_POST['observacao_entrada'])) {
                $updateRegistroFields[] = 'observacao_entrada = ?';
                $updateRegistroParams[] = trim($_POST['observacao_entrada']) ?: null;
            }
            
            if (isset($_POST['observacao_saida'])) {
                $updateRegistroFields[] = 'observacao_saida = ?';
                $updateRegistroParams[] = trim($_POST['observacao_saida']) ?: null;
            }
            
            // Executar update do registro se houver campos
            if (!empty($updateRegistroFields)) {
                $updateRegistroFields[] = 'updated_at = CURRENT_TIMESTAMP';
                $updateRegistroParams[] = $id;
                
                $queryRegistro = "UPDATE prestadores_registros SET " . implode(', ', $updateRegistroFields) . " WHERE id = ?";
                $this->db->query($queryRegistro, $updateRegistroParams);
            }
            
            if (empty($updateCadastroFields) && empty($updateRegistroFields)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            // Buscar dados atualizados
            $dadosAtualizados = $this->db->fetch("
                SELECT 
                    r.id,
                    r.cadastro_id,
                    r.entrada_at,
                    r.saida_at,
                    r.observacao_entrada,
                    r.observacao_saida,
                    r.setor,
                    r.funcionario_responsavel,
                    c.nome,
                    c.doc_type,
                    c.doc_number,
                    c.doc_country,
                    c.empresa,
                    c.placa_veiculo
                FROM prestadores_registros r
                INNER JOIN prestadores_cadastro c ON r.cadastro_id = c.id
                WHERE r.id = ?
            ", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro atualizado com sucesso',
                'data' => $dadosAtualizados
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
            
            // Buscar dados do registro para retornar
            $registro = $this->db->fetch("
                SELECT r.id, c.nome 
                FROM prestadores_registros r
                INNER JOIN prestadores_cadastro c ON r.cadastro_id = c.id
                WHERE r.id = ?
            ", [$id]);
            
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            // Deletar apenas o registro de entrada/saída
            $this->db->query("DELETE FROM prestadores_registros WHERE id = ?", [$id]);
            
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
    
    /**
     * Upload de foto do prestador (pré-cadastro)
     */
    public function uploadFoto() {
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            return;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $cadastroId = (int)($_POST['cadastro_id'] ?? 0);
            
            if ($cadastroId <= 0) {
                throw new Exception("ID do cadastro inválido");
            }
            
            // Verificar se o arquivo foi enviado
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Nenhuma foto foi enviada");
            }
            
            $file = $_FILES['photo'];
            
            // Validar tamanho (2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception("A foto deve ter no máximo 2MB");
            }
            
            // Validar tipo MIME
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou WEBP");
            }
            
            // Gerar nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'prestador_' . $cadastroId . '_' . time() . '.' . $extension;
            
            // Diretório de destino
            $uploadDir = __DIR__ . '/../../public/uploads/prestadores/';
            
            // Criar diretório se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $targetPath = $uploadDir . $fileName;
            
            // Remover foto anterior se existir
            $oldPhoto = $this->db->fetch("
                SELECT foto_url FROM prestadores_cadastro WHERE id = ?
            ", [$cadastroId]);
            
            if ($oldPhoto && !empty($oldPhoto['foto_url'])) {
                $oldPath = __DIR__ . '/../../public/uploads/' . $oldPhoto['foto_url'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Erro ao salvar a foto");
            }
            
            // Atualizar banco de dados
            $fotoUrl = 'prestadores/' . $fileName;
            $this->db->query("
                UPDATE prestadores_cadastro 
                SET foto_url = ?, updated_at = NOW()
                WHERE id = ?
            ", [$fotoUrl, $cadastroId]);
            
            // Buscar nome para auditoria
            $cadastro = $this->db->fetch("
                SELECT nome FROM prestadores_cadastro WHERE id = ?
            ", [$cadastroId]);
            
            // Auditoria
            AuditService::log(
                'prestadores',
                'update',
                $cadastroId,
                ['foto_url' => $fotoUrl],
                "Atualizou foto do prestador {$cadastro['nome']}"
            );
            
            echo json_encode([
                'success' => true,
                'foto_url' => $fotoUrl,
                'message' => 'Foto atualizada com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao fazer upload de foto: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}