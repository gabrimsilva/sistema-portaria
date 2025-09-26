<?php
/**
 * 🔍 GUARDRAIL CI: Scanner de Código para Segurança Biométrica
 * 
 * Detecta automaticamente código que pode introduzir vulnerabilidades
 * de dados biométricos, prevenindo regressões de segurança.
 */

class CodeScannerGuard {
    
    private $violations = [];
    private $baseDir;
    
    public function __construct($baseDir = null) {
        $this->baseDir = $baseDir ?: dirname(__DIR__, 2);
    }
    
    public function scanAll() {
        echo "🔍 EXECUTANDO SCANNER DE SEGURANÇA...\n\n";
        
        $this->scanForUnsafeUploads();
        $this->scanForPublicWrites();
        $this->scanForHardcodedPaths();
        $this->scanForMissingValidation();
        $this->scanForLegacyMethods();
        
        $this->printReport();
        
        return empty($this->violations);
    }
    
    /**
     * 🚫 Detectar uploads inseguros para public/uploads
     */
    private function scanForUnsafeUploads() {
        echo "1. Escaneando uploads inseguros...\n";
        
        $patterns = [
            'public/uploads/',
            'UPLOAD_PATH.*employees',
            'UPLOAD_PATH.*visitors', 
            'uploads/employees',
            'uploads/visitors',
            'move_uploaded_file.*public'
        ];
        
        foreach ($patterns as $pattern) {
            $this->scanPattern($pattern, 'UPLOAD_INSEGURO', "Upload para diretório público: $pattern");
        }
    }
    
    /**
     * 📝 Detectar escritas diretas em public/
     */
    private function scanForPublicWrites() {
        echo "2. Escaneando escritas em public/...\n";
        
        $patterns = [
            'file_put_contents.*public/',
            'fwrite.*public/',
            'mkdir.*public/uploads',
            'copy.*public/uploads'
        ];
        
        foreach ($patterns as $pattern) {
            $this->scanPattern($pattern, 'ESCRITA_PUBLICA', "Escrita direta em public/: $pattern");
        }
    }
    
    /**
     * 🛤️ Detectar caminhos hardcoded vulneráveis
     */
    private function scanForHardcodedPaths() {
        echo "3. Escaneando caminhos hardcoded...\n";
        
        $patterns = [
            '[\'"]/uploads/employees',
            '[\'"]/uploads/visitors',
            'return.*uploads/.*jpg',
            'src=.*uploads/',
            'href=.*uploads/'
        ];
        
        foreach ($patterns as $pattern) {
            $this->scanPattern($pattern, 'CAMINHO_HARDCODED', "Caminho vulnerável hardcoded: $pattern");
        }
    }
    
    /**
     * ✅ Detectar falta de validação
     */
    private function scanForMissingValidation() {
        echo "4. Escaneando validação ausente...\n";
        
        $files = $this->getPhpFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Se há upload de arquivo mas não há validação MIME
            if (preg_match('/\$_FILES.*error.*UPLOAD_ERR_OK/', $content) &&
                !preg_match('/finfo_file|mime_content_type|getimagesize/', $content)) {
                
                $this->addViolation($file, 'VALIDACAO_AUSENTE', 
                    'Upload de arquivo sem validação MIME');
            }
            
            // Se há SecureBiometricStorageService mas não há auditoria
            if (preg_match('/SecureBiometricStorageService/', $content) &&
                !preg_match('/AuditService.*log/', $content)) {
                
                $this->addViolation($file, 'AUDITORIA_AUSENTE', 
                    'Uso de serviço biométrico sem auditoria');
            }
        }
    }
    
    /**
     * 🗑️ Detectar métodos legados perigosos
     */
    private function scanForLegacyMethods() {
        echo "5. Escaneando métodos legados...\n";
        
        $patterns = [
            'function uploadPhoto\(',
            'function saveBase64Photo\(',
            'function handleFileUpload\(',
            'uploadPhoto\(\$_FILES'
        ];
        
        foreach ($patterns as $pattern) {
            $this->scanPattern($pattern, 'METODO_LEGADO', "Método legado inseguro: $pattern");
        }
    }
    
    /**
     * 🔍 Scanner genérico de padrões
     */
    private function scanPattern($pattern, $type, $description) {
        $files = $this->getPhpFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNum => $line) {
                if (preg_match("/$pattern/i", $line)) {
                    $this->addViolation($file, $type, $description, $lineNum + 1, trim($line));
                }
            }
        }
    }
    
    /**
     * 📁 Obter arquivos PHP para análise
     */
    private function getPhpFiles() {
        $files = [];
        $directories = [
            $this->baseDir . '/src',
            $this->baseDir . '/views',
            $this->baseDir . '/public'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                );
                
                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * ⚠️ Adicionar violação encontrada
     */
    private function addViolation($file, $type, $description, $line = null, $code = null) {
        $this->violations[] = [
            'file' => str_replace($this->baseDir . '/', '', $file),
            'type' => $type,
            'description' => $description,
            'line' => $line,
            'code' => $code
        ];
    }
    
    /**
     * 📊 Imprimir relatório
     */
    private function printReport() {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🔍 RELATÓRIO DO SCANNER DE SEGURANÇA\n";
        echo str_repeat("=", 70) . "\n\n";
        
        if (empty($this->violations)) {
            echo "🎉 NENHUMA VIOLAÇÃO DE SEGURANÇA ENCONTRADA!\n";
            echo "✅ Código está seguro para dados biométricos.\n\n";
            return;
        }
        
        $grouped = [];
        foreach ($this->violations as $violation) {
            $grouped[$violation['type']][] = $violation;
        }
        
        foreach ($grouped as $type => $violations) {
            echo "🚨 $type (" . count($violations) . " ocorrências):\n";
            echo str_repeat("-", 50) . "\n";
            
            foreach ($violations as $violation) {
                echo "📁 {$violation['file']}";
                if ($violation['line']) {
                    echo " (linha {$violation['line']})";
                }
                echo "\n";
                echo "   📝 {$violation['description']}\n";
                
                if ($violation['code']) {
                    echo "   💻 " . htmlspecialchars($violation['code']) . "\n";
                }
                echo "\n";
            }
        }
        
        echo str_repeat("=", 70) . "\n";
        echo "🚨 TOTAL: " . count($this->violations) . " violações encontradas\n";
        echo "❌ CORREÇÕES NECESSÁRIAS ANTES DO COMMIT!\n";
        echo str_repeat("=", 70) . "\n";
    }
}

// Executar scanner se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $scanner = new CodeScannerGuard();
    $clean = $scanner->scanAll();
    
    exit($clean ? 0 : 1); // Exit code para CI/CD
}