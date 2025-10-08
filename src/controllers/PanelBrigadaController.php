<?php
/**
 * Controller do Painel de Brigada de Incêndio
 * SEM LOGIN - Autenticação via IP allowlist + header X-Panel-Key
 */

class PanelBrigadaController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Renderiza painel fullscreen para TV
     */
    public function painel() {
        require __DIR__ . '/../../views/painel/brigada.php';
    }
    
    /**
     * API: Retorna brigadistas presentes na planta
     * GET /api/brigada/presentes
     */
    public function presentesApi() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        try {
            // Query: brigadistas ativos + presentes na planta
            // Adaptada ao schema real (sem start_date/end_date)
            $presentes = $this->db->fetchAll("
                SELECT 
                    p.nome,
                    p.setor,
                    p.foto_url,
                    r.profissional_renner_id as professional_id,
                    COALESCE(r.retorno, r.entrada_at) as desde,
                    b.active
                FROM registro_acesso r
                JOIN profissionais_renner p ON p.id = r.profissional_renner_id
                JOIN brigadistas b ON b.professional_id = p.id
                WHERE r.tipo = 'profissional_renner'
                  AND r.saida_final IS NULL
                  AND b.active = TRUE
                ORDER BY desde DESC
            ") ?? [];
            
            // Processar resultados
            $resultado = [];
            foreach ($presentes as $p) {
                // Normalizar boolean PostgreSQL
                $value = $p['active'] ?? false;
                $isActive = ($value === true || $value === 't' || $value === '1' || $value === 1);
                
                if (!$isActive) continue; // Skip se não ativo (redundância)
                
                // Formatar timestamp para ISO 8601
                $desde = $p['desde'];
                if ($desde) {
                    try {
                        $dt = new DateTime($desde, new DateTimeZone('America/Sao_Paulo'));
                        $desde = $dt->format('c'); // ISO 8601 com timezone
                    } catch (Exception $e) {
                        $desde = date('c', strtotime($desde));
                    }
                }
                
                $resultado[] = [
                    'nome' => $p['nome'],
                    'setor' => $p['setor'] ?? 'N/A',
                    'professional_id' => (int)$p['professional_id'],
                    'desde' => $desde,
                    'foto_url' => $p['foto_url'] ?? null
                    // NÃO incluir CPF (LGPD)
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $resultado,
                'timestamp' => date('c'),
                'total' => count($resultado)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            // Log de erro para monitoramento
            error_log('[PanelBrigada] Erro na API presentes: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar brigadistas presentes',
                'error' => ($_ENV['ENVIRONMENT'] ?? '') === 'development' ? $e->getMessage() : null
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
