<?php

class AccessController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function index() {
        $action = $_GET['action'] ?? 'register';
        
        switch ($action) {
            case 'register':
                $this->register();
                break;
            case 'save':
                $this->save();
                break;
            case 'history':
                $this->history();
                break;
            case 'scan':
                $this->scanQR();
                break;
            default:
                $this->register();
                break;
        }
    }
    
    public function register() {
        // Pre-selected visitor or employee
        $visitor_id = $_GET['visitor_id'] ?? null;
        $employee_id = $_GET['employee_id'] ?? null;
        
        $selectedVisitor = null;
        $selectedEmployee = null;
        
        if ($visitor_id) {
            $selectedVisitor = $this->db->fetch("SELECT * FROM visitantes WHERE id = ?", [$visitor_id]);
        }
        
        if ($employee_id) {
            $selectedEmployee = $this->db->fetch("SELECT * FROM funcionarios WHERE id = ? AND ativo = TRUE", [$employee_id]);
        }
        
        // Get active visitors and employees for dropdowns
        $activeVisitors = $this->db->fetchAll("SELECT * FROM visitantes WHERE status = 'ativo' ORDER BY nome ASC");
        $activeEmployees = $this->db->fetchAll("SELECT * FROM funcionarios WHERE ativo = TRUE ORDER BY nome ASC");
        
        include '../views/access/register.php';
    }
    
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::verifyRequest();
            try {
                $tipo = $_POST['tipo'] ?? '';
                $visitor_id = $_POST['visitor_id'] ?? null;
                $employee_id = $_POST['employee_id'] ?? null;
                $observacoes = $_POST['observacoes'] ?? '';
                
                if (empty($tipo) || ($visitor_id === null && $employee_id === null)) {
                    throw new Exception("Dados obrigatórios não preenchidos");
                }
                
                // Check if it's a valid combination
                if ($visitor_id !== null && $employee_id !== null) {
                    throw new Exception("Selecione apenas visitante OU funcionário");
                }
                
                $visitor_id = !empty($visitor_id) ? $visitor_id : null;
                $employee_id = !empty($employee_id) ? $employee_id : null;
                
                // Register access
                $this->db->query("
                    INSERT INTO acessos (tipo, funcionario_id, visitante_id, usuario_id, observacoes) 
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $tipo, $employee_id, $visitor_id, $_SESSION['user_id'], $observacoes
                ]);
                
                // Update visitor status if applicable
                if ($visitor_id) {
                    if ($tipo === 'saida') {
                        $this->db->query("UPDATE visitantes SET status = 'saiu' WHERE id = ?", [$visitor_id]);
                    } elseif ($tipo === 'entrada') {
                        $this->db->query("UPDATE visitantes SET status = 'ativo' WHERE id = ?", [$visitor_id]);
                    }
                }
                
                $personName = '';
                if ($visitor_id) {
                    $visitor = $this->db->fetch("SELECT nome FROM visitantes WHERE id = ?", [$visitor_id]);
                    $personName = $visitor['nome'];
                } else {
                    $employee = $this->db->fetch("SELECT nome FROM funcionarios WHERE id = ?", [$employee_id]);
                    $personName = $employee['nome'];
                }
                
                header("Location: /access?success=" . urlencode($tipo . " registrada para " . $personName));
                exit;
                
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->register();
            }
        }
    }
    
    public function history() {
        $date = $_GET['date'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? '';
        $person_type = $_GET['person_type'] ?? '';
        
        $sql = "
            SELECT a.*, 
                   f.nome as funcionario_nome, f.foto as funcionario_foto,
                   v.nome as visitante_nome, v.foto as visitante_foto, v.empresa,
                   u.nome as usuario_nome
            FROM acessos a
            LEFT JOIN funcionarios f ON a.funcionario_id = f.id
            LEFT JOIN visitantes v ON a.visitante_id = v.id
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE DATE(a.data_hora) = ?
        ";
        
        $params = [$date];
        
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
        
        $accessLogs = $this->db->fetchAll($sql, $params);
        
        // Get statistics for the day
        $stats = [
            'total_entries' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'entrada'", [$date])['count'] ?? 0,
            'total_exits' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'saida'", [$date])['count'] ?? 0,
            'visitor_entries' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'entrada' AND visitante_id IS NOT NULL", [$date])['count'] ?? 0,
            'employee_entries' => $this->db->fetch("SELECT COUNT(*) as count FROM acessos WHERE DATE(data_hora) = ? AND tipo = 'entrada' AND funcionario_id IS NOT NULL", [$date])['count'] ?? 0
        ];
        
        include '../views/access/history.php';
    }
    
    public function scanQR() {
        include '../views/access/scan.php';
    }
}