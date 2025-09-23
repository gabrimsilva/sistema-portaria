<?php

class DateTimeValidator 
{
    /**
     * Valida se uma data/hora não é futura (com tolerância de 1 hora)
     */
    public static function isNotFuture(string $datetime, int $toleranceHours = 1): bool 
    {
        if (empty($datetime)) {
            return true; // Data vazia é válida (será preenchida automaticamente)
        }
        
        $inputTime = strtotime($datetime);
        if ($inputTime === false) {
            return false; // Data inválida
        }
        
        $maxAllowedTime = time() + ($toleranceHours * 3600); // Tolerância de X horas
        return $inputTime <= $maxAllowedTime;
    }
    
    /**
     * Valida se uma data/hora não é muito antiga (máximo 30 dias atrás)
     */
    public static function isNotTooOld(string $datetime, int $maxDaysOld = 30): bool 
    {
        if (empty($datetime)) {
            return true; // Data vazia é válida
        }
        
        $inputTime = strtotime($datetime);
        if ($inputTime === false) {
            return false; // Data inválida
        }
        
        $minAllowedTime = time() - ($maxDaysOld * 24 * 3600); // X dias atrás
        return $inputTime >= $minAllowedTime;
    }
    
    /**
     * Valida se uma data de saída é posterior à entrada
     */
    public static function isExitAfterEntry(?string $entrada, ?string $saida): bool 
    {
        if (empty($entrada) || empty($saida)) {
            return true; // Se alguma data está vazia, não há conflito
        }
        
        $entradaTime = strtotime($entrada);
        $saidaTime = strtotime($saida);
        
        if ($entradaTime === false || $saidaTime === false) {
            return false; // Datas inválidas
        }
        
        return $saidaTime >= $entradaTime;
    }
    
    /**
     * Valida formato de data/hora
     */
    public static function isValidFormat(string $datetime): bool 
    {
        if (empty($datetime)) {
            return true; // Data vazia é válida
        }
        
        // Tentar vários formatos comuns
        $formats = [
            'Y-m-d H:i:s',      // 2023-12-25 14:30:00
            'Y-m-d H:i',        // 2023-12-25 14:30
            'Y-m-d\TH:i:s',     // 2023-12-25T14:30:00 (HTML datetime-local)
            'Y-m-d\TH:i',       // 2023-12-25T14:30 (HTML datetime-local)
            'd/m/Y H:i:s',      // 25/12/2023 14:30:00
            'd/m/Y H:i'         // 25/12/2023 14:30
        ];
        
        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $datetime);
            if ($parsed && $parsed->format($format) === $datetime) {
                return true;
            }
        }
        
        // Fallback: tentar strtotime
        return strtotime($datetime) !== false;
    }
    
    /**
     * Normaliza data/hora para formato padrão do banco (Y-m-d H:i:s)
     */
    public static function normalize(string $datetime): string 
    {
        if (empty($datetime)) {
            return '';
        }
        
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime; // Retorna original se inválido
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Validação completa de data/hora de entrada
     */
    public static function validateEntryDateTime(?string $datetime): array 
    {
        if (empty($datetime)) {
            return [
                'isValid' => true,
                'normalized' => date('Y-m-d H:i:s'), // Hora atual se vazio
                'message' => ''
            ];
        }
        
        // Validar formato
        if (!self::isValidFormat($datetime)) {
            return [
                'isValid' => false,
                'normalized' => $datetime,
                'message' => 'Formato de data/hora inválido'
            ];
        }
        
        // Validar se não é futura
        if (!self::isNotFuture($datetime, 1)) {
            return [
                'isValid' => false,
                'normalized' => $datetime,
                'message' => 'Data de entrada não pode ser futura (tolerância: 1 hora)'
            ];
        }
        
        // Validar se não é muito antiga
        if (!self::isNotTooOld($datetime, 30)) {
            return [
                'isValid' => false,
                'normalized' => $datetime,
                'message' => 'Data de entrada muito antiga (máximo: 30 dias atrás)'
            ];
        }
        
        return [
            'isValid' => true,
            'normalized' => self::normalize($datetime),
            'message' => ''
        ];
    }
    
    /**
     * Validação completa de data/hora de saída
     */
    public static function validateExitDateTime(?string $saida, ?string $entrada = null): array 
    {
        if (empty($saida)) {
            return [
                'isValid' => true,
                'normalized' => '',
                'message' => ''
            ];
        }
        
        // Validar formato
        if (!self::isValidFormat($saida)) {
            return [
                'isValid' => false,
                'normalized' => $saida,
                'message' => 'Formato de data/hora de saída inválido'
            ];
        }
        
        // Validar se não é futura
        if (!self::isNotFuture($saida, 1)) {
            return [
                'isValid' => false,
                'normalized' => $saida,
                'message' => 'Data de saída não pode ser futura (tolerância: 1 hora)'
            ];
        }
        
        // Validar se é posterior à entrada
        if (!self::isExitAfterEntry($entrada, $saida)) {
            return [
                'isValid' => false,
                'normalized' => $saida,
                'message' => 'Data de saída não pode ser anterior à data de entrada'
            ];
        }
        
        return [
            'isValid' => true,
            'normalized' => self::normalize($saida),
            'message' => ''
        ];
    }
}