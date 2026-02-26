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
        // Detectar contexto de relatórios
        $isReportsContext = (strpos($_SERVER['REQUEST_URI'], '/reports/') !== false);
        
        if ($isReportsContext) {
            $this->handleReportsIndex();
        } else {
            $this->handleStandardIndex();
        }
    }
    
    private function handleStandardIndex() {
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "SELECT * FROM visitantes_novo WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (nome ILIKE ? OR cpf ILIKE ? OR doc_number ILIKE ? OR empresa ILIKE ?)";
            $params[] = "%$search%";
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
    
    private function handleReportsIndex() {
        // Configurar timezone
        date_default_timezone_set('America/Sao_Paulo');
        
        // Filtros avançados para relatórios
        $data_inicial = $_GET['data_inicial'] ?? '';
        $data_final = $_GET['data_final'] ?? '';
        $empresa = $_GET['empresa'] ?? '';
        $funcionario_responsavel = $_GET['funcionario_responsavel'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Paginação
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Query base com filtros otimizados usando nova estrutura Pré-Cadastros v2.0.0
        $baseQuery = "
            SELECT 
                r.id as id,
                c.nome as nome,
                c.doc_type as doc_type,
                c.doc_number as doc_number,
                c.doc_country as doc_country,
                c.empresa as empresa,
                r.funcionario_responsavel as funcionario_responsavel,
                r.setor as setor,
                c.placa_veiculo as placa_veiculo,
                r.entrada_at as hora_entrada,
                r.saida_at as hora_saida,
                r.observacao_entrada as observacao_entrada,
                r.observacao_saida as observacao_saida,
                CASE 
                    WHEN c.valid_until < CURRENT_DATE THEN 'expirado'
                    WHEN c.valid_until <= CURRENT_DATE + INTERVAL '30 days' THEN 'expirando'
                    ELSE 'valido'
                END as validity_status
            FROM visitantes_registros r
            JOIN visitantes_cadastro c ON c.id = r.cadastro_id
            WHERE r.entrada_at IS NOT NULL AND c.deleted_at IS NULL";
        
        $whereConditions = [];
        $params = [];
        
        // Filtro por período (data inicial e/ou final)
        if (!empty($data_inicial) && !empty($data_final)) {
            $whereConditions[] = "DATE(r.entrada_at) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
        } elseif (!empty($data_inicial)) {
            $whereConditions[] = "DATE(r.entrada_at) >= ?";
            $params[] = $data_inicial;
        } elseif (!empty($data_final)) {
            $whereConditions[] = "DATE(r.entrada_at) <= ?";
            $params[] = $data_final;
        }
        
        // Filtro por empresa
        if (!empty($empresa)) {
            $whereConditions[] = "c.empresa ILIKE ?";
            $params[] = "%$empresa%";
        }
        
        // Filtro por funcionário responsável
        if (!empty($funcionario_responsavel)) {
            $whereConditions[] = "r.funcionario_responsavel ILIKE ?";
            $params[] = "%$funcionario_responsavel%";
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $whereConditions[] = "r.setor = ?";
            $params[] = $setor;
        }
        
        // Busca geral
        if (!empty($search)) {
            $whereConditions[] = "(c.nome ILIKE ? OR c.doc_number ILIKE ? OR c.empresa ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Adicionar condições WHERE se houver filtros
        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(" AND ", $whereConditions);
        }
        
        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as subquery";
        $countResult = $this->db->fetch($countQuery, $params);
        $totalRecords = (int)$countResult['total'];
        
        // Query principal com paginação
        $query = $baseQuery . " ORDER BY r.entrada_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $visitantes = $this->db->fetchAll($query, $params);
        
        // DEBUG: Verificar se há duplicação no resultado da query
        error_log("🔍 DEBUG Relatório Visitantes - Total registros retornados: " . count($visitantes));
        foreach ($visitantes as $v) {
            if (strpos($v['nome'], 'TESTE') !== false) {
                error_log("🔍 DEBUG Visitante: ID={$v['id']}, Nome={$v['nome']}");
            }
        }
        
        // Processar dados para exibição
        $canViewFullCpf = $this->canViewFullCpf();
        foreach ($visitantes as &$visitante) {
            // Mascarar CPF padronizado (LGPD)
            if (!empty($visitante['cpf'])) {
                if (!$canViewFullCpf) {
                    $visitante['cpf_masked'] = $this->maskCpf($visitante['cpf']);
                } else {
                    $visitante['cpf_masked'] = $visitante['cpf'];
                }
            }
            
            // Mascarar documento (LGPD) - mostrar apenas últimos dígitos
            if (!empty($visitante['doc_number'])) {
                if (!$canViewFullCpf) {
                    $docNumber = $visitante['doc_number'];
                    $docType = $visitante['doc_type'] ?? '';
                    
                    // Remover formatação para contar caracteres reais
                    $cleanDoc = preg_replace('/[^A-Za-z0-9]/', '', $docNumber);
                    $length = strlen($cleanDoc);
                    
                    // Mostrar apenas últimos 4 caracteres, resto com asteriscos
                    if ($length > 4) {
                        $visiblePart = substr($cleanDoc, -4);
                        $maskedPart = str_repeat('*', min($length - 4, 8)); // Max 8 asteriscos
                        $visitante['doc_number_masked'] = $maskedPart . $visiblePart;
                    } else {
                        $visitante['doc_number_masked'] = $docNumber;
                    }
                } else {
                    $visitante['doc_number_masked'] = $visitante['doc_number'];
                }
            }
            
            // Lógica "A pé" para placas vazias
            if (empty($visitante['placa_veiculo']) || trim($visitante['placa_veiculo']) === '') {
                $visitante['placa_display'] = 'A pé';
            } else {
                $visitante['placa_display'] = $visitante['placa_veiculo'];
            }
            
            // Formatar datas para exibição
            if (!empty($visitante['hora_entrada'])) {
                $visitante['data_entrada'] = date('d/m/Y', strtotime($visitante['hora_entrada']));
                $visitante['hora_entrada_formatted'] = date('H:i', strtotime($visitante['hora_entrada']));
            }
            
            if (!empty($visitante['hora_saida'])) {
                $visitante['hora_saida_formatted'] = date('H:i', strtotime($visitante['hora_saida']));
            }
        }
        unset($visitante); // 🔧 FIX: Limpar referência para evitar bugs em foreach subsequentes
        
        // Metadados de paginação
        $totalPages = ceil($totalRecords / $perPage);
        $paginationData = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
        
        // Buscar dados para filtros usando nova estrutura Pré-Cadastros v2.0.0
        $setores = $this->db->fetchAll("
            SELECT DISTINCT r.setor 
            FROM visitantes_registros r 
            JOIN visitantes_cadastro c ON c.id = r.cadastro_id
            WHERE r.setor IS NOT NULL AND c.deleted_at IS NULL 
            ORDER BY r.setor
        ");
        $empresas = $this->db->fetchAll("
            SELECT DISTINCT c.empresa 
            FROM visitantes_cadastro c 
            WHERE c.empresa IS NOT NULL AND c.empresa != '' AND c.deleted_at IS NULL 
            ORDER BY c.empresa 
            LIMIT 50
        ");
        $responsaveis = $this->db->fetchAll("
            SELECT DISTINCT r.funcionario_responsavel 
            FROM visitantes_registros r
            JOIN visitantes_cadastro c ON c.id = r.cadastro_id 
            WHERE r.funcionario_responsavel IS NOT NULL AND r.funcionario_responsavel != '' AND c.deleted_at IS NULL 
            ORDER BY r.funcionario_responsavel 
            LIMIT 50
        ");
        
        // Incluir view de relatórios
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
                
                // Novos campos de documento
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? '');
                
                // ========== VALIDAR E NORMALIZAR DADOS ==========
                // Normalizar tipo de documento: vazio = CPF (padrão)
                if (empty($doc_type)) {
                    $doc_type = 'CPF';
                }
                
                // Normalizar número do documento conforme tipo
                if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                    // Documentos brasileiros: apenas dígitos
                    $doc_number = preg_replace('/\D/', '', $doc_number);
                } else {
                    // Documentos internacionais: alfanuméricos (letras e números)
                    $doc_number = preg_replace('/[^A-Z0-9]/i', '', strtoupper($doc_number));
                }
                
                // Validar CPF se tipo for CPF
                if ($doc_type === 'CPF' && !empty($doc_number)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $doc_number = $cpfValidation['normalized'];
                    $cpf = $doc_number; // Sincronizar campo legado
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
                if (empty($doc_number)) {
                    throw new Exception("Número do documento é obrigatório");
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
                
                // NOVA ESTRUTURA PRÉ-CADASTROS V2.0.0
                // 1. Verificar se já existe pré-cadastro com esse documento
                $cadastroExistente = $this->db->fetch("
                    SELECT id FROM visitantes_cadastro 
                    WHERE doc_type = ? AND doc_number = ? AND deleted_at IS NULL
                    LIMIT 1
                ", [$doc_type, $doc_number]);
                
                if ($cadastroExistente) {
                    // Reutilizar cadastro existente
                    $cadastro_id = $cadastroExistente['id'];
                } else {
                    // Criar novo pré-cadastro (válido por 1 ano)
                    $this->db->query("
                        INSERT INTO visitantes_cadastro (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, valid_from, valid_until, ativo)
                        VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', true)
                    ", [
                        $nome,
                        $empresa,
                        $doc_type,
                        $doc_number,
                        !empty($doc_country) ? $doc_country : null,
                        $placa_veiculo
                    ]);
                    
                    // Obter ID do cadastro criado
                    $cadastro_id = $this->db->lastInsertId('visitantes_cadastro_id_seq');
                }
                
                // 2. Criar registro de entrada/saída (sempre criar novo registro)
                $this->db->query("
                    INSERT INTO visitantes_registros (cadastro_id, funcionario_responsavel, setor, entrada_at, saida_at)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $cadastro_id,
                    $funcionario_responsavel,
                    $setor,
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
        
        // PRÉ-CADASTROS V2.0.0: Buscar da nova estrutura
        $visitante = $this->db->fetch("
            SELECT r.*, c.nome, c.doc_type, c.doc_number, c.doc_country, c.empresa, c.placa_veiculo,
                   r.cadastro_id, r.setor, r.funcionario_responsavel,
                   r.entrada_at as hora_entrada, r.saida_at as hora_saida
            FROM visitantes_registros r
            JOIN visitantes_cadastro c ON c.id = r.cadastro_id
            WHERE r.id = ? AND c.deleted_at IS NULL
        ", [$id]);
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
                
                // Novos campos de documento
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? '');
                
                // ========== VALIDAR E NORMALIZAR DADOS ==========
                // Normalizar tipo de documento: vazio = CPF (padrão)
                if (empty($doc_type)) {
                    $doc_type = 'CPF';
                }
                
                // Normalizar número do documento conforme tipo
                if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                    // Documentos brasileiros: apenas dígitos
                    $doc_number = preg_replace('/\D/', '', $doc_number);
                } else {
                    // Documentos internacionais: alfanuméricos (letras e números)
                    $doc_number = preg_replace('/[^A-Z0-9]/i', '', strtoupper($doc_number));
                }
                
                // Validar CPF se tipo for CPF
                if ($doc_type === 'CPF' && !empty($doc_number)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                    if (!$cpfValidation['isValid']) {
                        throw new Exception($cpfValidation['message']);
                    }
                    $doc_number = $cpfValidation['normalized'];
                    $cpf = $doc_number; // Sincronizar campo legado
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // ================================================
                
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
                
                // ========== VALIDAÇÕES TEMPORAIS (opcionais para edição) ==========
                // Normalizar hora de entrada apenas se fornecida
                if (!empty($hora_entrada)) {
                    $entradaValidation = DateTimeValidator::validateEntryDateTime($hora_entrada);
                    if (!$entradaValidation['isValid']) {
                        throw new Exception($entradaValidation['message']);
                    }
                    $hora_entrada = $entradaValidation['normalized'];
                }
                
                // Normalizar hora de saída apenas se fornecida
                if (!empty($hora_saida)) {
                    $saidaValidation = DateTimeValidator::validateExitDateTime($hora_saida, $hora_entrada);
                    if (!$saidaValidation['isValid']) {
                        throw new Exception($saidaValidation['message']);
                    }
                    $hora_saida = $saidaValidation['normalized'];
                } else {
                    $hora_saida = null;
                }
                // ==========================================
                
                // Validação de duplicidade removida para permitir edição livre de saídas
                
                // PRÉ-CADASTROS V2.0.0: Buscar cadastro_id
                $registro = $this->db->fetch("SELECT cadastro_id FROM visitantes_registros WHERE id = ?", [$id]);
                if (!$registro) {
                    throw new Exception("Registro não encontrado");
                }
                
                // Atualizar CADASTRO (dados mestres)
                $this->db->query("
                    UPDATE visitantes_cadastro 
                    SET nome = ?, doc_type = ?, doc_number = ?, doc_country = ?, empresa = ?, placa_veiculo = ?
                    WHERE id = ?
                ", [
                    $nome, $doc_type, $doc_number, !empty($doc_country) ? $doc_country : null, $empresa, $placa_veiculo, $registro['cadastro_id']
                ]);
                
                // Atualizar REGISTRO (dados do evento)
                $this->db->query("
                    UPDATE visitantes_registros 
                    SET setor = ?, funcionario_responsavel = ?, entrada_at = ?, saida_at = ?
                    WHERE id = ?
                ", [
                    $setor, $funcionario_responsavel, $hora_entrada ?: null, $hora_saida ?: null, $id
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
                
                // PRÉ-CADASTROS V2.0.0: Buscar da nova estrutura
                $visitante = $this->db->fetch("
                    SELECT r.*, c.nome, c.doc_type, c.doc_number, c.doc_country, c.empresa, c.placa_veiculo,
                           r.entrada_at as hora_entrada, r.saida_at as hora_saida
                    FROM visitantes_registros r
                    JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ?
                ", [$_POST['id']]);
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
                        $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, $id, 'visitantes_novo');
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
                    SET hora_saida = CURRENT_TIMESTAMP
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
                // Verificação CSRF manual para retorno JSON adequado
                if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'])) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                
                $nome = trim($_POST['nome'] ?? '');
                $cpf = trim($_POST['cpf'] ?? '');
                $empresa = trim($_POST['empresa'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $setor = trim($_POST['setor'] ?? '');
                $placa_veiculo = trim($_POST['placa_veiculo'] ?? '');
                $hora_entrada_input = trim($_POST['hora_entrada'] ?? '');
                
                // Novos campos de documento
                $doc_type = trim($_POST['doc_type'] ?? '');
                $doc_number = trim($_POST['doc_number'] ?? '');
                $doc_country = trim($_POST['doc_country'] ?? '');
                
                // ========== NORMALIZAR DADOS ==========
                // Normalizar tipo de documento: vazio = CPF (padrão)
                if (empty($doc_type)) {
                    $doc_type = 'CPF';
                }
                
                // Normalizar número do documento conforme tipo
                if (in_array($doc_type, ['CPF', 'RG', 'CNH'])) {
                    // Documentos brasileiros: apenas dígitos
                    $doc_number = preg_replace('/\D/', '', $doc_number);
                } else {
                    // Documentos internacionais: alfanuméricos (letras e números)
                    $doc_number = preg_replace('/[^A-Z0-9]/i', '', strtoupper($doc_number));
                }
                
                // Validar CPF se tipo for CPF
                if ($doc_type === 'CPF' && !empty($doc_number)) {
                    $cpfValidation = CpfValidator::validateAndNormalize($doc_number);
                    if (!$cpfValidation['isValid']) {
                        echo json_encode(['success' => false, 'message' => $cpfValidation['message']]);
                        return;
                    }
                    $doc_number = $cpfValidation['normalized'];
                    $cpf = $doc_number; // Sincronizar campo legado
                }
                
                // Placa: apenas letras e números, maiúscula
                $placa_veiculo = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa_veiculo)));
                // =====================================
                
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
                
                // NOVA ESTRUTURA PRÉ-CADASTROS V2.0.0
                // 1. PRIMEIRO: Verificar se já existe pré-cadastro com esse documento
                // Buscar usando REPLACE para remover máscaras (tanto no DB quanto no input)
                $cadastroExistente = $this->db->fetch("
                    SELECT id, placa_veiculo FROM visitantes_cadastro 
                    WHERE doc_type = ? 
                      AND REPLACE(REPLACE(REPLACE(doc_number, '.', ''), '-', ''), '/', '') = ? 
                      AND deleted_at IS NULL
                    LIMIT 1
                ", [$doc_type, $doc_number]);
                
                // 2. DEPOIS: Validar duplicidade (ignorando o próprio cadastro se reutilizando)
                $dadosValidacao = [
                    'cpf' => $cpf,
                    'placa_veiculo' => $placa_veiculo,
                    'hora_entrada' => $hora_entrada
                ];
                
                // Se está reutilizando cadastro, validar CPF ativo E placa (excluindo o próprio cadastro)
                if ($cadastroExistente) {
                    file_put_contents('php://stderr', "🔄 REUTILIZANDO PRÉ-CADASTRO ID: " . $cadastroExistente['id'] . "\n");
                    file_put_contents('php://stderr', "📋 Placa do cadastro: " . $cadastroExistente['placa_veiculo'] . "\n");
                    file_put_contents('php://stderr', "📋 Placa recebida: $placa_veiculo\n");
                    
                    // Validar CPF não tem entrada em aberto
                    $cpfValidation = $this->duplicityService->validateCpfNotOpen($cpf);
                    if (!$cpfValidation['isValid']) {
                        file_put_contents('php://stderr', "❌ CPF já tem entrada em aberto\n");
                        echo json_encode([
                            'success' => false,
                            'message' => $cpfValidation['message']
                        ]);
                        return;
                    }
                    
                    file_put_contents('php://stderr', "✅ CPF validado com sucesso\n");
                    file_put_contents('php://stderr', "🔍 Validando placa com excludeId={$cadastroExistente['id']}, excludeTable=visitantes_cadastro\n");
                    
                    // Validar placa única (excluindo o próprio cadastro sendo reutilizado)
                    $placaValidation = $this->duplicityService->validatePlacaUnique($placa_veiculo, $cadastroExistente['id'], 'visitantes_cadastro');
                    
                    file_put_contents('php://stderr', "📊 Resultado validação placa: " . json_encode($placaValidation) . "\n");
                    
                    if (!$placaValidation['isValid']) {
                        file_put_contents('php://stderr', "❌ Placa já em uso por outro cadastro: " . $placaValidation['message'] . "\n");
                        echo json_encode([
                            'success' => false,
                            'message' => $placaValidation['message']
                        ]);
                        return;
                    }
                    
                    file_put_contents('php://stderr', "✅ Validação OK - criando novo registro\n");
                    // Reutilizar cadastro existente
                    $cadastro_id = $cadastroExistente['id'];
                    
                    // Atualizar placa no cadastro se fornecida
                    if (!empty($placa_veiculo)) {
                        $this->db->query("
                            UPDATE visitantes_cadastro 
                            SET placa_veiculo = ?
                            WHERE id = ?
                        ", [$placa_veiculo, $cadastro_id]);
                    }
                } else {
                    // Criar novo cadastro: validar TUDO (CPF, placa, debounce)
                    $validacao = $this->duplicityService->validateNewEntry($dadosValidacao, 'visitante');
                    
                    if (!$validacao['isValid']) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro de duplicidade',
                            'errors' => $validacao['errors']
                        ]);
                        return;
                    }
                    
                    // Criar novo pré-cadastro (válido por 1 ano)
                    $this->db->query("
                        INSERT INTO visitantes_cadastro (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, valid_from, valid_until, ativo)
                        VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', true)
                    ", [
                        $nome,
                        $empresa,
                        $doc_type,
                        $doc_number,
                        !empty($doc_country) ? $doc_country : null,
                        $placa_veiculo
                    ]);
                    
                    // Obter ID do cadastro criado
                    $cadastro_id = $this->db->lastInsertId('visitantes_cadastro_id_seq');
                }
                
                // 2. Criar registro de entrada (sempre criar novo registro)
                $this->db->query("
                    INSERT INTO visitantes_registros (cadastro_id, funcionario_responsavel, setor, entrada_at)
                    VALUES (?, ?, ?, ?)
                ", [
                    $cadastro_id,
                    $funcionario_responsavel,
                    $setor,
                    $hora_entrada
                ]);
                
                // 4. Obter ID do registro criado
                $registro_id = $this->db->lastInsertId('visitantes_registros_id_seq');
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Visitante cadastrado com sucesso',
                    'data' => [
                        'id' => $registro_id,
                        'cadastro_id' => $cadastro_id,
                        'nome' => $nome,
                        'tipo' => 'Visitante',
                        'doc_number' => $doc_number,
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
                
                // EDIÇÃO DE REGISTRO (evento) - NÃO edita pré-cadastro
                // Apenas campos do registro: setor, funcionario_responsavel, saida_at
                $setor = trim($_POST['setor'] ?? '');
                $funcionario_responsavel = trim($_POST['funcionario_responsavel'] ?? '');
                $hora_saida = trim($_POST['hora_saida'] ?? '');
                
                if (empty($id)) {
                    echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                    return;
                }
                
                // Buscar dados atuais do visitante (PRÉ-CADASTROS V2.0.0)
                $visitanteAtual = $this->db->fetch("
                    SELECT r.*, c.nome, c.doc_type, c.doc_number, c.doc_country, c.empresa, c.placa_veiculo,
                           r.cadastro_id, r.setor, r.funcionario_responsavel,
                           r.entrada_at as hora_entrada, r.saida_at as hora_saida
                    FROM visitantes_registros r
                    JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ? AND c.deleted_at IS NULL
                ", [$id]);
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
                
                // ========== PRÉ-CADASTROS V2.0.0: EDIÇÃO DE REGISTRO ==========
                // Dashboard SOMENTE edita o REGISTRO (evento de entrada/saída)
                // Dados do pré-cadastro (nome, doc, empresa, placa) NÃO são alterados aqui
                // Apenas a página de Pré-Cadastros pode editar dados mestres
                
                // Atualizar apenas campos do REGISTRO (dados do evento específico)
                $this->db->query("
                    UPDATE visitantes_registros 
                    SET setor = ?, funcionario_responsavel = ?, saida_at = ?
                    WHERE id = ?
                ", [$setor, $funcionario_responsavel, $hora_saida_final, $id]);
                
                // Buscar dados atualizados para retornar
                $visitanteAtualizado = $this->db->fetch("
                    SELECT r.*, c.nome, c.doc_type, c.doc_number, c.doc_country, c.empresa, c.placa_veiculo,
                           r.entrada_at as hora_entrada, r.saida_at as hora_saida
                    FROM visitantes_registros r
                    JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ?
                ", [$id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Registro atualizado com sucesso',
                    'data' => [
                        'id' => $id,
                        'nome' => $visitanteAtualizado['nome'],
                        'tipo' => 'Visitante',
                        'doc_type' => $visitanteAtualizado['doc_type'] ?? null,
                        'doc_number' => $visitanteAtualizado['doc_number'] ?? null,
                        'doc_country' => $visitanteAtualizado['doc_country'] ?? null,
                        'cpf' => $visitanteAtualizado['doc_number'] ?? null, // Compatibilidade
                        'empresa' => $visitanteAtualizado['empresa'],
                        'setor' => $setor,
                        'funcionario_responsavel' => $funcionario_responsavel,
                        'placa_veiculo' => $visitanteAtualizado['placa_veiculo'],
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
                
                // Buscar o visitante (PRÉ-CADASTROS V2.0.0)
                $visitante = $this->db->fetch("
                    SELECT r.*, c.nome, c.doc_type, c.doc_number, c.doc_country, c.empresa, c.placa_veiculo, c.foto_url,
                           r.entrada_at as hora_entrada, r.saida_at as hora_saida
                    FROM visitantes_registros r
                    JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                    WHERE r.id = ? AND c.deleted_at IS NULL
                ", [$id]);
                
                if (!$visitante) {
                    echo json_encode(['success' => false, 'message' => 'Visitante não encontrado']);
                    return;
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'id' => $visitante['id'],
                        'nome' => $visitante['nome'],
                        'doc_type' => $visitante['doc_type'] ?? null,
                        'doc_number' => $visitante['doc_number'] ?? null,
                        'doc_country' => $visitante['doc_country'] ?? null,
                        'cpf' => $visitante['doc_number'] ?? null, // Compatibilidade
                        'empresa' => $visitante['empresa'],
                        'setor' => $visitante['setor'],
                        'funcionario_responsavel' => $visitante['funcionario_responsavel'],
                        'placa_veiculo' => $visitante['placa_veiculo'],
                        'hora_entrada' => $visitante['hora_entrada'],
                        'hora_saida' => $visitante['hora_saida'],
                        'foto_url' => $visitante['foto_url'] ?? null
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
        
        // Verificar CSRF token via query parameter para GET requests
        $csrfToken = $_GET['csrf_token'] ?? '';
        if (empty($csrfToken) || !CSRFProtection::validateToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
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
                FROM visitantes_registros r
                INNER JOIN visitantes_cadastro c ON r.cadastro_id = c.id
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
            $registro = $this->db->fetch("SELECT cadastro_id FROM visitantes_registros WHERE id = ?", [$id]);
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
                $updateCadastroParams[] = $cadastroId;
                
                $queryCadastro = "UPDATE visitantes_cadastro SET " . implode(', ', $updateCadastroFields) . " WHERE id = ?";
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
                $updateRegistroParams[] = $id;
                
                $queryRegistro = "UPDATE visitantes_registros SET " . implode(', ', $updateRegistroFields) . " WHERE id = ?";
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
                FROM visitantes_registros r
                INNER JOIN visitantes_cadastro c ON r.cadastro_id = c.id
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
                FROM visitantes_registros r
                INNER JOIN visitantes_cadastro c ON r.cadastro_id = c.id
                WHERE r.id = ?
            ", [$id]);
            
            if (!$registro) {
                throw new Exception('Registro não encontrado');
            }
            
            // Deletar apenas o registro de entrada/saída
            $this->db->query("DELETE FROM visitantes_registros WHERE id = ?", [$id]);
            
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
        $empresa = $_GET['empresa'] ?? '';
        $funcionario_responsavel = $_GET['funcionario_responsavel'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Query base com filtros
        $whereConditions = ["hora_entrada IS NOT NULL"];
        $params = [];
        
        // Filtro por período
        if (!empty($data_inicial) && !empty($data_final)) {
            $whereConditions[] = "DATE(hora_entrada) BETWEEN ? AND ?";
            $params[] = $data_inicial;
            $params[] = $data_final;
        } elseif (!empty($data_inicial)) {
            $whereConditions[] = "DATE(hora_entrada) >= ?";
            $params[] = $data_inicial;
        } elseif (!empty($data_final)) {
            $whereConditions[] = "DATE(hora_entrada) <= ?";
            $params[] = $data_final;
        }
        
        // Filtro por empresa
        if (!empty($empresa)) {
            $whereConditions[] = "empresa ILIKE ?";
            $params[] = "%$empresa%";
        }
        
        // Filtro por funcionário responsável
        if (!empty($funcionario_responsavel)) {
            $whereConditions[] = "funcionario_responsavel ILIKE ?";
            $params[] = "%$funcionario_responsavel%";
        }
        
        // Filtro por setor
        if (!empty($setor)) {
            $whereConditions[] = "setor = ?";
            $params[] = $setor;
        }
        
        // Busca geral
        if (!empty($search)) {
            $whereConditions[] = "(nome ILIKE ? OR cpf ILIKE ? OR doc_number ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        // Buscar TODOS os dados (sem paginação) para exportação
        $query = "
            SELECT id, nome, cpf, doc_type, doc_number, doc_country,
                   empresa, funcionario_responsavel, setor, 
                   placa_veiculo, hora_entrada, hora_saida
            FROM visitantes_novo 
            WHERE $whereClause 
            ORDER BY hora_entrada DESC
        ";
        
        $visitantes = $this->db->fetchAll($query, $params);
        
        // Configurar headers para download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_visitantes_' . date('Y-m-d_His') . '.csv"');
        
        // Abrir output stream
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (compatibilidade Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos do CSV
        fputcsv($output, [
            'ID',
            'Nome',
            'CPF',
            'Tipo Documento',
            'Número Documento',
            'País Documento',
            'Empresa',
            'Funcionário Responsável',
            'Setor',
            'Placa do Veículo',
            'Hora Entrada',
            'Hora Saída'
        ], ';');
        
        // Dados
        $canViewFullCpf = $this->canViewFullCpf();
        foreach ($visitantes as $visitante) {
            // Mascarar CPF se necessário
            $cpf_display = !empty($visitante['cpf']) ? 
                ($canViewFullCpf ? $visitante['cpf'] : $this->maskCpf($visitante['cpf'])) : '';
            
            // Mascarar doc_number se necessário (LGPD)
            $doc_number_display = !empty($visitante['doc_number']) ? 
                ($canViewFullCpf ? $visitante['doc_number'] : $this->maskCpf($visitante['doc_number'])) : '';
            
            // Lógica "A pé" para placas
            $placa_display = empty($visitante['placa_veiculo']) ? 'A pé' : $visitante['placa_veiculo'];
            
            fputcsv($output, [
                $this->sanitizeForCsv($visitante['id']),
                $this->sanitizeForCsv($visitante['nome']),
                $this->sanitizeForCsv($cpf_display),
                $this->sanitizeForCsv($visitante['doc_type'] ?? ''),
                $this->sanitizeForCsv($doc_number_display),
                $this->sanitizeForCsv($visitante['doc_country'] ?? ''),
                $this->sanitizeForCsv($visitante['empresa'] ?? ''),
                $this->sanitizeForCsv($visitante['funcionario_responsavel'] ?? ''),
                $this->sanitizeForCsv($visitante['setor'] ?? ''),
                $this->sanitizeForCsv($placa_display),
                $this->sanitizeForCsv($visitante['hora_entrada'] ?? ''),
                $this->sanitizeForCsv($visitante['hora_saida'] ?? '')
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Upload de foto do visitante (pré-cadastro)
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
            $fileName = 'visitante_' . $cadastroId . '_' . time() . '.' . $extension;
            
            // Diretório de destino
            $uploadDir = __DIR__ . '/../../public/uploads/visitantes/';
            
            // Criar diretório se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $targetPath = $uploadDir . $fileName;
            
            // Remover foto anterior se existir
            $oldPhoto = $this->db->fetch("
                SELECT foto_url FROM visitantes_cadastro WHERE id = ?
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
            $fotoUrl = 'visitantes/' . $fileName;
            $this->db->query("
                UPDATE visitantes_cadastro 
                SET foto_url = ?
                WHERE id = ?
            ", [$fotoUrl, $cadastroId]);
            
            // Buscar nome para auditoria
            $cadastro = $this->db->fetch("
                SELECT nome FROM visitantes_cadastro WHERE id = ?
            ", [$cadastroId]);
            
            // Auditoria
            AuditService::log(
                'visitantes',
                'update',
                $cadastroId,
                ['foto_url' => $fotoUrl],
                "Atualizou foto do visitante {$cadastro['nome']}"
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