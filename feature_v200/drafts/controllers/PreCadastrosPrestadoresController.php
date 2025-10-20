<?php
/**
 * Controller: Pré-Cadastros de Prestadores de Serviço
 * 
 * Gerencia cadastros de prestadores com validade de 1 ano (reutilizáveis)
 * Separado dos registros de acesso (eventos pontuais de entrada/saída)
 * 
 * @version 2.0.0
 * @status DRAFT - NÃO APLICAR SEM APROVAÇÃO
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/AuditLogService.php';

class PreCadastrosPrestadoresController {
    private $db;
    private $authService;
    private $auditService;
    
    public function __construct() {
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->auditService = new AuditLogService();
    }
    
    /**
     * Lista de pré-cadastros (página principal)
     */
    public function index() {
        // Verificar permissão
        $this->authService->requirePermission('pre_cadastros.read');
        
        // Buscar estatísticas
        $stats = $this->getStats();
        
        // Renderizar view
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/index.php';
    }
    
    /**
     * Formulário de novo pré-cadastro
     */
    public function create() {
        $this->authService->requirePermission('pre_cadastros.create');
        
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/form.php';
    }
    
    /**
     * Salvar novo pré-cadastro
     */
    public function save() {
        $this->authService->requirePermission('pre_cadastros.create');
        
        try {
            // Capturar dados do formulário
            $nome = $_POST['nome'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $doc_type = $_POST['doc_type'] ?? 'CPF';
            $doc_number = $_POST['doc_number'] ?? '';
            $doc_country = $_POST['doc_country'] ?? 'Brasil';
            $placa_veiculo = $_POST['placa_veiculo'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;
            
            // Validade: padrão 1 ano a partir de hoje
            $valid_from = $_POST['valid_from'] ?? date('Y-m-d');
            $valid_until = $_POST['valid_until'] ?? null;
            
            // Se valid_until não informado, calcular +1 ano
            if (empty($valid_until)) {
                $dateFrom = new DateTime($valid_from);
                $dateFrom->modify('+1 year');
                $valid_until = $dateFrom->format('Y-m-d');
            }
            
            // Normalização de documento (type-aware)
            $doc_number = $this->normalizeDocument($doc_number, $doc_type);
            
            // Normalização de placa
            if (!empty($placa_veiculo) && strtoupper($placa_veiculo) !== 'APE') {
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
            }
            
            // Validações
            $this->validate($nome, $doc_type, $doc_number, $valid_from, $valid_until);
            
            // Verificar duplicidade
            $this->checkDuplicity($doc_type, $doc_number);
            
            // Inserir no banco
            $sql = "INSERT INTO prestadores_cadastro 
                    (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, 
                     valid_from, valid_until, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $nome, 
                $empresa, 
                $doc_type, 
                $doc_number, 
                $doc_country, 
                $placa_veiculo,
                $valid_from,
                $valid_until,
                $observacoes
            ]);
            
            // Auditoria
            $this->auditService->log(
                'pre_cadastros_prestadores',
                'create',
                null,
                "Pré-cadastro criado: $nome (Doc: $doc_type $doc_number, Válido até: $valid_until)"
            );
            
            // Redirect com sucesso
            $_SESSION['flash_success'] = 'Pré-cadastro criado com sucesso! Válido até ' . 
                                          date('d/m/Y', strtotime($valid_until));
            header('Location: /pre-cadastros/prestadores');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/prestadores?action=new');
            exit;
        }
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id) {
        $this->authService->requirePermission('pre_cadastros.update');
        
        $cadastro = $this->db->fetch(
            "SELECT * FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
        
        if (!$cadastro) {
            $_SESSION['flash_error'] = 'Cadastro não encontrado';
            header('Location: /pre-cadastros/prestadores');
            exit;
        }
        
        require_once __DIR__ . '/../../views/pre-cadastros/prestadores/form.php';
    }
    
    /**
     * Atualizar cadastro existente
     */
    public function update($id) {
        $this->authService->requirePermission('pre_cadastros.update');
        
        try {
            // Verificar se existe
            $existing = $this->db->fetch(
                "SELECT id FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if (!$existing) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Capturar dados
            $nome = $_POST['nome'] ?? '';
            $empresa = $_POST['empresa'] ?? '';
            $doc_type = $_POST['doc_type'] ?? 'CPF';
            $doc_number = $_POST['doc_number'] ?? '';
            $doc_country = $_POST['doc_country'] ?? 'Brasil';
            $placa_veiculo = $_POST['placa_veiculo'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;
            $valid_from = $_POST['valid_from'] ?? date('Y-m-d');
            $valid_until = $_POST['valid_until'] ?? null;
            
            // Normalização
            $doc_number = $this->normalizeDocument($doc_number, $doc_type);
            if (!empty($placa_veiculo) && strtoupper($placa_veiculo) !== 'APE') {
                $placa_veiculo = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa_veiculo));
            }
            
            // Validações
            $this->validate($nome, $doc_type, $doc_number, $valid_from, $valid_until);
            
            // Verificar duplicidade (excluindo o próprio registro)
            $this->checkDuplicity($doc_type, $doc_number, $id);
            
            // Atualizar
            $sql = "UPDATE prestadores_cadastro 
                    SET nome = ?, empresa = ?, doc_type = ?, doc_number = ?, 
                        doc_country = ?, placa_veiculo = ?, valid_from = ?, 
                        valid_until = ?, observacoes = ?, updated_at = NOW()
                    WHERE id = ?";
            
            $this->db->execute($sql, [
                $nome, $empresa, $doc_type, $doc_number, $doc_country,
                $placa_veiculo, $valid_from, $valid_until, $observacoes, $id
            ]);
            
            // Auditoria
            $this->auditService->log(
                'pre_cadastros_prestadores',
                'update',
                $id,
                "Pré-cadastro atualizado: $nome"
            );
            
            $_SESSION['flash_success'] = 'Cadastro atualizado com sucesso!';
            header('Location: /pre-cadastros/prestadores');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /pre-cadastros/prestadores?action=edit&id=' . $id);
            exit;
        }
    }
    
    /**
     * Excluir cadastro (soft delete)
     */
    public function delete($id) {
        $this->authService->requirePermission('pre_cadastros.delete');
        
        try {
            // Verificar se tem registros vinculados
            $count = $this->db->fetch(
                "SELECT COUNT(*) as total FROM prestadores_registros WHERE cadastro_id = ?",
                [$id]
            );
            
            if ($count['total'] > 0) {
                throw new Exception(
                    'Não é possível excluir este cadastro pois existem ' . 
                    $count['total'] . ' registros de acesso vinculados a ele.'
                );
            }
            
            // Soft delete
            $this->db->execute(
                "UPDATE prestadores_cadastro SET deleted_at = NOW() WHERE id = ?",
                [$id]
            );
            
            // Auditoria
            $this->auditService->log(
                'pre_cadastros_prestadores',
                'delete',
                $id,
                'Pré-cadastro excluído (soft delete)'
            );
            
            $_SESSION['flash_success'] = 'Cadastro excluído com sucesso!';
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /pre-cadastros/prestadores');
        exit;
    }
    
    /**
     * Renovar validade (+1 ano)
     */
    public function renovar($id) {
        $this->authService->requirePermission('pre_cadastros.renovar');
        
        try {
            $cadastro = $this->db->fetch(
                "SELECT nome FROM prestadores_cadastro WHERE id = ? AND deleted_at IS NULL",
                [$id]
            );
            
            if (!$cadastro) {
                throw new Exception('Cadastro não encontrado');
            }
            
            // Calcular nova validade (+1 ano a partir de hoje)
            $novaValidade = date('Y-m-d', strtotime('+1 year'));
            
            // Atualizar
            $this->db->execute(
                "UPDATE prestadores_cadastro 
                 SET valid_until = ?, ativo = true, updated_at = NOW() 
                 WHERE id = ?",
                [$novaValidade, $id]
            );
            
            // Auditoria
            $this->auditService->log(
                'pre_cadastros_prestadores',
                'renovar',
                $id,
                "Validade renovada até: $novaValidade"
            );
            
            $_SESSION['flash_success'] = 'Cadastro renovado até ' . 
                                          date('d/m/Y', strtotime($novaValidade));
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /pre-cadastros/prestadores');
        exit;
    }
    
    /**
     * Buscar estatísticas
     */
    private function getStats() {
        $total = $this->db->fetch(
            "SELECT COUNT(*) as total FROM prestadores_cadastro WHERE deleted_at IS NULL"
        )['total'];
        
        $validos = $this->db->fetch(
            "SELECT COUNT(*) as total FROM vw_prestadores_cadastro_status 
             WHERE status_validade = 'valido'"
        )['total'];
        
        $expirando = $this->db->fetch(
            "SELECT COUNT(*) as total FROM vw_prestadores_cadastro_status 
             WHERE status_validade = 'expirando'"
        )['total'];
        
        $expirados = $this->db->fetch(
            "SELECT COUNT(*) as total FROM vw_prestadores_cadastro_status 
             WHERE status_validade = 'expirado'"
        )['total'];
        
        return [
            'total' => $total,
            'validos' => $validos,
            'expirando' => $expirando,
            'expirados' => $expirados
        ];
    }
    
    /**
     * Validações
     */
    private function validate($nome, $doc_type, $doc_number, $valid_from, $valid_until) {
        if (empty($nome)) {
            throw new Exception('Nome é obrigatório');
        }
        
        if (empty($doc_type)) {
            throw new Exception('Tipo de documento é obrigatório');
        }
        
        if (empty($doc_number)) {
            throw new Exception('Número do documento é obrigatório');
        }
        
        if (empty($valid_from)) {
            throw new Exception('Data de início da validade é obrigatória');
        }
        
        if (empty($valid_until)) {
            throw new Exception('Data de fim da validade é obrigatória');
        }
        
        // Validar datas
        $dateFrom = new DateTime($valid_from);
        $dateUntil = new DateTime($valid_until);
        
        if ($dateUntil <= $dateFrom) {
            throw new Exception('Data de fim deve ser posterior à data de início');
        }
    }
    
    /**
     * Verificar duplicidade
     */
    private function checkDuplicity($doc_type, $doc_number, $excludeId = null) {
        $sql = "SELECT id, nome FROM prestadores_cadastro 
                WHERE doc_type = ? AND doc_number = ? AND deleted_at IS NULL";
        $params = [$doc_type, $doc_number];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $existing = $this->db->fetch($sql, $params);
        
        if ($existing) {
            throw new Exception(
                "Já existe um cadastro com este documento: {$existing['nome']}"
            );
        }
    }
    
    /**
     * Normalizar documento conforme tipo
     */
    private function normalizeDocument($doc, $type) {
        // Documentos brasileiros: apenas dígitos
        if (in_array($type, ['CPF', 'RG', 'CNH'])) {
            return preg_replace('/[^0-9]/', '', $doc);
        }
        
        // Documentos internacionais: alfanumérico uppercase
        return strtoupper(trim($doc));
    }
}
