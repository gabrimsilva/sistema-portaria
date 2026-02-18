<?php

class PainelBrigadaController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        $pageTitle = 'Painel de Brigada de Incêndio';
        $currentPage = 'painel-brigada';
        
        $brigadistas = $this->getBrigadistasAtivos();
        
        include __DIR__ . '/../../views/painel/brigada.php';
    }
    
    public function api() {
        header('Content-Type: application/json');
        
        try {
            $brigadistas = $this->getBrigadistasAtivos();
            
            echo json_encode([
                'success' => true,
                'data' => $brigadistas,
                'total' => count($brigadistas)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function getBrigadistasAtivos() {
        $query = "
            SELECT 
                b.id,
                pr.id as professional_id,
                pr.nome,
                pr.setor,
                pr.empresa,
                pr.foto_url,
                b.ramal,
                b.note,
                r.entrada_at
            FROM brigadistas b
            INNER JOIN profissionais_renner pr ON pr.id = b.professional_id
            INNER JOIN registro_acesso r ON r.profissional_renner_id = pr.id
            WHERE b.active = true
              AND r.saida_at IS NULL
              AND r.data_saida IS NULL
              AND r.saida_final IS NULL
              AND r.tipo = 'profissional_renner'
            ORDER BY pr.nome
        ";
        
        $brigadistas = $this->db->fetchAll($query);
        
        foreach ($brigadistas as &$brigadista) {
            if (!empty($brigadista['foto_url'])) {
                $fotoPath = __DIR__ . '/../../public' . $brigadista['foto_url'];
                $realPath = realpath($fotoPath);
                
                if ($realPath === false || strpos($realPath, realpath(__DIR__ . '/../../public/uploads/profissionais/')) !== 0) {
                    $brigadista['foto_url'] = '/assets/images/default-avatar.png';
                }
            } else {
                $brigadista['foto_url'] = '/assets/images/default-avatar.png';
            }
        }
        
        return $brigadistas;
    }
}
