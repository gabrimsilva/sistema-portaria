<?php

class CpfValidator 
{
    /**
     * Valida CPF - verifica dígitos verificadores e rejeita sequências repetidas
     * Baseado no algoritmo oficial da Receita Federal
     */
    public static function isValidCPF(string $cpf): bool 
    {
        // Remove caracteres não numéricos
        $digits = preg_replace('/\D/', '', $cpf);
        
        // Deve ter exatamente 11 dígitos
        if (strlen($digits) !== 11) {
            return false;
        }
        
        // Rejeita sequências repetidas (111.111.111-11, 222.222.222-22, etc.)
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }
        
        // Valida primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($digits[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $checkDigit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if (intval($digits[9]) !== $checkDigit1) {
            return false;
        }
        
        // Valida segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($digits[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $checkDigit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return intval($digits[10]) === $checkDigit2;
    }
    
    /**
     * Normaliza CPF removendo caracteres não numéricos
     */
    public static function normalize(string $cpf): string 
    {
        return preg_replace('/\D/', '', $cpf);
    }
    
    /**
     * Formata CPF para exibição com máscara 000.000.000-00
     */
    public static function format(string $cpf): string 
    {
        $digits = self::normalize($cpf);
        
        if (strlen($digits) !== 11) {
            return $cpf; // Retorna original se inválido
        }
        
        return substr($digits, 0, 3) . '.' . 
               substr($digits, 3, 3) . '.' . 
               substr($digits, 6, 3) . '-' . 
               substr($digits, 9, 2);
    }
    
    /**
     * Valida e normaliza CPF em uma operação
     * Retorna array com resultado da validação e CPF normalizado
     */
    public static function validateAndNormalize(string $cpf): array 
    {
        $normalized = self::normalize($cpf);
        $isValid = self::isValidCPF($cpf);
        
        return [
            'isValid' => $isValid,
            'normalized' => $normalized,
            'formatted' => $isValid ? self::format($cpf) : $cpf,
            'message' => $isValid ? '' : 'CPF inválido - verifique os dígitos verificadores'
        ];
    }
}