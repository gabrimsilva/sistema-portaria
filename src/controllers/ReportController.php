<?php

class ReportController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                $this->dashboard();
                break;
            case 'visitors':
                $this->visitorsReport();
                break;
            case 'employees':
                $this->employeesReport();
                break;
            case 'access':
                $this->accessReport();
                break;
            case 'export':
                $this->export();
                break;
            default:
                $this->dashboard();
                break;
        }
    }
    
    public function dashboard() {
        // Get date range
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Overall statistics
        $stats = [
            'total_visitors' => $this->db->fetch("SELECT COUNT(*) as count FROM visitantes WHERE data_cadastro BETWEEN ? AND ?", [$startDate, $endDate])['count'] ?? 0,
            'total_employees' => $this->db->fetch("SELECT COUNT(*) as count FROM funcionarios WHERE data_admissao <= ?", [$endDate])['count'] ?? 0,
            'total_accesses' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE data_hora BETWEEN ? AND ?", [$startDate, $endDate])['count'] ?? 0,
            'entries_today' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = CURRENT_DATE AND tipo = 'entrada'")['count'] ?? 0
        ];
        
        // Daily access data for chart
        $dailyAccess = $this->db->fetchAll("
            SELECT 
                DATE(data_hora) as date,
                COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as entries,
                COUNT(CASE WHEN tipo = 'saida' THEN 1 END) as exits
            FROM acessos 
            WHERE data_hora BETWEEN ? AND ?
            GROUP BY DATE(data_hora)
            ORDER BY date ASC
        ", [$startDate, $endDate]);
        
        // Top visitors by access frequency
        $topVisitors = $this->db->fetchAll("
            SELECT v.nome, v.empresa, COUNT(a.id) as access_count
            FROM visitantes v
            LEFT JOIN acessos a ON v.id = a.visitante_id
            WHERE v.data_cadastro BETWEEN ? AND ?
            GROUP BY v.id, v.nome, v.empresa
            HAVING COUNT(a.id) > 0
            ORDER BY access_count DESC
            LIMIT 10
        ", [$startDate, $endDate]);
        
        // Access by hour
        $hourlyAccess = $this->db->fetchAll("
            SELECT 
                EXTRACT(HOUR FROM data_hora) as hour,
                COUNT(*) as total
            FROM acessos 
            WHERE data_hora BETWEEN ? AND ?
            GROUP BY EXTRACT(HOUR FROM data_hora)
            ORDER BY hour ASC
        ", [$startDate, $endDate]);
        
        include '../views/reports/dashboard.php';
    }
    
    public function visitorsReport() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';
        $company = $_GET['company'] ?? '';
        
        $sql = "SELECT * FROM visitantes WHERE data_cadastro BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        if (!empty($company)) {
            $sql .= " AND empresa ILIKE ?";
            $params[] = "%$company%";
        }
        
        $sql .= " ORDER BY data_cadastro DESC";
        
        $visitors = $this->db->fetchAll($sql, $params);
        
        // Get unique companies for filter
        $companies = $this->db->fetchAll("
            SELECT DISTINCT empresa 
            FROM visitantes 
            WHERE empresa IS NOT NULL AND empresa != ''
            ORDER BY empresa ASC
        ");
        
        include '../views/reports/visitors.php';
    }
    
    public function employeesReport() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';
        $department = $_GET['department'] ?? '';
        
        $sql = "SELECT * FROM funcionarios WHERE data_admissao <= ?";
        $params = [$endDate];
        
        if ($status !== '') {
            $sql .= " AND ativo = ?";
            $params[] = $status === '1';
        }
        
        if (!empty($department)) {
            $sql .= " AND cargo ILIKE ?";
            $params[] = "%$department%";
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $employees = $this->db->fetchAll($sql, $params);
        
        // Get unique departments for filter
        $departments = $this->db->fetchAll("
            SELECT DISTINCT cargo 
            FROM funcionarios 
            WHERE cargo IS NOT NULL AND cargo != ''
            ORDER BY cargo ASC
        ");
        
        include '../views/reports/employees.php';
    }
    
    public function accessReport() {
        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? '';
        $person_type = $_GET['person_type'] ?? '';
        
        $sql = "
            SELECT a.*, 
                   f.nome as funcionario_nome, f.cargo,
                   v.nome as visitante_nome, v.empresa,
                   u.nome as usuario_nome
            FROM acessos a
            LEFT JOIN funcionarios f ON a.funcionario_id = f.id
            LEFT JOIN visitantes v ON a.visitante_id = v.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE DATE(a.data_hora) BETWEEN ? AND ?
        ";
        
        $params = [$startDate, $endDate];
        
        if (!empty($type)) {
            $sql .= " AND a.tipo = ?";
            $params[] = $type;
        }
        
        if ($person_type === 'visitor') {
            $sql .= " AND a.visitante_id IS NOT NULL";
        } elseif ($person_type === 'employee') {
            $sql .= " AND a.funcionario_id IS NOT NULL";
        }
        
        $sql .= " ORDER BY a.data_hora DESC";
        
        $accesses = $this->db->fetchAll($sql, $params);
        
        include '../views/reports/access.php';
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $report_type = $_GET['report_type'] ?? 'visitors';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        if ($format === 'csv') {
            $this->exportCSV($report_type, $startDate, $endDate);
        }
    }
    
    private function exportCSV($reportType, $startDate, $endDate) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $reportType . '_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($reportType) {
            case 'visitors':
                fputcsv($output, ['ID', 'Nome', 'CPF', 'Empresa', 'Pessoa Visitada', 'Email', 'Telefone', 'Status', 'Data Cadastro']);
                
                $visitors = $this->db->fetchAll("SELECT * FROM visitantes WHERE data_cadastro BETWEEN ? AND ? ORDER BY data_cadastro DESC", [$startDate, $endDate]);
                
                foreach ($visitors as $visitor) {
                    fputcsv($output, [
                        $visitor['id'],
                        $visitor['nome'],
                        $visitor['cpf'],
                        $visitor['empresa'],
                        $visitor['pessoa_visitada'],
                        $visitor['email'],
                        $visitor['telefone'],
                        $visitor['status'],
                        date('d/m/Y H:i', strtotime($visitor['data_cadastro']))
                    ]);
                }
                break;
                
            case 'employees':
                fputcsv($output, ['ID', 'Nome', 'CPF', 'Cargo', 'Email', 'Telefone', 'Status', 'Data Admissão']);
                
                $employees = $this->db->fetchAll("SELECT * FROM funcionarios WHERE data_admissao <= ? ORDER BY nome ASC", [$endDate]);
                
                foreach ($employees as $employee) {
                    fputcsv($output, [
                        $employee['id'],
                        $employee['nome'],
                        $employee['cpf'],
                        $employee['cargo'],
                        $employee['email'],
                        $employee['telefone'],
                        $employee['ativo'] ? 'Ativo' : 'Inativo',
                        date('d/m/Y', strtotime($employee['data_admissao']))
                    ]);
                }
                break;
                
            case 'access':
                fputcsv($output, ['ID', 'Data/Hora', 'Tipo', 'Nome', 'Categoria', 'Empresa/Cargo', 'Registrado por']);
                
                $accesses = $this->db->fetchAll("
                    SELECT a.*, 
                           f.nome as funcionario_nome, f.cargo,
                           v.nome as visitante_nome, v.empresa,
                           u.nome as usuario_nome
                    FROM acessos a
                    LEFT JOIN funcionarios f ON a.funcionario_id = f.id
                    LEFT JOIN visitantes v ON a.visitante_id = v.id
                    LEFT JOIN usuarios u ON a.usuario_id = u.id
                    WHERE DATE(a.data_hora) BETWEEN ? AND ?
                    ORDER BY a.data_hora DESC
                ", [$startDate, $endDate]);
                
                foreach ($accesses as $access) {
                    fputcsv($output, [
                        $access['id'],
                        date('d/m/Y H:i:s', strtotime($access['data_hora'])),
                        ucfirst($access['tipo']),
                        $access['funcionario_nome'] ?? $access['visitante_nome'],
                        $access['funcionario_id'] ? 'Funcionário' : 'Visitante',
                        $access['cargo'] ?? $access['empresa'],
                        $access['usuario_nome']
                    ]);
                }
                break;
        }
        
        fclose($output);
        exit;
    }
}