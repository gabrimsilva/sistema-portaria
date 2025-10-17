<?php
/**
 * API Controller: Pré-Cadastros
 * 
 * APIs para integração com o Dashboard:
 * - Busca de pré-cadastros (autocomplete)
 * - Verificação de validade
 * - Obtenção de dados para preencher formulário
 * - Renovação de cadastros expirados
 * 
 * @version 2.0.0
 * @status DRAFT - NÃO APLICAR SEM APROVAÇÃO
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

class ApiPreCadastrosController {
    private $db;
    private $authService;
    
    public function __construct() {
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        header('Content-Type: application/json');
    }
    
    /**
     * API: Buscar pré-cadastros (autocomplete no dashboard)
     * 
     * GET /api/pre-cadastros/buscar?q=joão&tipo=visitante
     * 
     * Retorna lista de cadastros que correspondem à busca
     */
    public function buscar() {
        try {
            $this->authService->requirePermission('pre_cadastros.read');
            
            $query = $_GET['q'] ?? '';
            $tipo = $_GET['tipo'] ?? 'visitante'; // 'visitante' ou 'prestador'
            
            if (strlen($query) < 2) {
                echo json_encode([
                    'success' => true,
                    'results' => []
                ]);
                exit;
            }
            
            // Determinar tabela/view
            $table = $tipo === 'visitante' 
                ? 'vw_visitantes_cadastro_status' 
                : 'vw_prestadores_cadastro_status';
            
            // Buscar por nome, documento ou empresa
            $sql = "SELECT 
                        id,
                        nome,
                        empresa,
                        doc_type,
                        doc_number,
                        doc_country,
                        placa_veiculo,
                        valid_until,
                        status_validade,
                        dias_restantes,
                        dias_expirado
                    FROM $table
                    WHERE (
                        nome ILIKE ? OR 
                        doc_number LIKE ? OR 
                        empresa ILIKE ?
                    )
                    AND ativo = true
                    ORDER BY 
                        CASE status_validade
                            WHEN 'valido' THEN 1
                            WHEN 'expirando' THEN 2
                            WHEN 'expirado' THEN 3
                        END,
                        nome
                    LIMIT 10";
            
            $searchTerm = "%$query%";
            $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
            
            // Formatar resultados para autocomplete
            $formatted = array_map(function($r) {
                return [
                    'id' => $r['id'],
                    'label' => $this->formatLabel($r),
                    'value' => $r['nome'],
                    'data' => $r
                ];
            }, $results);
            
            echo json_encode([
                'success' => true,
                'results' => $formatted
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * API: Verificar validade de cadastro
     * 
     * GET /api/pre-cadastros/verificar-validade?id=123&tipo=visitante
     * 
     * Retorna se o cadastro está válido, expirando ou expirado
     */
    public function verificarValidade($id, $tipo) {
        try {
            $this->authService->requirePermission('pre_cadastros.read');
            
            $table = $tipo === 'visitante' 
                ? 'vw_visitantes_cadastro_status' 
                : 'vw_prestadores_cadastro_status';
            
            $cadastro = $this->db->fetch(
                "SELECT id, nome, status_validade, dias_restantes, dias_expirado, valid_until
                 FROM $table WHERE id = ?",
                [$id]
            );
            
            if (!$cadastro) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cadastro não encontrado'
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'valido' => $cadastro['status_validade'] === 'valido',
                'status' => $cadastro['status_validade'],
                'dias_restantes' => $cadastro['dias_restantes'],
                'dias_expirado' => $cadastro['dias_expirado'],
                'valid_until' => $cadastro['valid_until'],
                'mensagem' => $this->getStatusMessage($cadastro)
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * API: Obter dados do pré-cadastro (preencher formulário dashboard)
     * 
     * GET /api/pre-cadastros/obter-dados?id=123&tipo=visitante
     * 
     * Retorna todos os dados para preencher o formulário de entrada
     */
    public function obterDados($id, $tipo) {
        try {
            $this->authService->requirePermission('pre_cadastros.read');
            
            $table = $tipo === 'visitante' 
                ? 'visitantes_cadastro' 
                : 'prestadores_cadastro';
            
            $cadastro = $this->db->fetch(
                "SELECT * FROM $table WHERE id = ? AND deleted_at IS NULL AND ativo = true",
                [$id]
            );
            
            if (!$cadastro) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cadastro não encontrado ou inativo'
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'cadastro_id' => $cadastro['id'],
                    'nome' => $cadastro['nome'],
                    'empresa' => $cadastro['empresa'],
                    'doc_type' => $cadastro['doc_type'],
                    'doc_number' => $cadastro['doc_number'],
                    'doc_country' => $cadastro['doc_country'],
                    'placa_veiculo' => $cadastro['placa_veiculo'],
                    'observacoes' => $cadastro['observacoes']
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * API: Renovar cadastro expirado (+1 ano)
     * 
     * POST /api/pre-cadastros/renovar
     * Body: {"id": 123, "tipo": "visitante"}
     */
    public function renovar() {
        try {
            $this->authService->requirePermission('pre_cadastros.update');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $tipo = $input['tipo'] ?? 'visitante';
            
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            $table = $tipo === 'visitante' 
                ? 'visitantes_cadastro' 
                : 'prestadores_cadastro';
            
            // Nova validade: hoje + 1 ano
            $newValidFrom = date('Y-m-d');
            $newValidUntil = date('Y-m-d', strtotime('+1 year'));
            
            $sql = "UPDATE $table 
                    SET valid_from = ?, valid_until = ?, ativo = true 
                    WHERE id = ? AND deleted_at IS NULL";
            
            $this->db->execute($sql, [$newValidFrom, $newValidUntil, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro renovado até ' . date('d/m/Y', strtotime($newValidUntil)),
                'valid_until' => $newValidUntil
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * API: Estatísticas de pré-cadastros (dashboard cards)
     * 
     * GET /api/pre-cadastros/stats
     */
    public function stats() {
        try {
            $this->authService->requirePermission('pre_cadastros.read');
            
            $stats = [
                'visitantes' => [
                    'total' => $this->getCount('visitantes_cadastro'),
                    'validos' => $this->getCountByStatus('vw_visitantes_cadastro_status', 'valido'),
                    'expirando' => $this->getCountByStatus('vw_visitantes_cadastro_status', 'expirando'),
                    'expirados' => $this->getCountByStatus('vw_visitantes_cadastro_status', 'expirado')
                ],
                'prestadores' => [
                    'total' => $this->getCount('prestadores_cadastro'),
                    'validos' => $this->getCountByStatus('vw_prestadores_cadastro_status', 'valido'),
                    'expirando' => $this->getCountByStatus('vw_prestadores_cadastro_status', 'expirando'),
                    'expirados' => $this->getCountByStatus('vw_prestadores_cadastro_status', 'expirado')
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================
    
    /**
     * Formatar label para autocomplete
     */
    private function formatLabel($cadastro) {
        $docMasked = $this->maskDocument($cadastro['doc_number']);
        $status = $this->getStatusBadge($cadastro['status_validade']);
        
        return sprintf(
            "%s - %s %s %s",
            $cadastro['nome'],
            $cadastro['doc_type'],
            $docMasked,
            $status
        );
    }
    
    /**
     * Mascarar documento (LGPD)
     */
    private function maskDocument($doc) {
        $len = strlen($doc);
        if ($len <= 4) return $doc;
        return str_repeat('*', $len - 4) . substr($doc, -4);
    }
    
    /**
     * Badge de status
     */
    private function getStatusBadge($status) {
        $badges = [
            'valido' => '✅',
            'expirando' => '⚠️',
            'expirado' => '❌'
        ];
        return $badges[$status] ?? '';
    }
    
    /**
     * Mensagem de status
     */
    private function getStatusMessage($cadastro) {
        switch ($cadastro['status_validade']) {
            case 'valido':
                return "Válido até " . date('d/m/Y', strtotime($cadastro['valid_until']));
            case 'expirando':
                return "Expira em {$cadastro['dias_restantes']} dias";
            case 'expirado':
                return "Expirado há {$cadastro['dias_expirado']} dias";
            default:
                return "Status desconhecido";
        }
    }
    
    /**
     * Contar cadastros por tabela
     */
    private function getCount($table) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM $table 
             WHERE deleted_at IS NULL AND ativo = true"
        );
        return (int)$result['count'];
    }
    
    /**
     * Contar por status
     */
    private function getCountByStatus($view, $status) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM $view WHERE status_validade = ?",
            [$status]
        );
        return (int)$result['count'];
    }
}
