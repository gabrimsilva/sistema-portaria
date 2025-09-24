<?php

class CnpjValidator {
    
    /**
     * Valida CNPJ com dígitos verificadores
     */
    public static function isValid($cnpj) {
        // Remove formatação
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verifica se não são todos iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Calcula primeiro dígito verificador
        $soma = 0;
        $multiplicador = [5,4,3,2,9,8,7,6,5,4,3,2];
        
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador[$i];
        }
        
        $resto = $soma % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;
        
        if ($cnpj[12] != $dv1) {
            return false;
        }
        
        // Calcula segundo dígito verificador
        $soma = 0;
        $multiplicador = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador[$i];
        }
        
        $resto = $soma % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;
        
        return $cnpj[13] == $dv2;
    }
    
    /**
     * Formata CNPJ para exibição
     */
    public static function format($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    
    /**
     * Remove formatação do CNPJ
     */
    public static function clean($cnpj) {
        return preg_replace('/\D/', '', $cnpj);
    }
}