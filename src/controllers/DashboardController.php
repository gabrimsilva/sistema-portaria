<?php

class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->checkAuthentication();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    private function countAtivosAgora($tipo) {
        try {
            switch ($tipo) {
                case 'visitante':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM visitantes_novo 
                        WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL
                    ")['total'] ?? 0;
                
                case 'prestador':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM prestadores_servico 
                        WHERE entrada IS NOT NULL AND saida IS NULL
                    ")['total'] ?? 0;
                
                case 'profissional_renner':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM registro_acesso 
                        WHERE tipo = 'profissional_renner' 
                          AND (entrada_at IS NOT NULL OR retorno IS NOT NULL) 
                          AND saida_final IS NULL
                    ")['total'] ?? 0;
                
                default:
                    return 0;
            }
        } catch (Exception $e) {
            error_log("Erro countAtivosAgora($tipo): " . $e->getMessage());
            return 0;
        }
    }
    
    private function countRegistradosHoje($tipo) {
        try {
            switch ($tipo) {
                case 'visitante':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM visitantes_novo 
                        WHERE hora_entrada >= CURRENT_DATE 
                          AND hora_entrada < CURRENT_DATE + INTERVAL '1 day'
                    ")['total'] ?? 0;
                
                case 'prestador':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM prestadores_servico 
                        WHERE entrada >= CURRENT_DATE 
                          AND entrada < CURRENT_DATE + INTERVAL '1 day'
                    ")['total'] ?? 0;
                
                case 'profissional_renner':
                    return $this->db->fetch("
                        SELECT COUNT(*) as total 
                        FROM registro_acesso 
                        WHERE tipo = 'profissional_renner' 
                          AND ((entrada_at >= CURRENT_DATE AND entrada_at < CURRENT_DATE + INTERVAL '1 day')
                           OR (retorno >= CURRENT_DATE AND retorno < CURRENT_DATE + INTERVAL '1 day'))
                    ")['total'] ?? 0;
                
                default:
                    return 0;
            }
        } catch (Exception $e) {
            error_log("Erro countRegistradosHoje($tipo): " . $e->getMessage());
            return 0;
        }
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
                FROM registro_acesso 
                WHERE tipo = 'profissional_renner' AND DATE(saida_at) = CURRENT_DATE AND retorno IS NULL
            ")['total'] ?? 0;
            
            // Profissionais Renner ativos (funcionários reais da empresa)
            $profissionaisAtivos = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM registro_acesso 
                WHERE tipo = 'profissional_renner' AND (entrada_at IS NOT NULL OR retorno IS NOT NULL) AND saida_final IS NULL
            ")['total'] ?? 0;
            
            // Entradas de hoje (de todos os tipos)
            $movimentacao = $this->getMovimentacaoHoje();
            $entradasHoje = $movimentacao['visitantes'] + $movimentacao['prestadores'] + $movimentacao['profissionais'];
            
            // Saídas de hoje (de todos os tipos)
            $saidasHoje = $this->getSaidasHoje();
            
            // Estatísticas compiladas para a view
            $stats = [
                // NOVAS métricas padronizadas para os cards
                'profissional_ativos' => $this->countAtivosAgora('profissional_renner'),
                'profissional_hoje' => $this->countRegistradosHoje('profissional_renner'),
                'visitante_ativos' => $this->countAtivosAgora('visitante'),
                'visitante_hoje' => $this->countRegistradosHoje('visitante'),
                'prestador_ativos' => $this->countAtivosAgora('prestador'),
                'prestador_hoje' => $this->countRegistradosHoje('prestador'),
                // TOTAIS (somas)
                'total_ativos' => 
                    $this->countAtivosAgora('profissional_renner') + 
                    $this->countAtivosAgora('visitante') + 
                    $this->countAtivosAgora('prestador'),
                'total_registrados_hoje' => 
                    $this->countRegistradosHoje('profissional_renner') + 
                    $this->countRegistradosHoje('visitante') + 
                    $this->countRegistradosHoje('prestador'),
                // Manter compatibilidade com código legado
                'visitors_today' => $movimentacao['visitantes'],
                'employees_active' => $profissionaisAtivos,
                'entries_today' => $entradasHoje,
                'exits_today' => $saidasHoje,
                'total_profissionais' => $totalProfissionais,
                'total_visitantes' => $totalVisitantes,
                'total_prestadores' => $totalPrestadores,
                'visitantes_ativos' => $visitantesAtivos,
                'prestadores_trabalhando' => $prestadoresTrabalhando,
                'profissionais_fora' => $profissionaisForaHoje
            ];
            
            // Setores mais movimentados para gráficos
            $setoresMaisMovimentados = $this->getSetoresMaisMovimentados();
            
            // Pessoas atualmente na empresa
            $pessoasNaEmpresa = $this->getPessoasNaEmpresa();
            
            include '../views/dashboard/index.php';
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $stats = [
                'visitors_today' => 0,
                'employees_active' => 0,
                'entries_today' => 0,
                'exits_today' => 0,
                'total_profissionais' => 0,
                'total_visitantes' => 0,
                'total_prestadores' => 0,
                'visitantes_ativos' => 0,
                'prestadores_trabalhando' => 0,
                'profissionais_fora' => 0
            ];
            $setoresMaisMovimentados = [];
            $pessoasNaEmpresa = [];
            include '../views/dashboard/index.php';
        }
    }
    
    private function getMovimentacaoHoje() {
        $result = ['visitantes' => 0, 'prestadores' => 0, 'profissionais' => 0];
        
        // Entradas de visitantes hoje (usando range predicates)
        try {
            $visitantesHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM visitantes_novo 
                WHERE hora_entrada >= CURRENT_DATE AND hora_entrada < CURRENT_DATE + INTERVAL '1 day'
            ")['total'] ?? 0;
            $result['visitantes'] = $visitantesHoje;
        } catch (Exception $e) {
            error_log("Erro ao contar visitantes hoje: " . $e->getMessage());
        }
        
        // Entradas de prestadores hoje
        try {
            $prestadoresHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM prestadores_servico 
                WHERE entrada >= CURRENT_DATE AND entrada < CURRENT_DATE + INTERVAL '1 day'
            ")['total'] ?? 0;
            $result['prestadores'] = $prestadoresHoje;
        } catch (Exception $e) {
            error_log("Erro ao contar prestadores hoje: " . $e->getMessage());
        }
        
        // Entradas de profissionais hoje (entrada_at OU retorno)
        try {
            $profissionaisHoje = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM registro_acesso 
                WHERE tipo = 'profissional_renner' 
                  AND ((entrada_at >= CURRENT_DATE AND entrada_at < CURRENT_DATE + INTERVAL '1 day')
                   OR (retorno >= CURRENT_DATE AND retorno < CURRENT_DATE + INTERVAL '1 day'))
            ")['total'] ?? 0;
            $result['profissionais'] = $profissionaisHoje;
        } catch (Exception $e) {
            error_log("Erro ao contar profissionais hoje: " . $e->getMessage());
        }
        
        return $result;
    }
    
    private function getSaidasHoje() {
        $totalSaidas = 0;
        
        // Saídas de visitantes hoje
        try {
            $visitantesSaidas = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM visitantes_novo 
                WHERE hora_saida >= CURRENT_DATE AND hora_saida < CURRENT_DATE + INTERVAL '1 day'
            ")['total'] ?? 0;
            $totalSaidas += $visitantesSaidas;
        } catch (Exception $e) {
            error_log("Erro ao contar saídas de visitantes hoje: " . $e->getMessage());
        }
        
        // Saídas de prestadores hoje
        try {
            $prestadoresSaidas = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM prestadores_servico 
                WHERE saida >= CURRENT_DATE AND saida < CURRENT_DATE + INTERVAL '1 day'
            ")['total'] ?? 0;
            $totalSaidas += $prestadoresSaidas;
        } catch (Exception $e) {
            error_log("Erro ao contar saídas de prestadores hoje: " . $e->getMessage());
        }
        
        // Saídas finais de profissionais hoje
        try {
            $profissionaisSaidas = $this->db->fetch("
                SELECT COUNT(*) as total 
                FROM registro_acesso 
                WHERE tipo = 'profissional_renner' AND saida_final >= CURRENT_DATE AND saida_final < CURRENT_DATE + INTERVAL '1 day'
            ")['total'] ?? 0;
            $totalSaidas += $profissionaisSaidas;
        } catch (Exception $e) {
            error_log("Erro ao contar saídas de profissionais hoje: " . $e->getMessage());
        }
        
        return $totalSaidas;
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
                    (SELECT p.setor, COUNT(*) as total
                     FROM registro_acesso r
                     JOIN profissionais_renner p ON p.id = r.profissional_renner_id
                     WHERE r.tipo = 'profissional_renner' AND p.setor IS NOT NULL AND r.entrada_at >= CURRENT_DATE - INTERVAL '7 days'
                     GROUP BY p.setor)
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
    
    private function getPessoasNaEmpresa() {
        try {
            $pessoas = [];
            
            // Visitantes na empresa (entraram mas não saíram)
            $visitantesAtivos = $this->db->fetchAll("
                SELECT nome, cpf, empresa, setor, hora_entrada, 'Visitante' as tipo, id, placa_veiculo, funcionario_responsavel
                FROM visitantes_novo 
                WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL
                ORDER BY hora_entrada DESC
            ") ?? [];
            
            // Prestadores trabalhando (entraram mas não saíram)
            $prestadoresAtivos = $this->db->fetchAll("
                SELECT nome, cpf, empresa, setor, entrada as hora_entrada, 'Prestador' as tipo, id, placa_veiculo, funcionario_responsavel
                FROM prestadores_servico 
                WHERE entrada IS NOT NULL AND saida IS NULL
                ORDER BY entrada DESC
            ") ?? [];
            
            // Profissionais que estão na empresa (têm entrada_at ou retorno, mas não saída_final)
            $profissionaisAtivos = $this->db->fetchAll("
                SELECT p.nome, r.cpf, r.empresa, p.setor, 
                       COALESCE(r.retorno, r.entrada_at) as hora_entrada, 
                       'Profissional Renner' as tipo, r.id, r.placa_veiculo
                FROM registro_acesso r
                JOIN profissionais_renner p ON p.id = r.profissional_renner_id
                WHERE r.tipo = 'profissional_renner' 
                  AND (r.entrada_at IS NOT NULL OR r.retorno IS NOT NULL) 
                  AND r.saida_final IS NULL
                ORDER BY COALESCE(r.retorno, r.entrada_at) DESC
            ") ?? [];
            
            // Combinar todos os tipos
            $pessoas = array_merge($visitantesAtivos, $prestadoresAtivos, $profissionaisAtivos);
            
            // Ordenar por hora de entrada mais recente
            usort($pessoas, function($a, $b) {
                return strtotime($b['hora_entrada']) - strtotime($a['hora_entrada']);
            });
            
            return $pessoas;
        } catch (Exception $e) {
            return [];
        }
    }
}