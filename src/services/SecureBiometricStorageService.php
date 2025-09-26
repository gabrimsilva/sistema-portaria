<?php

/**
 * Serviço de Armazenamento Seguro de Dados Biométricos
 * 
 * Este serviço garante que dados biométricos (fotos) sejam armazenados
 * de forma segura, criptografados e com controle de acesso adequado
 * conforme exigido pela LGPD para dados sensíveis.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/AuthorizationService.php';

class SecureBiometricStorageService {
    private $db;
    private $auditService;
    private $authService;
    
    // Diretório seguro fora do web root
    private $secureBasePath;
    
    // Chave de criptografia (deve vir de variável de ambiente em produção)
    private $encryptionKey;
    
    // Algoritmo de criptografia com autenticação
    private $cipher = 'AES-256-GCM';
    
    // Tipos de entidade permitidos
    private $allowedEntityTypes = ['employees', 'visitors', 'prestadores'];
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
        $this->authService = new AuthorizationService();
        
        // Definir caminho base correto
        $this->secureBasePath = dirname(dirname(__DIR__)) . '/storage/secure/biometrics';
        
        // Gerar/obter chave de criptografia
        $this->initializeEncryptionKey();
        
        // Garantir que diretórios existem
        $this->ensureSecureDirectories();
    }
    
    /**
     * Armazenar foto biométrica de forma segura
     */
    public function storeBiometricPhoto($entityType, $entityId, $fileData, $originalFilename = null) {
        $this->validateEntityType($entityType);
        
        if (!$this->authService->hasPermission('biometric.store')) {
            throw new Exception('Acesso negado para armazenar dados biométricos');
        }
        
        try {
            // Validar dados da imagem
            $this->validateImageData($fileData);
            
            // Gerar nome seguro do arquivo
            $secureFilename = $this->generateSecureFilename($entityType, $entityId);
            
            // Criptografar dados da imagem
            $encryptedData = $this->encryptImageData($fileData);
            
            // Caminho completo do arquivo
            $fullPath = $this->getSecureFilePath($entityType, $secureFilename);
            
            // Salvar arquivo criptografado
            if (!file_put_contents($fullPath, $encryptedData)) {
                throw new Exception('Erro ao salvar arquivo biométrico criptografado');
            }
            
            // Registrar no banco de dados
            $biometricId = $this->registerBiometricFile($entityType, $entityId, $secureFilename, $originalFilename);
            
            // Auditoria
            $this->auditService->log('biometric_store', $entityType, $entityId, null, [
                'biometric_id' => $biometricId,
                'file_size_bytes' => strlen($fileData),
                'storage_path' => 'SECURE_ENCRYPTED'
            ]);
            
            return $biometricId;
            
        } catch (Exception $e) {
            error_log("Erro ao armazenar dados biométricos: " . $e->getMessage());
            throw new Exception('Erro interno no armazenamento seguro');
        }
    }
    
    /**
     * Recuperar foto biométrica de forma segura
     */
    public function retrieveBiometricPhoto($biometricId, $entityType = null, $entityId = null) {
        if (!$this->authService->hasPermission('biometric.read')) {
            throw new Exception('Acesso negado para visualizar dados biométricos');
        }
        
        try {
            // Obter informações do arquivo do banco
            $fileInfo = $this->getBiometricFileInfo($biometricId);
            
            if (!$fileInfo) {
                throw new Exception('Arquivo biométrico não encontrado');
            }
            
            // Verificar autorização específica se fornecidos entityType/entityId
            if ($entityType && $entityId) {
                if ($fileInfo['entity_type'] !== $entityType || $fileInfo['entity_id'] != $entityId) {
                    throw new Exception('Acesso negado ao arquivo específico');
                }
            }
            
            // Verificar se arquivo existe no filesystem
            $fullPath = $this->getSecureFilePath($fileInfo['entity_type'], $fileInfo['secure_filename']);
            
            if (!file_exists($fullPath)) {
                throw new Exception('Arquivo físico não encontrado');
            }
            
            // Ler e descriptografar dados
            $encryptedData = file_get_contents($fullPath);
            $imageData = $this->decryptImageData($encryptedData);
            
            // Auditoria de acesso
            $this->auditService->log('biometric_access', $fileInfo['entity_type'], $fileInfo['entity_id'], null, [
                'biometric_id' => $biometricId,
                'accessed_by_user' => $_SESSION['user_id'] ?? null,
                'access_time' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'data' => $imageData,
                'mime_type' => $fileInfo['mime_type'] ?? 'image/jpeg',
                'original_filename' => $fileInfo['original_filename']
            ];
            
        } catch (Exception $e) {
            // Log de tentativa de acesso não autorizado
            $this->auditService->log('biometric_access_denied', $entityType ?? 'unknown', $entityId ?? 0, null, [
                'biometric_id' => $biometricId,
                'error' => 'ACCESS_DENIED',
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Deletar foto biométrica de forma segura
     */
    public function deleteBiometricPhoto($biometricId, $reason = 'Solicitação de exclusão') {
        if (!$this->authService->hasPermission('biometric.delete')) {
            throw new Exception('Acesso negado para deletar dados biométricos');
        }
        
        try {
            // Obter informações do arquivo
            $fileInfo = $this->getBiometricFileInfo($biometricId);
            
            if (!$fileInfo) {
                throw new Exception('Arquivo biométrico não encontrado');
            }
            
            // Caminho do arquivo
            $fullPath = $this->getSecureFilePath($fileInfo['entity_type'], $fileInfo['secure_filename']);
            
            // Deletar arquivo físico
            if (file_exists($fullPath)) {
                // Sobrescrever com dados aleatórios antes de deletar (secure deletion)
                $fileSize = filesize($fullPath);
                $randomData = random_bytes($fileSize);
                file_put_contents($fullPath, $randomData);
                unlink($fullPath);
            }
            
            // Marcar como deletado no banco
            $this->db->query(
                "UPDATE biometric_files SET deleted_at = CURRENT_TIMESTAMP, deletion_reason = ? WHERE id = ?",
                [$reason, $biometricId]
            );
            
            // Auditoria
            $this->auditService->log('biometric_delete', $fileInfo['entity_type'], $fileInfo['entity_id'], null, [
                'biometric_id' => $biometricId,
                'deletion_reason' => 'SECURE_DELETION',
                'deleted_by' => $_SESSION['user_id'] ?? null
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao deletar dados biométricos: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Migrar fotos existentes do diretório público para armazenamento seguro
     */
    public function migrateExistingPhotos() {
        if (!$this->authService->hasPermission('admin.system.manage')) {
            throw new Exception('Acesso negado para migração de dados');
        }
        
        $migrated = 0;
        $errors = 0;
        
        try {
            // Migrar funcionários
            $employees = $this->db->fetchAll("SELECT id, foto FROM funcionarios WHERE foto IS NOT NULL AND foto != ''");
            
            foreach ($employees as $employee) {
                try {
                    $oldPath = PUBLIC_PATH . '/' . $employee['foto'];
                    
                    if (file_exists($oldPath)) {
                        $imageData = file_get_contents($oldPath);
                        $biometricId = $this->storeBiometricPhoto('employees', $employee['id'], $imageData, basename($oldPath));
                        
                        // Atualizar referência no banco
                        $this->db->query(
                            "UPDATE funcionarios SET foto = ?, biometric_id = ? WHERE id = ?",
                            ['SECURE_BIOMETRIC:' . $biometricId, $biometricId, $employee['id']]
                        );
                        
                        // Remover arquivo antigo
                        unlink($oldPath);
                        $migrated++;
                    }
                } catch (Exception $e) {
                    error_log("Erro ao migrar funcionário {$employee['id']}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            // Migrar visitantes
            $visitors = $this->db->fetchAll("SELECT id, foto FROM visitantes_novo WHERE foto IS NOT NULL AND foto != ''");
            
            foreach ($visitors as $visitor) {
                try {
                    $oldPath = PUBLIC_PATH . '/' . $visitor['foto'];
                    
                    if (file_exists($oldPath)) {
                        $imageData = file_get_contents($oldPath);
                        $biometricId = $this->storeBiometricPhoto('visitors', $visitor['id'], $imageData, basename($oldPath));
                        
                        // Atualizar referência no banco
                        $this->db->query(
                            "UPDATE visitantes_novo SET foto = ?, biometric_id = ? WHERE id = ?",
                            ['SECURE_BIOMETRIC:' . $biometricId, $biometricId, $visitor['id']]
                        );
                        
                        // Remover arquivo antigo
                        unlink($oldPath);
                        $migrated++;
                    }
                } catch (Exception $e) {
                    error_log("Erro ao migrar visitante {$visitor['id']}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            // Auditoria da migração
            $this->auditService->log('biometric_migration', 'system', 0, null, [
                'migrated_count' => $migrated,
                'error_count' => $errors,
                'migration_completed' => date('Y-m-d H:i:s')
            ]);
            
            return ['migrated' => $migrated, 'errors' => $errors];
            
        } catch (Exception $e) {
            error_log("Erro na migração de dados biométricos: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Validar tipo de entidade
     */
    private function validateEntityType($entityType) {
        if (!in_array($entityType, $this->allowedEntityTypes)) {
            throw new Exception("Tipo de entidade não permitido: {$entityType}");
        }
    }
    
    /**
     * Validar dados da imagem
     */
    private function validateImageData($imageData) {
        if (empty($imageData)) {
            throw new Exception('Dados da imagem não podem estar vazios');
        }
        
        // Verificar se é uma imagem válida
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Verificar tamanho (max 5MB)
        if (strlen($imageData) > 5 * 1024 * 1024) {
            throw new Exception('Arquivo muito grande (máximo 5MB)');
        }
    }
    
    /**
     * Gerar nome seguro para arquivo
     */
    private function generateSecureFilename($entityType, $entityId) {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        return "{$entityType}_{$entityId}_{$timestamp}_{$random}.enc";
    }
    
    /**
     * Obter caminho completo do arquivo
     */
    private function getSecureFilePath($entityType, $filename) {
        return $this->secureBasePath . '/' . $entityType . '/' . $filename;
    }
    
    /**
     * Inicializar chave de criptografia
     */
    private function initializeEncryptionKey() {
        // OBRIGATÓRIO usar variável de ambiente em produção
        $this->encryptionKey = $_ENV['BIOMETRIC_ENCRYPTION_KEY'] ?? null;
        
        if (!$this->encryptionKey) {
            // Apenas para desenvolvimento - NUNCA em produção
            if (getenv('REPLIT_ENVIRONMENT') === 'development' || !getenv('REPLIT_ENVIRONMENT')) {
                $this->encryptionKey = 'dev-key-32-chars-NEVER-USE-PROD!!';
                error_log('AVISO: Usando chave de desenvolvimento para dados biométricos');
            } else {
                throw new Exception('BIOMETRIC_ENCRYPTION_KEY obrigatória em produção');
            }
        }
        
        if (strlen($this->encryptionKey) < 32) {
            throw new Exception('Chave de criptografia deve ter pelo menos 32 caracteres');
        }
    }
    
    /**
     * Garantir que diretórios seguros existem
     */
    private function ensureSecureDirectories() {
        foreach ($this->allowedEntityTypes as $entityType) {
            $dir = $this->secureBasePath . '/' . $entityType;
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true); // Apenas owner pode ler/escrever
            }
        }
    }
    
    /**
     * Criptografar dados da imagem com autenticação (AES-GCM)
     */
    private function encryptImageData($imageData) {
        $iv = random_bytes(12); // GCM recomenda 12 bytes para IV
        $tag = '';
        
        $encrypted = openssl_encrypt($imageData, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new Exception('Erro na criptografia dos dados');
        }
        
        // Concatenar IV + Tag + dados criptografados para integridade
        return $iv . $tag . $encrypted;
    }
    
    /**
     * Descriptografar dados da imagem com verificação de integridade (AES-GCM)
     */
    private function decryptImageData($encryptedData) {
        $ivLength = 12; // GCM usa 12 bytes para IV
        $tagLength = 16; // GCM tag tem 16 bytes
        
        if (strlen($encryptedData) < $ivLength + $tagLength) {
            throw new Exception('Dados criptografados inválidos');
        }
        
        $iv = substr($encryptedData, 0, $ivLength);
        $tag = substr($encryptedData, $ivLength, $tagLength);
        $encrypted = substr($encryptedData, $ivLength + $tagLength);
        
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new Exception('Erro na descriptografia ou dados foram adulterados');
        }
        
        return $decrypted;
    }
    
    /**
     * Registrar arquivo biométrico no banco
     */
    private function registerBiometricFile($entityType, $entityId, $secureFilename, $originalFilename) {
        $result = $this->db->fetch(
            "INSERT INTO biometric_files 
             (entity_type, entity_id, secure_filename, original_filename, created_at, created_by)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?) RETURNING id",
            [$entityType, $entityId, $secureFilename, $originalFilename, $_SESSION['user_id'] ?? null]
        );
        
        return $result['id'];
    }
    
    /**
     * Obter informações do arquivo biométrico
     */
    private function getBiometricFileInfo($biometricId) {
        return $this->db->fetch(
            "SELECT * FROM biometric_files WHERE id = ? AND deleted_at IS NULL",
            [$biometricId]
        );
    }
}