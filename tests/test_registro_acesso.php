<?php

/**
 * Testes básicos para o sistema de registro de acesso
 * Este arquivo testa os endpoints implementados na Etapa 2
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/RegistroAcessoController.php';
require_once __DIR__ . '/../src/services/AuditService.php';
require_once __DIR__ . '/../src/services/AuthorizationService.php';

class RegistroAcessoTest {
    private $controller;
    private $db;
    private $testData = [];
    
    public function __construct() {
        $this->controller = new RegistroAcessoController();
        $this->db = new Database();
        
        // Simular sessão de usuário administrador para testes
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Admin Teste';
        $_SESSION['user_profile'] = 'administrador';
    }
    
    /**
     * Executa todos os testes
     */
    public function runAllTests() {
        echo "<h1>Testes do Sistema de Registro de Acesso - Etapa 2</h1>\n";
        echo "<hr>\n";
        
        try {
            // Limpar dados de teste anteriores
            $this->cleanup();
            
            // Executar testes
            $this->testCheckIn();
            $this->testDuplicityValidation();
            $this->testCheckOut();
            $this->testEdit();
            $this->testInvalidExit();
            $this->testAlerts();
            $this->testAuthorization();
            $this->testAudit();
            
            echo "<h2>✅ TODOS OS TESTES PASSARAM!</h2>\n";
            
        } catch (Exception $e) {
            echo "<h2>❌ TESTE FALHOU: " . $e->getMessage() . "</h2>\n";
            echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
        } finally {
            // Limpar dados de teste
            $this->cleanup();
        }
    }
    
    /**
     * Teste 1: Check-in básico
     */
    private function testCheckIn() {
        echo "<h3>1. Testando Check-in (POST /entradas)</h3>\n";
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'tipo' => 'visitante',
            'nome' => 'João Teste',
            'cpf' => '123.456.789-00',
            'empresa' => 'Empresa Teste',
            'placa_veiculo' => 'ABC-1234',
            'funcionario_responsavel' => 'Maria Silva'
        ];
        
        // Simular CSRF token
        $_POST['csrf_token'] = CSRFProtection::generateToken();
        
        ob_start();
        $this->controller->checkIn();
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if (!$data['success']) {
            throw new Exception("Check-in falhou: " . $data['message']);
        }
        
        $this->testData['checkin_id'] = $data['data']['id'];
        echo "✅ Check-in realizado com sucesso - ID: " . $this->testData['checkin_id'] . "\n";
    }
    
    /**
     * Teste 2: Validação de duplicidade
     */
    private function testDuplicityValidation() {
        echo "<h3>2. Testando Validação de Duplicidade</h3>\n";
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'tipo' => 'prestador_servico',
            'nome' => 'Carlos Teste',
            'cpf' => '123.456.789-00', // Mesmo CPF
            'empresa' => 'Prestadora XYZ'
        ];
        
        $_POST['csrf_token'] = CSRFProtection::generateToken();
        
        ob_start();
        $this->controller->checkIn();
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if ($data['success']) {
            throw new Exception("Validação de duplicidade falhou - permitiu entrada duplicada");
        }
        
        echo "✅ Duplicidade detectada corretamente: " . $data['message'] . "\n";
    }
    
    /**
     * Teste 3: Check-out
     */
    private function testCheckOut() {
        echo "<h3>3. Testando Check-out (POST /saidas/:id)</h3>\n";
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'saida_at' => date('Y-m-d H:i:s', strtotime('+2 hours'))
        ];
        $_POST['csrf_token'] = CSRFProtection::generateToken();
        
        ob_start();
        $this->controller->checkOut($this->testData['checkin_id']);
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if (!$data['success']) {
            throw new Exception("Check-out falhou: " . $data['message']);
        }
        
        echo "✅ Check-out realizado com sucesso\n";
    }
    
    /**
     * Teste 4: Edição de registro
     */
    private function testEdit() {
        echo "<h3>4. Testando Edição (PUT /registros/:id)</h3>\n";
        
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        $editData = [
            'observacao' => 'Observação de teste editada',
            'empresa' => 'Empresa Teste Editada'
        ];
        
        // Simular input PUT
        $inputBackup = 'php://input';
        file_put_contents('php://temp', json_encode($editData));
        
        ob_start();
        $this->controller->edit($this->testData['checkin_id']);
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if (!$data['success']) {
            throw new Exception("Edição falhou: " . $data['message']);
        }
        
        echo "✅ Edição realizada com sucesso\n";
    }
    
    /**
     * Teste 5: Validação de saída inválida
     */
    private function testInvalidExit() {
        echo "<h3>5. Testando Validação de Saída Inválida</h3>\n";
        
        // Criar novo check-in
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'tipo' => 'prestador_servico',
            'nome' => 'Pedro Teste',
            'cpf' => '987.654.321-00',
            'empresa' => 'Prestadora ABC'
        ];
        $_POST['csrf_token'] = CSRFProtection::generateToken();
        
        ob_start();
        $this->controller->checkIn();
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        $newId = $data['data']['id'];
        
        // Tentar check-out com horário anterior
        $_POST = [
            'saida_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ];
        $_POST['csrf_token'] = CSRFProtection::generateToken();
        
        ob_start();
        $this->controller->checkOut($newId);
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if ($data['success']) {
            throw new Exception("Validação de horário falhou - permitiu saída anterior à entrada");
        }
        
        // Limpar registro de teste
        $this->db->query("DELETE FROM registro_acesso WHERE id = ?", [$newId]);
        
        echo "✅ Validação de horário funcionando corretamente: " . $data['message'] . "\n";
    }
    
    /**
     * Teste 6: Sistema de alertas >12h
     */
    private function testAlerts() {
        echo "<h3>6. Testando Sistema de Alertas (>12h)</h3>\n";
        
        // Criar registro antigo (>12h)
        $this->db->query(
            "INSERT INTO registro_acesso (tipo, nome, cpf, entrada_at, created_by) 
             VALUES (?, ?, ?, ?, ?)",
            [
                'visitante',
                'Visitante Antigo',
                '11111111111',
                date('Y-m-d H:i:s', strtotime('-15 hours')),
                1
            ]
        );
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        ob_start();
        $this->controller->alertas();
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if (!$data['success'] || $data['count'] == 0) {
            throw new Exception("Sistema de alertas não detectou registros >12h");
        }
        
        echo "✅ Sistema de alertas funcionando - " . $data['count'] . " alerta(s) detectado(s)\n";
    }
    
    /**
     * Teste 7: Sistema de autorização
     */
    private function testAuthorization() {
        echo "<h3>7. Testando Sistema de Autorização</h3>\n";
        
        $authService = new AuthorizationService();
        
        // Testar permissões de administrador
        if (!$authService->hasPermission('registro_acesso.create')) {
            throw new Exception("Permissão de administrador não funcionando");
        }
        
        // Testar campos editáveis
        $editableFields = $authService->getUserEditableFields();
        if (!in_array('*', $editableFields)) {
            throw new Exception("Campos editáveis de administrador incorretos");
        }
        
        echo "✅ Sistema de autorização funcionando corretamente\n";
    }
    
    /**
     * Teste 8: Sistema de auditoria
     */
    private function testAudit() {
        echo "<h3>8. Testando Sistema de Auditoria</h3>\n";
        
        // Verificar se logs foram criados
        $logs = $this->db->fetchAll(
            "SELECT * FROM audit_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5",
            [$_SESSION['user_id']]
        );
        
        if (empty($logs)) {
            throw new Exception("Nenhum log de auditoria foi criado");
        }
        
        $expectedActions = ['CREATE', 'CHECKOUT', 'UPDATE'];
        $foundActions = array_column($logs, 'acao');
        
        foreach ($expectedActions as $action) {
            if (!in_array($action, $foundActions)) {
                throw new Exception("Ação de auditoria '$action' não encontrada");
            }
        }
        
        echo "✅ Sistema de auditoria funcionando - " . count($logs) . " log(s) registrado(s)\n";
    }
    
    /**
     * Limpar dados de teste
     */
    private function cleanup() {
        try {
            $this->db->query("DELETE FROM registro_acesso WHERE nome LIKE '%Teste%' OR nome LIKE '%Antigo%'");
            $this->db->query("DELETE FROM audit_log WHERE user_id = ? AND timestamp > (CURRENT_TIMESTAMP - INTERVAL '1 hour')", [1]);
        } catch (Exception $e) {
            // Ignorar erros de limpeza
        }
    }
}

// Executar testes se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'test_registro_acesso.php') {
    $test = new RegistroAcessoTest();
    $test->runAllTests();
}