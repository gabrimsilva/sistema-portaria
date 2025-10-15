<?php
// ================================================
// CONTROLLER: DOCUMENTOS INTERNACIONAIS
// Versão: 2.0.0
// Objetivo: Validação e gestão de documentos estrangeiros
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para src/ sem aprovação!

class DocumentoController {
    private $db;
    
    public function __construct() {
        $this->checkAuthentication();
        require_once __DIR__ . '/../../src/config/database.php';
        $this->db = new Database();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
    }
    
    /**
     * GET /api/documentos/tipos
     * Retorna tipos de documentos disponíveis
     */
    public function getTipos() {
        header('Content-Type: application/json');
        
        try {
            // Buscar tipos do ENUM no banco
            $result = $this->db->query("
                SELECT enumlabel AS tipo 
                FROM pg_enum 
                WHERE enumtypid = 'document_type'::regtype
                ORDER BY enumsortorder
            ")->fetchAll();
            
            $tipos = array_column($result, 'tipo');
            
            echo json_encode([
                'success' => true,
                'tipos' => $tipos
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar tipos de documentos: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/documentos/paises
     * Retorna lista de países (ISO-3166 alpha-2)
     */
    public function getPaises() {
        header('Content-Type: application/json');
        
        $paises = [
            'BR' => 'Brasil',
            'AR' => 'Argentina',
            'BO' => 'Bolívia',
            'CL' => 'Chile',
            'CO' => 'Colômbia',
            'EC' => 'Equador',
            'GF' => 'Guiana Francesa',
            'GY' => 'Guiana',
            'PY' => 'Paraguai',
            'PE' => 'Peru',
            'SR' => 'Suriname',
            'UY' => 'Uruguai',
            'VE' => 'Venezuela',
            'US' => 'Estados Unidos',
            'CA' => 'Canadá',
            'MX' => 'México',
            'PT' => 'Portugal',
            'ES' => 'Espanha',
            'IT' => 'Itália',
            'FR' => 'França',
            'DE' => 'Alemanha',
            'UK' => 'Reino Unido',
            'CN' => 'China',
            'JP' => 'Japão',
            'KR' => 'Coreia do Sul',
            'IN' => 'Índia',
            'OTHER' => 'Outro'
        ];
        
        echo json_encode([
            'success' => true,
            'paises' => $paises
        ]);
    }
    
    /**
     * POST /api/documentos/validar
     * Valida documento conforme tipo
     * 
     * Body: {
     *   "doc_type": "CPF|RG|PASSAPORTE|...",
     *   "doc_number": "123456789",
     *   "doc_country": "BR"
     * }
     */
    public function validar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $docType = $data['doc_type'] ?? null;
            $docNumber = $data['doc_number'] ?? null;
            $docCountry = $data['doc_country'] ?? 'BR';
            
            if (!$docType || !$docNumber) {
                throw new Exception('Tipo e número de documento são obrigatórios');
            }
            
            // Validar conforme tipo
            require_once __DIR__ . '/../../src/services/DocumentValidator.php';
            $validation = DocumentValidator::validate($docType, $docNumber, $docCountry);
            
            echo json_encode($validation);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /api/documentos/buscar?doc_number=X&doc_type=Y
     * Busca pessoa por documento
     */
    public function buscar() {
        header('Content-Type: application/json');
        
        try {
            $docNumber = $_GET['doc_number'] ?? null;
            $docType = $_GET['doc_type'] ?? null;
            
            if (!$docNumber) {
                throw new Exception('Número do documento é obrigatório');
            }
            
            // Normalizar número
            $docNumber = preg_replace('/[^A-Z0-9]/', '', strtoupper($docNumber));
            
            // Buscar em todas as tabelas
            $results = [
                'visitantes' => [],
                'prestadores' => [],
                'profissionais' => []
            ];
            
            // Visitantes
            $whereClause = "doc_number = ?";
            $params = [$docNumber];
            
            if ($docType) {
                $whereClause .= " AND doc_type = ?";
                $params[] = $docType;
            }
            
            $results['visitantes'] = $this->db->query("
                SELECT id, nome, doc_type, doc_number, doc_country, empresa, 
                       pessoa_visitada, status, data_cadastro, data_vencimento, validity_status
                FROM visitantes
                WHERE $whereClause
                ORDER BY data_cadastro DESC
                LIMIT 10
            ", $params)->fetchAll();
            
            // Prestadores
            $results['prestadores'] = $this->db->query("
                SELECT id, nome, doc_type, doc_number, doc_country, empresa, 
                       setor, funcionario_responsavel, entrada, saida
                FROM prestadores_servico
                WHERE $whereClause
                ORDER BY entrada DESC
                LIMIT 10
            ", $params)->fetchAll();
            
            // Profissionais
            $results['profissionais'] = $this->db->query("
                SELECT id, nome, doc_type, doc_number, doc_country, setor, 
                       empresa, data_entrada, data_admissao
                FROM profissionais_renner
                WHERE $whereClause
                ORDER BY data_entrada DESC
                LIMIT 10
            ", $params)->fetchAll();
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'total' => count($results['visitantes']) + count($results['prestadores']) + count($results['profissionais'])
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
