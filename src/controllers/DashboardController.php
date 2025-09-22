<?php

class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        try {
            // Get today's statistics
            $today = date('Y-m-d');
            
            $visitorsResult = $this->db->fetch("SELECT COUNT(*) as count FROM visitantes WHERE DATE(data_cadastro) = ?", [$today]);
            $employeesResult = $this->db->fetch("SELECT COUNT(*) as count FROM funcionarios WHERE ativo = TRUE");
            $entriesResult = $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'entrada'", [$today]);
            $exitsResult = $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'saida'", [$today]);
            
            $stats = [
                'visitors_today' => $visitorsResult['count'] ?? 0,
                'employees_active' => $employeesResult['count'] ?? 0,
                'entries_today' => $entriesResult['count'] ?? 0,
                'exits_today' => $exitsResult['count'] ?? 0
            ];
            
            // Recent access logs
            $recentAccess = $this->db->fetchAll("
                SELECT a.*, 
                       f.nome as funcionario_nome, f.foto as funcionario_foto,
                       v.nome as visitante_nome, v.foto as visitante_foto
                FROM acessos a
                LEFT JOIN funcionarios f ON a.funcionario_id = f.id
                LEFT JOIN visitantes v ON a.visitante_id = v.id
                ORDER BY a.data_hora DESC
                LIMIT 10
            ") ?? [];
            
        } catch (Exception $e) {
            // Initialize with default values if database queries fail
            $stats = [
                'visitors_today' => 0,
                'employees_active' => 0,
                'entries_today' => 0,
                'exits_today' => 0
            ];
            $recentAccess = [];
        }
        
        include '../views/dashboard/index.php';
    }
}