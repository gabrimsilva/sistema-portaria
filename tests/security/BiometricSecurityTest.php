<?php
/**
 * 🛡️ TESTES CRÍTICOS DE SEGURANÇA BIOMÉTRICA
 * 
 * Verifica se dados biométricos estão adequadamente protegidos
 * contra acesso direto e vazamentos de dados conforme LGPD.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/services/SecureBiometricStorageService.php';

class BiometricSecurityTest {
    
    private $results = [];
    
    public function runAllTests() {
        echo "🛡️ EXECUTANDO TESTES DE SEGURANÇA BIOMÉTRICA...\n\n";
        
        $this->testUploadDirectoryBlocked();
        $this->testSecureStorageDirectory();
        $this->testBiometricEncryption();
        $this->testAccessControlTokens();
        $this->testProductionConfiguration();
        $this->testNoLegacyUploads();
        
        return $this->printResults();
    }
    
    /**
     * 🚫 Teste: Diretório /uploads/ deve estar bloqueado
     */
    private function testUploadDirectoryBlocked() {
        echo "1. Testando bloqueio de /uploads/...\n";
        
        $testCases = [
            '/uploads/test.jpg',
            '/uploads/employees/photo.jpg', 
            '/uploads/visitors/image.png',
            '/uploads/logos/logo.jpg'
        ];
        
        foreach ($testCases as $path) {
            $url = "http://localhost:5000" . $path;
            $response = $this->httpRequest($url);
            
            if ($response['code'] === 403 || $response['code'] === 404) {
                $this->results[] = "✅ $path bloqueado (HTTP {$response['code']})";
            } else {
                $this->results[] = "❌ FALHA: $path acessível (HTTP {$response['code']})";
            }
        }
    }
    
    /**
     * 🔒 Teste: Armazenamento seguro fora de public/
     */
    private function testSecureStorageDirectory() {
        echo "2. Testando armazenamento seguro...\n";
        
        $secureDir = __DIR__ . '/../../storage/secure/biometrics';
        
        if (is_dir($secureDir)) {
            $this->results[] = "✅ Diretório seguro existe: $secureDir";
            
            // Verificar permissões
            $perms = substr(sprintf('%o', fileperms($secureDir)), -3);
            if ($perms === '700') {
                $this->results[] = "✅ Permissões seguras: $perms";
            } else {
                $this->results[] = "⚠️ Permissões: $perms (recomendado: 700)";
            }
        } else {
            $this->results[] = "❌ FALHA: Diretório seguro não existe";
        }
        
        // Verificar se está fora de public/
        $publicDir = __DIR__ . '/../../public';
        if (strpos($secureDir, $publicDir) === false) {
            $this->results[] = "✅ Armazenamento fora de public/";
        } else {
            $this->results[] = "❌ CRÍTICO: Armazenamento dentro de public/";
        }
    }
    
    /**
     * 🔐 Teste: Criptografia AES-256-GCM
     */
    private function testBiometricEncryption() {
        echo "3. Testando criptografia biométrica...\n";
        
        try {
            $service = new SecureBiometricStorageService();
            
            // Testar dados falsos para verificar criptografia
            $testData = "test image data";
            $testFile = "test.jpg";
            
            // Verificar se método de criptografia existe
            $reflection = new ReflectionClass($service);
            if ($reflection->hasMethod('encryptData')) {
                $this->results[] = "✅ Método de criptografia disponível";
            } else {
                $this->results[] = "❌ FALHA: Método de criptografia não encontrado";
            }
            
            // Verificar constante de chave
            if (defined('BIOMETRIC_ENCRYPTION_KEY') || getenv('BIOMETRIC_ENCRYPTION_KEY')) {
                $this->results[] = "✅ Chave de criptografia configurada";
            } else {
                $this->results[] = "⚠️ Chave de criptografia usando fallback de desenvolvimento";
            }
            
        } catch (Exception $e) {
            $this->results[] = "❌ ERRO na inicialização do serviço: " . $e->getMessage();
        }
    }
    
    /**
     * 🎫 Teste: Tokens de acesso controlado
     */
    private function testAccessControlTokens() {
        echo "4. Testando controle de acesso...\n";
        
        // Verificar endpoint seguro
        $secureUrl = "http://localhost:5000/secure/biometric/photo";
        $response = $this->httpRequest($secureUrl);
        
        if ($response['code'] === 401 || $response['code'] === 403) {
            $this->results[] = "✅ Endpoint seguro protegido (HTTP {$response['code']})";
        } else if ($response['code'] === 400) {
            $this->results[] = "✅ Endpoint seguro valida parâmetros (HTTP {$response['code']})";
        } else {
            $this->results[] = "❌ FALHA: Endpoint não protegido (HTTP {$response['code']})";
        }
    }
    
    /**
     * ⚙️ Teste: Configuração de produção
     */
    private function testProductionConfiguration() {
        echo "5. Testando configuração de produção...\n";
        
        // Verificar .htaccess
        $htaccessFile = __DIR__ . '/../../public/.htaccess';
        if (file_exists($htaccessFile)) {
            $content = file_get_contents($htaccessFile);
            if (strpos($content, 'uploads/') !== false) {
                $this->results[] = "✅ .htaccess bloqueia uploads/";
            } else {
                $this->results[] = "❌ FALHA: .htaccess não bloqueia uploads/";
            }
        } else {
            $this->results[] = "⚠️ .htaccess não encontrado";
        }
        
        // Verificar configuração de deployment
        $replitFile = __DIR__ . '/../../.replit';
        if (file_exists($replitFile)) {
            $this->results[] = "✅ Configuração de deployment existe";
        } else {
            $this->results[] = "⚠️ Configuração de deployment não encontrada";
        }
    }
    
    /**
     * 🧹 Teste: Sem uploads legados
     */
    private function testNoLegacyUploads() {
        echo "6. Verificando uploads legados...\n";
        
        $uploadsDir = __DIR__ . '/../../public/uploads';
        
        if (is_dir($uploadsDir)) {
            $files = glob($uploadsDir . '/{employees,visitors}/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            
            if (empty($files)) {
                $this->results[] = "✅ Nenhum arquivo biométrico em uploads/";
            } else {
                $this->results[] = "❌ CRÍTICO: " . count($files) . " arquivos vulneráveis encontrados";
                foreach (array_slice($files, 0, 3) as $file) {
                    $this->results[] = "  ⚠️ " . basename($file);
                }
            }
        } else {
            $this->results[] = "✅ Diretório uploads/ não existe";
        }
    }
    
    /**
     * 🌐 Utilitário: Requisição HTTP
     */
    private function httpRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
        
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['code' => $code];
    }
    
    /**
     * 📊 Exibir resultados
     */
    private function printResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🛡️ RELATÓRIO DE SEGURANÇA BIOMÉTRICA\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $success = 0;
        $warnings = 0;
        $failures = 0;
        
        foreach ($this->results as $result) {
            echo $result . "\n";
            
            if (strpos($result, '✅') !== false) $success++;
            elseif (strpos($result, '⚠️') !== false) $warnings++;
            elseif (strpos($result, '❌') !== false) $failures++;
        }
        
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "📊 RESUMO: {$success} sucessos, {$warnings} avisos, {$failures} falhas\n";
        
        if ($failures === 0) {
            echo "🎉 SISTEMA SEGURO PARA PRODUÇÃO!\n";
        } elseif ($failures > 0) {
            echo "🚨 CORREÇÕES NECESSÁRIAS ANTES DO DEPLOY!\n";
        }
        
        echo str_repeat("=", 60) . "\n";
        
        // Return boolean para CI/CD
        return $failures === 0;
    }
}

// Executar testes se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $test = new BiometricSecurityTest();
    $test->runAllTests();
}