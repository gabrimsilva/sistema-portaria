<?php

require_once '../src/services/AuditService.php';
require_once '../src/services/AuthorizationService.php';
require_once '../src/services/DuplicityValidationService.php';
require_once '../src/utils/CpfValidator.php';
require_once '../src/utils/DateTimeValidator.php';

class RegistroAcessoController {
    private $db;
    private $auditService;
    private $authService;
    private $duplicityService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
        $this->authService = new AuthorizationService();
        $this->duplicityService = new DuplicityValidationService();
    }
    
    /**
     * POST /entradas - Check-in
     */
    public function checkIn() {
        header('Content-Type: application/json');
        
        // Verificar permissão
        $this->authService->requirePermission('registro_acesso.create');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        CSRFProtection::verifyRequest();
        
        try {
            $data = $this->validateCheckInData($_POST);
            
            // Validar duplicidade
            $this->validateDuplicity($data);
            
            // Inserir registro
            $this->db->query(
                "INSERT INTO registro_acesso (tipo, nome, cpf, empresa, setor, placa_veiculo, funcionario_responsavel, observacao, entrada_at, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['tipo'],
                    $data['nome'],
                    $data['cpf'],
                    $data['empresa'],
                    $data['setor'],
                    $data['placa_veiculo'],
                    $data['funcionario_responsavel'],
                    $data['observacao'],
                    $data['entrada_at'],
                    $_SESSION['user_id']
                ]
            );
            
            // Obter ID do registro inserido
            $id = $this->db->fetch("SELECT currval('registro_acesso_id_seq') as id")['id'];
            
            $registro = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
            
            // Registrar auditoria
            $this->auditService->log('CREATE', 'registro_acesso', $id, null, $registro);
            
            echo json_encode([
                'success' => true,
                'message' => 'Check-in realizado com sucesso',
                'data' => $registro
            ]);
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Tratar erros específicos do banco de dados
            if (strpos($errorMessage, 'ux_registro_cpf_ativo') !== false) {
                $message = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'ux_registro_placa_ativa') !== false) {
                $message = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'chk_registro_horario_valido') !== false) {
                $message = "A hora de saída não pode ser anterior à hora de entrada.";
            } else {
                $message = $errorMessage;
            }
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
        }
    }
    
    /**
     * POST /saidas/:id - Check-out
     */
    public function checkOut($id) {
        header('Content-Type: application/json');
        
        // Verificar permissão
        $this->authService->requirePermission('registro_acesso.update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        CSRFProtection::verifyRequest();
        
        try {
            // Buscar registro atual
            $registro = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
            
            if (!$registro) {
                throw new Exception("Registro não encontrado");
            }
            
            if ($registro['saida_at']) {
                throw new Exception("Check-out já foi realizado para este registro");
            }
            
            $saida_at = $_POST['saida_at'] ?? date('Y-m-d H:i:s');
            
            // Validar horário de saída com DateTimeValidator
            $saidaValidation = DateTimeValidator::validateExitDateTime($saida_at, $registro['entrada_at']);
            if (!$saidaValidation['isValid']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $saidaValidation['message']
                ]);
                return;
            }
            $saida_at = $saidaValidation['normalized'];
            
            // Atualizar registro
            $this->db->query(
                "UPDATE registro_acesso SET saida_at = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$saida_at, $_SESSION['user_id'], $id]
            );
            
            $registroAtualizado = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
            
            // Registrar auditoria
            $this->auditService->log('CHECKOUT', 'registro_acesso', $id, $registro, $registroAtualizado);
            
            echo json_encode([
                'success' => true,
                'message' => 'Check-out realizado com sucesso',
                'data' => $registroAtualizado
            ]);
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Tratar erros específicos do banco de dados
            if (strpos($errorMessage, 'ux_registro_cpf_ativo') !== false) {
                $message = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'ux_registro_placa_ativa') !== false) {
                $message = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'chk_registro_horario_valido') !== false) {
                $message = "A hora de saída não pode ser anterior à hora de entrada.";
            } else {
                $message = $errorMessage;
            }
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
        }
    }
    
    /**
     * PUT /registros/:id - Editar registro
     */
    public function edit($id) {
        header('Content-Type: application/json');
        
        // Verificar permissão
        $this->authService->requirePermission('registro_acesso.update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        CSRFProtection::verifyRequest();
        
        try {
            // Buscar registro atual
            $registroAntes = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
            
            if (!$registroAntes) {
                throw new Exception("Registro não encontrado");
            }
            
            // Obter dados da requisição
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception("Dados inválidos");
            }
            
            // Filtrar campos editáveis baseado no perfil do usuário
            $dadosEditaveis = $this->authService->filterEditableData($input);
            
            if (empty($dadosEditaveis)) {
                throw new Exception("Nenhum campo editável foi fornecido");
            }
            
            // Validações temporais com DateTimeValidator
            if (isset($dadosEditaveis['entrada_at'])) {
                $entradaValidation = DateTimeValidator::validateEntryDateTime($dadosEditaveis['entrada_at']);
                if (!$entradaValidation['isValid']) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $entradaValidation['message']
                    ]);
                    return;
                }
                $dadosEditaveis['entrada_at'] = $entradaValidation['normalized'];
            }
            
            if (isset($dadosEditaveis['saida_at'])) {
                // Use entrada atual (modificada ou existente) como baseline
                $entradaBaseline = $dadosEditaveis['entrada_at'] ?? $registroAntes['entrada_at'];
                $saidaValidation = DateTimeValidator::validateExitDateTime($dadosEditaveis['saida_at'], $entradaBaseline);
                if (!$saidaValidation['isValid']) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $saidaValidation['message']
                    ]);
                    return;
                }
                $dadosEditaveis['saida_at'] = $saidaValidation['normalized'];
            }
            
            // Normalizar CPF e placa se fornecidos
            if (isset($dadosEditaveis['cpf'])) {
                $dadosEditaveis['cpf'] = preg_replace('/\D/', '', $dadosEditaveis['cpf']);
            }
            if (isset($dadosEditaveis['placa_veiculo'])) {
                $dadosEditaveis['placa_veiculo'] = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($dadosEditaveis['placa_veiculo'])));
            }
            
            // Construir query de update dinamicamente
            $setClauses = [];
            $params = [];
            
            foreach ($dadosEditaveis as $field => $value) {
                $setClauses[] = "$field = ?";
                $params[] = $value;
            }
            
            $setClauses[] = "updated_by = ?";
            $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $_SESSION['user_id'];
            $params[] = $id;
            
            $sql = "UPDATE registro_acesso SET " . implode(', ', $setClauses) . " WHERE id = ?";
            
            $this->db->query($sql, $params);
            
            $registroDepois = $this->db->fetch("SELECT * FROM registro_acesso WHERE id = ?", [$id]);
            
            // Registrar auditoria
            $this->auditService->log('UPDATE', 'registro_acesso', $id, $registroAntes, $registroDepois);
            
            echo json_encode([
                'success' => true,
                'message' => 'Registro editado com sucesso',
                'data' => $registroDepois
            ]);
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Tratar erros específicos do banco de dados
            if (strpos($errorMessage, 'ux_registro_cpf_ativo') !== false) {
                $message = "Este CPF já está ativo no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'ux_registro_placa_ativa') !== false) {
                $message = "Esta placa de veículo já está ativa no sistema. Não é possível registrar duas entradas simultâneas.";
            } elseif (strpos($errorMessage, 'chk_registro_horario_valido') !== false) {
                $message = "A hora de saída não pode ser anterior à hora de entrada.";
            } else {
                $message = $errorMessage;
            }
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
        }
    }
    
    /**
     * GET /registros - Listar registros
     */
    public function list() {
        header('Content-Type: application/json');
        
        // Verificar permissão
        $this->authService->requirePermission('registro_acesso.read');
        
        try {
            $sql = "SELECT ra.*, 
                           u1.nome as criado_por_nome,
                           u2.nome as atualizado_por_nome
                    FROM registro_acesso ra 
                    LEFT JOIN usuarios u1 ON ra.created_by = u1.id
                    LEFT JOIN usuarios u2 ON ra.updated_by = u2.id
                    ORDER BY ra.entrada_at DESC";
            
            $registros = $this->db->fetchAll($sql);
            
            echo json_encode([
                'success' => true,
                'data' => $registros
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar registros. Tente novamente.'
            ]);
        }
    }
    
    /**
     * GET /registros/alertas - Registros com permanência > 12h
     */
    public function alertas() {
        header('Content-Type: application/json');
        
        // Verificar permissão
        $this->authService->requirePermission('registro_acesso.read');
        
        try {
            $sql = "SELECT ra.*, 
                           u1.nome as criado_por_nome,
                           EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - ra.entrada_at))/3600 as horas_permanencia
                    FROM registro_acesso ra 
                    LEFT JOIN usuarios u1 ON ra.created_by = u1.id
                    WHERE ra.saida_at IS NULL 
                    AND ra.entrada_at < (CURRENT_TIMESTAMP - INTERVAL '12 hours')
                    ORDER BY ra.entrada_at ASC";
            
            $alertas = $this->db->fetchAll($sql);
            
            echo json_encode([
                'success' => true,
                'data' => $alertas,
                'count' => count($alertas)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar alertas. Tente novamente.'
            ]);
        }
    }
    
    /**
     * Validar dados de check-in
     */
    private function validateCheckInData($data) {
        $required = ['tipo', 'nome', 'cpf'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Campos obrigatórios: " . implode(', ', $missing));
        }
        
        // Validar tipo
        $allowedTypes = ['visitante', 'prestador_servico', 'profissional_renner'];
        if (!in_array($data['tipo'], $allowedTypes)) {
            throw new Exception("Tipo inválido. Permitidos: " . implode(', ', $allowedTypes));
        }
        
        // Validar e normalizar dados
        if (!empty($data['cpf'])) {
            $cpfValidation = CpfValidator::validateAndNormalize($data['cpf']);
            if (!$cpfValidation['isValid']) {
                throw new Exception($cpfValidation['message']);
            }
            $data['cpf'] = $cpfValidation['normalized'];
        }
        
        $data['placa_veiculo'] = isset($data['placa_veiculo']) ? 
            preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($data['placa_veiculo']))) : '';
        
        // Validar horário de entrada
        $entradaValidation = DateTimeValidator::validateEntryDateTime($data['entrada_at']);
        if (!$entradaValidation['isValid']) {
            throw new Exception($entradaValidation['message']);
        }
        $data['entrada_at'] = $entradaValidation['normalized'];
        
        return $data;
    }
    
    /**
     * Validar duplicidade usando o serviço existente
     */
    private function validateDuplicity($data) {
        // Validar CPF
        $cpfValidation = $this->duplicityService->validateCpfNotOpen($data['cpf']);
        if (!$cpfValidation['isValid']) {
            throw new Exception($cpfValidation['message']);
        }
        
        // Validar placa se fornecida
        if (!empty($data['placa_veiculo']) && $data['placa_veiculo'] !== 'APE') {
            $placaValidation = $this->duplicityService->validatePlacaNotOpen($data['placa_veiculo']);
            if (!$placaValidation['isValid']) {
                throw new Exception($placaValidation['message']);
            }
        }
    }
}