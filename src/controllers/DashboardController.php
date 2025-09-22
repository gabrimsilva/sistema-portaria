<?php

class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        try {
            // Estatísticas dos novos módulos
            $totalProfissionais = $this->db->fetch("SELECT COUNT(*) as total FROM profissionais_renner")['total'] ?? 0;
            $totalVisitantes = $this->db->fetch("SELECT COUNT(*) as total FROM visitantes_novo")['total'] ?? 0;
            $totalPrestadores = $this->db->fetch("SELECT COUNT(*) as total FROM prestadores_servico")['total'] ?? 0;
            
            // Visitantes ativos na empresa
            $visitantesAtivos = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM visitantes_novo 
                WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL
            ")['total'] ?? 0;
            
            // Prestadores trabalhando
            $prestadoresTrabalhando = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM prestadores_servico 
                WHERE entrada IS NOT NULL AND saida IS NULL
            ")['total'] ?? 0;
            
            // Profissionais que saíram hoje e ainda não retornaram
            $profissionaisForaHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM profissionais_renner 
                WHERE DATE(saida) = CURRENT_DATE AND retorno IS NULL
            ")['total'] ?? 0;
            
            // Estatísticas compiladas
            $stats = [
                'total_profissionais' => $totalProfissionais,
                'total_visitantes' => $totalVisitantes,
                'total_prestadores' => $totalPrestadores,
                'visitantes_ativos' => $visitantesAtivos,
                'prestadores_trabalhando' => $prestadoresTrabalhando,
                'profissionais_fora' => $profissionaisForaHoje
            ];
            
            // Movimentação de hoje para gráficos
            $movimentacaoHoje = $this->getMovimentacaoHoje();
            $setoresMaisMovimentados = $this->getSetoresMaisMovimentados();
            
            include '../views/dashboard/index.php';
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $stats = [
                'total_profissionais' => 0,
                'total_visitantes' => 0,
                'total_prestadores' => 0,
                'visitantes_ativos' => 0,
                'prestadores_trabalhando' => 0,
                'profissionais_fora' => 0
            ];
            $movimentacaoHoje = ['visitantes' => 0, 'prestadores' => 0, 'profissionais' => 0];
            $setoresMaisMovimentados = [];
            include '../views/dashboard/index.php';
        }
    }
    
    private function getMovimentacaoHoje() {
        try {
            // Entradas de visitantes hoje
            $visitantesHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM visitantes_novo 
                WHERE DATE(hora_entrada) = CURRENT_DATE
            ")['total'] ?? 0;
            
            // Entradas de prestadores hoje
            $prestadoresHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM prestadores_servico 
                WHERE DATE(entrada) = CURRENT_DATE
            ")['total'] ?? 0;
            
            // Entradas de profissionais hoje (data_entrada)
            $profissionaisHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM profissionais_renner 
                WHERE DATE(data_entrada) = CURRENT_DATE
            ")['total'] ?? 0;
            
            return [
                'visitantes' => $visitantesHoje,
                'prestadores' => $prestadoresHoje,
                'profissionais' => $profissionaisHoje
            ];
        } catch (Exception $e) {
            return ['visitantes' => 0, 'prestadores' => 0, 'profissionais' => 0];
        }
    }
    
    private function getSetoresMaisMovimentados() {
        try {
            // Movimentação por setor nos últimos 7 dias
            $query = "
                SELECT setor, SUM(total) as total_movimentacao
                FROM (
                    (SELECT setor, COUNT(*) as total
                     FROM visitantes_novo 
                     WHERE setor IS NOT NULL AND hora_entrada >= CURRENT_DATE - INTERVAL '7 days'
                     GROUP BY setor)
                    UNION ALL
                    (SELECT setor, COUNT(*) as total
                     FROM prestadores_servico 
                     WHERE setor IS NOT NULL AND entrada >= CURRENT_DATE - INTERVAL '7 days'
                     GROUP BY setor)
                    UNION ALL
                    (SELECT setor, COUNT(*) as total
                     FROM profissionais_renner 
                     WHERE setor IS NOT NULL AND data_entrada >= CURRENT_DATE - INTERVAL '7 days'
                     GROUP BY setor)
                ) AS movimentacao
                GROUP BY setor
                ORDER BY total_movimentacao DESC 
                LIMIT 10
            ";
            
            return $this->db->fetchAll($query) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }
}