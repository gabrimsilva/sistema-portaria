<?php
// ================================================
// SERVICE: CPF VALIDATOR
// Versão: 2.0.0
// Objetivo: Validar CPF brasileiro
// ================================================

class CpfValidator {
    
    /**
     * Valida e normaliza CPF
     * 
     * @param string $cpf CPF a ser validado
     * @return array ['isValid' => bool, 'message' => string, 'normalized' => string]
     */
    public static function validateAndNormalize($cpf) {
        $cpfClean = preg_replace('/[^\d]/', '', $cpf);
        
        if (strlen($cpfClean) !== 11) {
            return [
                'isValid' => false,
                'message' => 'CPF deve ter 11 dígitos',
                'normalized' => ''
            ];
        }
        
        if (preg_match('/^(\d)\1+$/', $cpfClean)) {
            return [
                'isValid' => false,
                'message' => 'CPF inválido',
                'normalized' => ''
            ];
        }
        
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpfClean[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        
        if (intval($cpfClean[9]) !== $digito1) {
            return [
                'isValid' => false,
                'message' => 'CPF inválido',
                'normalized' => ''
            ];
        }
        
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpfClean[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;
        
        if (intval($cpfClean[10]) !== $digito2) {
            return [
                'isValid' => false,
                'message' => 'CPF inválido',
                'normalized' => ''
            ];
        }
        
        $normalized = substr($cpfClean, 0, 3) . '.' . 
                      substr($cpfClean, 3, 3) . '.' . 
                      substr($cpfClean, 6, 3) . '-' . 
                      substr($cpfClean, 9, 2);
        
        return [
            'isValid' => true,
            'message' => 'CPF válido',
            'normalized' => $normalized
        ];
    }
    
    /**
     * Valida CPF (retorna boolean)
     */
    public static function isValid($cpf) {
        $result = self::validateAndNormalize($cpf);
        return $result['isValid'];
    }
    
    /**
     * Normaliza CPF (adiciona formatação)
     */
    public static function normalize($cpf) {
        $result = self::validateAndNormalize($cpf);
        return $result['normalized'] ?: $cpf;
    }
    
    /**
     * Remove formatação do CPF
     */
    public static function clean($cpf) {
        return preg_replace('/[^\d]/', '', $cpf);
    }
}
