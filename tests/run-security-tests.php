<?php
/**
 * 🚀 EXECUTOR PRINCIPAL DE TESTES DE SEGURANÇA
 * 
 * Script principal para executar todos os testes de segurança biométrica
 * de forma automatizada para CI/CD e verificações manuais.
 */

// Verificar ambiente ANTES de incluir config
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

echo "🛡️ SISTEMA DE VERIFICAÇÃO DE SEGURANÇA BIOMÉTRICA\n";
echo "===================================================\n\n";

$allPassed = true;
$securityTestPassed = true;

try {
    // 1. Executar testes de segurança runtime
    echo "1️⃣ EXECUTANDO TESTES DE SEGURANÇA RUNTIME...\n";
    echo str_repeat("-", 50) . "\n";
    
    require_once __DIR__ . '/security/BiometricSecurityTest.php';
    $securityTest = new BiometricSecurityTest();
    $securityTestPassed = $securityTest->runAllTests();
    
    if (!$securityTestPassed) {
        $allPassed = false;
        echo "❌ Testes de segurança runtime falharam\n";
    }
    
    echo "\n\n";
    
    // 2. Executar scanner de código
    echo "2️⃣ EXECUTANDO SCANNER DE CÓDIGO...\n";
    echo str_repeat("-", 50) . "\n";
    
    require_once __DIR__ . '/security/CodeScannerGuard.php';
    $codeScanner = new CodeScannerGuard();
    $codeClean = $codeScanner->scanAll();
    
    if (!$codeClean) {
        $allPassed = false;
    }
    
    echo "\n\n";
    
    // 3. Verificações adicionais de configuração
    echo "3️⃣ VERIFICAÇÕES DE CONFIGURAÇÃO...\n";
    echo str_repeat("-", 50) . "\n";
    
    $configIssues = [];
    
    // Verificar se servidor está rodando
    if (!@file_get_contents('http://localhost:5000', false, stream_context_create(['http' => ['timeout' => 3]]))) {
        $configIssues[] = "❌ Servidor não está rodando na porta 5000";
    } else {
        echo "✅ Servidor está rodando\n";
    }
    
    // Verificar estrutura de diretórios
    $secureDir = BASE_PATH . '/storage/secure/biometrics';
    if (!is_dir($secureDir)) {
        $configIssues[] = "❌ Diretório seguro não existe: $secureDir";
    } else {
        echo "✅ Diretório seguro existe\n";
    }
    
    // Verificar configuração de database
    $dbConfigFile = BASE_PATH . '/config/database.php';
    if (!file_exists($dbConfigFile)) {
        $configIssues[] = "❌ Arquivo de configuração DB não existe";
    } else {
        echo "✅ Configuração de database existe\n";
    }
    
    if (!empty($configIssues)) {
        echo "\n⚠️ PROBLEMAS DE CONFIGURAÇÃO:\n";
        foreach ($configIssues as $issue) {
            echo "  $issue\n";
        }
        echo "\n";
    }
    
    // 4. Resultado final
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🏆 RESULTADO FINAL DOS TESTES DE SEGURANÇA\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($allPassed && empty($configIssues)) {
        echo "🎉 TODOS OS TESTES PASSARAM!\n";
        echo "✅ Sistema está seguro para produção\n";
        echo "🔒 Dados biométricos adequadamente protegidos\n";
        $exitCode = 0;
    } else {
        echo "🚨 ALGUNS TESTES FALHARAM!\n";
        echo "❌ Correções necessárias antes do deploy\n";
        echo "🔧 Revise os relatórios acima\n";
        $exitCode = 1;
    }
    
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "💥 ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $exitCode = 2;
}

// Para CI/CD
if (php_sapi_name() === 'cli') {
    exit($exitCode);
}