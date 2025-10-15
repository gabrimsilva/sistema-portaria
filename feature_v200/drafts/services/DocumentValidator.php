<?php
// ================================================
// SERVICE: DOCUMENT VALIDATOR
// Versão: 2.0.0
// Objetivo: Validar diferentes tipos de documentos
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para src/ sem aprovação!

class DocumentValidator {
    
    /**
     * Valida documento conforme tipo
     * 
     * @param string $docType Tipo do documento (CPF, RG, PASSAPORTE, etc)
     * @param string $docNumber Número do documento
     * @param string $country Código do país (ISO-3166 alpha-2)
     * @return array ['isValid' => bool, 'message' => string, 'normalized' => string]
     */
    public static function validate($docType, $docNumber, $country = 'BR') {
        switch ($docType) {
            case 'CPF':
                return self::validateCPF($docNumber);
            
            case 'RG':
                return self::validateRG($docNumber);
            
            case 'CNH':
                return self::validateCNH($docNumber);
            
            case 'PASSAPORTE':
            case 'PASSPORT':
                return self::validatePassport($docNumber, $country);
            
            case 'RNE':
                return self::validateRNE($docNumber);
            
            case 'DNI':
                return self::validateDNI($docNumber, $country);
            
            case 'CI':
                return self::validateCI($docNumber, $country);
            
            case 'OUTRO':
                return self::validateGeneric($docNumber);
            
            default:
                return [
                    'isValid' => false,
                    'message' => 'Tipo de documento não suportado',
                    'normalized' => ''
                ];
        }
    }
    
    /**
     * Valida CPF brasileiro
     */
    private static function validateCPF($cpf) {
        // Usar CpfValidator existente
        require_once __DIR__ . '/../../src/services/CpfValidator.php';
        return CpfValidator::validateAndNormalize($cpf);
    }
    
    /**
     * Valida RG brasileiro
     */
    private static function validateRG($rg) {
        // Remover caracteres não numéricos
        $rg = preg_replace('/[^0-9X]/', '', strtoupper($rg));
        
        if (empty($rg)) {
            return [
                'isValid' => false,
                'message' => 'RG não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        // RG pode ter 7-9 dígitos + opcionalmente X
        if (strlen($rg) < 7 || strlen($rg) > 10) {
            return [
                'isValid' => false,
                'message' => 'RG deve ter entre 7 e 10 caracteres',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'RG válido',
            'normalized' => $rg
        ];
    }
    
    /**
     * Valida CNH (Carteira Nacional de Habilitação)
     */
    private static function validateCNH($cnh) {
        // Remover caracteres não numéricos
        $cnh = preg_replace('/\D/', '', $cnh);
        
        if (strlen($cnh) !== 11) {
            return [
                'isValid' => false,
                'message' => 'CNH deve ter 11 dígitos',
                'normalized' => ''
            ];
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1+$/', $cnh)) {
            return [
                'isValid' => false,
                'message' => 'CNH inválida',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'CNH válida',
            'normalized' => $cnh
        ];
    }
    
    /**
     * Valida Passaporte
     */
    private static function validatePassport($passport, $country) {
        // Remover espaços e converter para maiúsculas
        $passport = strtoupper(trim($passport));
        
        if (empty($passport)) {
            return [
                'isValid' => false,
                'message' => 'Número de passaporte não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        // Passaporte geralmente tem 6-9 caracteres alfanuméricos
        if (!preg_match('/^[A-Z0-9]{6,9}$/', $passport)) {
            return [
                'isValid' => false,
                'message' => 'Passaporte deve conter 6-9 caracteres alfanuméricos',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'Passaporte válido',
            'normalized' => $passport
        ];
    }
    
    /**
     * Valida RNE (Registro Nacional de Estrangeiro)
     */
    private static function validateRNE($rne) {
        // Remover caracteres não alfanuméricos
        $rne = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rne));
        
        if (empty($rne)) {
            return [
                'isValid' => false,
                'message' => 'RNE não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        // RNE pode ter formato variado, validação básica
        if (strlen($rne) < 5 || strlen($rne) > 15) {
            return [
                'isValid' => false,
                'message' => 'RNE deve ter entre 5 e 15 caracteres',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'RNE válido',
            'normalized' => $rne
        ];
    }
    
    /**
     * Valida DNI (Documento Nacional de Identidad - Argentina, Espanha)
     */
    private static function validateDNI($dni, $country) {
        $dni = preg_replace('/[^A-Z0-9]/', '', strtoupper($dni));
        
        if (empty($dni)) {
            return [
                'isValid' => false,
                'message' => 'DNI não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        if ($country === 'AR') {
            // DNI Argentina: 7-8 dígitos
            if (!preg_match('/^\d{7,8}$/', $dni)) {
                return [
                    'isValid' => false,
                    'message' => 'DNI argentino deve ter 7-8 dígitos',
                    'normalized' => ''
                ];
            }
        } elseif ($country === 'ES') {
            // DNI Espanha: 8 dígitos + letra
            if (!preg_match('/^\d{8}[A-Z]$/', $dni)) {
                return [
                    'isValid' => false,
                    'message' => 'DNI espanhol deve ter 8 dígitos seguidos de uma letra',
                    'normalized' => ''
                ];
            }
        }
        
        return [
            'isValid' => true,
            'message' => 'DNI válido',
            'normalized' => $dni
        ];
    }
    
    /**
     * Valida CI (Cédula de Identidad - América Latina)
     */
    private static function validateCI($ci, $country) {
        $ci = preg_replace('/[^A-Z0-9]/', '', strtoupper($ci));
        
        if (empty($ci)) {
            return [
                'isValid' => false,
                'message' => 'CI não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        // Validação genérica para CI de diferentes países
        if (strlen($ci) < 5 || strlen($ci) > 12) {
            return [
                'isValid' => false,
                'message' => 'CI deve ter entre 5 e 12 caracteres',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'CI válido',
            'normalized' => $ci
        ];
    }
    
    /**
     * Validação genérica para documentos não específicos
     */
    private static function validateGeneric($docNumber) {
        $docNumber = trim($docNumber);
        
        if (empty($docNumber)) {
            return [
                'isValid' => false,
                'message' => 'Número do documento não pode ser vazio',
                'normalized' => ''
            ];
        }
        
        if (strlen($docNumber) < 3) {
            return [
                'isValid' => false,
                'message' => 'Número do documento muito curto',
                'normalized' => ''
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'Documento aceito',
            'normalized' => strtoupper($docNumber)
        ];
    }
    
    /**
     * Formata documento para exibição
     */
    public static function format($docType, $docNumber) {
        switch ($docType) {
            case 'CPF':
                // 000.000.000-00
                if (strlen($docNumber) === 11) {
                    return substr($docNumber, 0, 3) . '.' . 
                           substr($docNumber, 3, 3) . '.' . 
                           substr($docNumber, 6, 3) . '-' . 
                           substr($docNumber, 9, 2);
                }
                break;
            
            case 'CNH':
                // 00000000000
                return $docNumber;
            
            case 'PASSAPORTE':
            case 'PASSPORT':
                // Formato padrão com espaços a cada 3 caracteres
                return strtoupper(chunk_split($docNumber, 3, ' '));
        }
        
        return strtoupper($docNumber);
    }
}
