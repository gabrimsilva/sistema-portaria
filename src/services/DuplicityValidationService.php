<?php

require_once __DIR__ . '/../../config/database.php';

class DuplicityValidationService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Verifica se um profissional Renner já tem entrada em aberto na tabela registro_acesso
     * 
     * @param int $profissionalId ID do profissional
     * @param int|null $excludeId ID do registro a excluir da verificação (para edições)
     * @return array ['isValid' => bool, 'message' => string, 'entry' => array|null]
     */
    public function validateProfissionalNotOpen($profissionalId, $excludeId = null) {
        if (empty($profissionalId)) {
            return ['isValid' => true, 'message' => '', 'entry' => null];
        }
        
        $sql = "SELECT r.id, r.nome, r.entrada_at, r.placa_veiculo 
                FROM registro_acesso r 
                WHERE r.profissional_renner_id = ? 
                  AND r.entrada_at IS NOT NULL 
                  AND r.saida_final IS NULL
                  AND r.tipo = 'profissional_renner'";
        $params = [$profissionalId];
        
        if ($excludeId) {
            $sql .= " AND r.id != ?";
            $params[] = $excludeId;
        }
        
        $entradaAberta = $this->db->fetch($sql, $params);
        
        if ($entradaAberta) {
            $horarioFormatado = date('H:i', strtotime($entradaAberta['entrada_at']));
            $dataFormatada = date('d/m/Y', strtotime($entradaAberta['entrada_at']));
            return [
                'isValid' => false,
                'message' => "Este profissional já tem uma entrada em aberto desde {$dataFormatada} às {$horarioFormatado}. Por favor, registre a saída antes de criar uma nova entrada.",
                'entry' => $entradaAberta
            ];
        }
        
        return ['isValid' => true, 'message' => '', 'entry' => null];
    }
    
    /**
     * Verifica se CPF já tem entrada em aberto (sem saída)
     * 
     * @param string $cpf CPF a verificar
     * @param int|null $excludeId ID do registro a excluir da verificação (para edições)
     * @param string|null $excludeTable Tabela do registro sendo editado ('visitantes_registros' ou 'prestadores_servico')
     * @return array ['isValid' => bool, 'message' => string, 'entry' => array|null]
     */
    public function validateCpfNotOpen($cpf, $excludeId = null, $excludeTable = null) {
        if (empty($cpf)) {
            return ['isValid' => true, 'message' => '', 'entry' => null];
        }
        
        // Normalizar CPF (apenas dígitos)
        $cpf = preg_replace('/\D/', '', $cpf);
        
        // Verificar visitantes ativos (PRÉ-CADASTROS V2.0.0)
        $sql = "SELECT c.nome, c.doc_number as cpf, c.empresa, r.entrada_at as hora_entrada, 'Visitante' as tipo 
                FROM visitantes_registros r
                JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                WHERE c.doc_number = ? AND r.entrada_at IS NOT NULL AND r.saida_at IS NULL
                  AND c.deleted_at IS NULL";
        $params = [$cpf];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'visitantes_registros') {
            $sql .= " AND r.id != ?";
            $params[] = $excludeId;
        }
        
        $visitanteAtivo = $this->db->fetch($sql, $params);
        if ($visitanteAtivo) {
            $horarioFormatado = date('H:i', strtotime($visitanteAtivo['hora_entrada']));
            return [
                'isValid' => false,
                'message' => "Entrada em aberto para este CPF às {$horarioFormatado} (Visitante: {$visitanteAtivo['nome']}).",
                'entry' => $visitanteAtivo
            ];
        }
        
        // Verificar prestadores ativos
        $sql = "SELECT nome, cpf, empresa, entrada, 'Prestador' as tipo 
                FROM prestadores_servico 
                WHERE cpf = ? AND entrada IS NOT NULL AND saida IS NULL";
        $params = [$cpf];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'prestadores_servico') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $prestadorAtivo = $this->db->fetch($sql, $params);
        if ($prestadorAtivo) {
            $horarioFormatado = date('H:i', strtotime($prestadorAtivo['entrada']));
            return [
                'isValid' => false,
                'message' => "Entrada em aberto para este CPF às {$horarioFormatado} (Prestador: {$prestadorAtivo['nome']}).",
                'entry' => $prestadorAtivo
            ];
        }
        
        // Verificar registros unificados com CPF ativo
        $sql = "SELECT nome, cpf, empresa, entrada_at, tipo, 'Registro' as categoria 
                FROM registro_acesso 
                WHERE cpf = ? AND entrada_at IS NOT NULL AND saida_at IS NULL";
        $params = [$cpf];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'registro_acesso') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $registroAtivo = $this->db->fetch($sql, $params);
        if ($registroAtivo) {
            $horarioFormatado = date('H:i', strtotime($registroAtivo['entrada_at']));
            $tipoFormatado = ucfirst(str_replace('_', ' ', $registroAtivo['tipo']));
            return [
                'isValid' => false,
                'message' => "Entrada em aberto para este CPF às {$horarioFormatado} ({$tipoFormatado}: {$registroAtivo['nome']}).",
                'entry' => $registroAtivo
            ];
        }
        
        return ['isValid' => true, 'message' => '', 'entry' => null];
    }
    
    /**
     * Valida se uma placa já está em uso em TODO o sistema
     * Profissionais Renner têm placas únicas sempre
     * Visitantes/Prestadores não podem usar placas já cadastradas
     * 
     * @param string $placa Placa a verificar
     * @param int|null $excludeId ID do registro a excluir da verificação
     * @param string|null $excludeTable Tabela do registro sendo editado
     * @return array ['isValid' => bool, 'message' => string, 'entry' => array|null]
     */
    public function validatePlacaUnique($placa, $excludeId = null, $excludeTable = null) {
        if (empty($placa) || $placa === 'APE') {
            return ['isValid' => true, 'message' => '', 'entry' => null];
        }
        
        // Normalizar placa (apenas letras e números, maiúscula)
        $placa = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa)));
        
        // 1. PRIORIDADE: Verificar Profissionais Renner (placas sempre únicas)
        $sql = "SELECT nome, cpf, placa_veiculo, 'Profissional Renner' as tipo 
                FROM profissionais_renner 
                WHERE placa_veiculo = ?";
        $params = [$placa];
        
        // Permitir edição do próprio registro
        if ($excludeId && $excludeTable === 'profissionais_renner') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $profissional = $this->db->fetch($sql, $params);
        if ($profissional) {
            return [
                'isValid' => false,
                'message' => "Esta placa já pertence ao funcionário {$profissional['nome']} (Profissional Renner). Placas de funcionários são únicas no sistema.",
                'entry' => $profissional
            ];
        }
        
        // 2. Verificar visitantes com a mesma placa (globalmente única) - PRÉ-CADASTROS V2.0.0
        error_log("🔍 validatePlacaUnique - Placa: $placa, excludeId: " . ($excludeId ?? 'NULL') . ", excludeTable: " . ($excludeTable ?? 'NULL'));
        
        $sql = "SELECT c.id as cadastro_id, c.nome, c.doc_number as cpf, c.empresa, c.placa_veiculo, r.entrada_at as hora_entrada, 'Visitante' as tipo 
                FROM visitantes_cadastro c
                LEFT JOIN visitantes_registros r ON r.cadastro_id = c.id
                WHERE c.placa_veiculo = ? AND c.deleted_at IS NULL";
        $params = [$placa];
        
        // Excluir o próprio cadastro se estamos reutilizando/editando
        if ($excludeId && $excludeTable === 'visitantes_registros') {
            error_log("🔧 Excluindo registro ID: $excludeId da validação");
            $sql .= " AND r.id != ?";
            $params[] = $excludeId;
        }
        if ($excludeId && $excludeTable === 'visitantes_cadastro') {
            error_log("🔧 Excluindo cadastro ID: $excludeId da validação");
            $sql .= " AND c.id != ?";
            $params[] = $excludeId;
        }
        
        error_log("📝 SQL: $sql");
        error_log("📝 Params: " . json_encode($params));
        
        $visitanteExistente = $this->db->fetch($sql, $params);
        
        error_log("📊 Resultado da query: " . json_encode($visitanteExistente));
        
        if ($visitanteExistente) {
            error_log("❌ PLACA JÁ EXISTE - Cadastro ID: " . ($visitanteExistente['cadastro_id'] ?? 'N/A') . " Nome: " . $visitanteExistente['nome']);
            return [
                'isValid' => false,
                'message' => "Esta placa já foi cadastrada anteriormente para outro visitante ({$visitanteExistente['nome']}). Placas devem ser únicas no sistema.",
                'entry' => $visitanteExistente
            ];
        }
        
        error_log("✅ Placa disponível para visitantes");
        
        // 3. Verificar prestadores com a mesma placa (globalmente única)
        $sql = "SELECT nome, cpf, empresa, placa_veiculo, entrada, 'Prestador' as tipo 
                FROM prestadores_servico 
                WHERE placa_veiculo = ?";
        $params = [$placa];
        
        if ($excludeId && $excludeTable === 'prestadores_servico') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $prestadorExistente = $this->db->fetch($sql, $params);
        if ($prestadorExistente) {
            return [
                'isValid' => false,
                'message' => "Esta placa já foi cadastrada anteriormente para outro prestador ({$prestadorExistente['nome']}). Placas devem ser únicas no sistema.",
                'entry' => $prestadorExistente
            ];
        }
        
        return ['isValid' => true, 'message' => '', 'entry' => null];
    }
    
    /**
     * Verifica se placa já está em uso em uma entrada em aberto
     * @deprecated Use validatePlacaUnique para validação completa
     * 
     * @param string $placa Placa a verificar
     * @param int|null $excludeId ID do registro a excluir da verificação
     * @param string|null $excludeTable Tabela do registro sendo editado ('visitantes_registros' ou 'prestadores_servico')
     * @return array ['isValid' => bool, 'message' => string, 'entry' => array|null]
     */
    public function validatePlacaNotOpen($placa, $excludeId = null, $excludeTable = null) {
        if (empty($placa)) {
            return ['isValid' => true, 'message' => '', 'entry' => null];
        }
        
        // Normalizar placa (apenas letras e números, maiúscula)
        $placa = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($placa)));
        
        // Verificar visitantes com placa ativa (PRÉ-CADASTROS V2.0.0)
        $sql = "SELECT c.nome, c.doc_number as cpf, c.empresa, c.placa_veiculo, r.entrada_at as hora_entrada, 'Visitante' as tipo 
                FROM visitantes_registros r
                JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                WHERE c.placa_veiculo = ? AND r.entrada_at IS NOT NULL AND r.saida_at IS NULL
                  AND c.deleted_at IS NULL";
        $params = [$placa];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'visitantes_registros') {
            $sql .= " AND r.id != ?";
            $params[] = $excludeId;
        }
        
        $visitanteAtivo = $this->db->fetch($sql, $params);
        if ($visitanteAtivo) {
            $horarioFormatado = date('H:i', strtotime($visitanteAtivo['hora_entrada']));
            return [
                'isValid' => false,
                'message' => "Placa já está em uso em um acesso aberto às {$horarioFormatado} (Visitante: {$visitanteAtivo['nome']}).",
                'entry' => $visitanteAtivo
            ];
        }
        
        // Verificar prestadores com placa ativa
        $sql = "SELECT nome, cpf, empresa, placa_veiculo, entrada, 'Prestador' as tipo 
                FROM prestadores_servico 
                WHERE placa_veiculo = ? AND entrada IS NOT NULL AND saida IS NULL";
        $params = [$placa];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'prestadores_servico') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $prestadorAtivo = $this->db->fetch($sql, $params);
        if ($prestadorAtivo) {
            $horarioFormatado = date('H:i', strtotime($prestadorAtivo['entrada']));
            return [
                'isValid' => false,
                'message' => "Placa já está em uso em um acesso aberto às {$horarioFormatado} (Prestador: {$prestadorAtivo['nome']}).",
                'entry' => $prestadorAtivo
            ];
        }
        
        // Verificar registros unificados com placa ativa (só está aberto se saida_final é NULL)
        $sql = "SELECT nome, cpf, empresa, placa_veiculo, entrada_at, tipo, 'Registro' as categoria 
                FROM registro_acesso 
                WHERE placa_veiculo = ? AND entrada_at IS NOT NULL AND saida_final IS NULL";
        $params = [$placa];
        
        // Só excluir se estamos editando a mesma tabela
        if ($excludeId && $excludeTable === 'registro_acesso') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $registroAtivo = $this->db->fetch($sql, $params);
        if ($registroAtivo) {
            $horarioFormatado = date('H:i', strtotime($registroAtivo['entrada_at']));
            $tipoFormatado = ucfirst(str_replace('_', ' ', $registroAtivo['tipo']));
            return [
                'isValid' => false,
                'message' => "Placa já está em uso em um acesso aberto às {$horarioFormatado} ({$tipoFormatado}: {$registroAtivo['nome']}).",
                'entry' => $registroAtivo
            ];
        }
        
        return ['isValid' => true, 'message' => '', 'entry' => null];
    }
    
    /**
     * Verifica debounce: impede cadastro repetido com mesmo CPF+placa em menos de 30 segundos
     * 
     * @param string $cpf CPF a verificar
     * @param string|null $placa Placa a verificar (opcional)
     * @param int $debounceSeconds Segundos de debounce (padrão: 30)
     * @return array ['isValid' => bool, 'message' => string, 'lastEntry' => array|null]
     */
    public function validateDebounce($cpf, $placa = null, $debounceSeconds = 30) {
        if (empty($cpf)) {
            return ['isValid' => true, 'message' => '', 'lastEntry' => null];
        }
        
        $cpf = preg_replace('/\D/', '', $cpf);
        $placa = $placa ? strtoupper(trim($placa)) : null;
        
        // Verificar últimos registros nos últimos X segundos
        $timeLimit = date('Y-m-d H:i:s', time() - $debounceSeconds);
        
        // Buscar em visitantes (PRÉ-CADASTROS V2.0.0)
        $sql = "SELECT c.nome, c.doc_number as cpf, c.placa_veiculo, r.created_at, 'Visitante' as tipo 
                FROM visitantes_registros r
                JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                WHERE c.doc_number = ? AND r.created_at > ? AND c.deleted_at IS NULL";
        $params = [$cpf, $timeLimit];
        
        if ($placa) {
            $sql .= " AND c.placa_veiculo = ?";
            $params[] = $placa;
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT 1";
        
        $recentVisitante = $this->db->fetch($sql, $params);
        if ($recentVisitante) {
            $segundosRestantes = $debounceSeconds - (time() - strtotime($recentVisitante['created_at']));
            return [
                'isValid' => false,
                'message' => "Aguarde {$segundosRestantes} segundos antes de cadastrar novamente este CPF" . ($placa ? "/placa" : "") . ".",
                'lastEntry' => $recentVisitante
            ];
        }
        
        // Buscar em prestadores
        $sql = "SELECT nome, cpf, placa_veiculo, created_at, 'Prestador' as tipo 
                FROM prestadores_servico 
                WHERE cpf = ? AND created_at > ?";
        $params = [$cpf, $timeLimit];
        
        if ($placa) {
            $sql .= " AND placa_veiculo = ?";
            $params[] = $placa;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 1";
        
        $recentPrestador = $this->db->fetch($sql, $params);
        if ($recentPrestador) {
            $segundosRestantes = $debounceSeconds - (time() - strtotime($recentPrestador['created_at']));
            return [
                'isValid' => false,
                'message' => "Aguarde {$segundosRestantes} segundos antes de cadastrar novamente este CPF" . ($placa ? "/placa" : "") . ".",
                'lastEntry' => $recentPrestador
            ];
        }
        
        return ['isValid' => true, 'message' => '', 'lastEntry' => null];
    }
    
    /**
     * Verifica sobreposição de horários para edição
     * 
     * @param string $cpf CPF da pessoa
     * @param string $novaEntrada Nova data/hora de entrada
     * @param string|null $novaSaida Nova data/hora de saída (opcional)
     * @param int $excludeId ID do registro sendo editado
     * @param string $tabela Tabela a verificar ('visitantes_registros' ou 'prestadores_servico')
     * @return array ['isValid' => bool, 'message' => string, 'conflictingEntry' => array|null]
     */
    public function validateTimeOverlap($cpf, $novaEntrada, $novaSaida, $excludeId, $tabela) {
        if (empty($cpf) || empty($novaEntrada)) {
            return ['isValid' => true, 'message' => '', 'conflictingEntry' => null];
        }
        
        $cpf = preg_replace('/\D/', '', $cpf);
        
        // Verificar se saída é posterior à entrada
        if ($novaSaida && strtotime($novaSaida) <= strtotime($novaEntrada)) {
            return [
                'isValid' => false,
                'message' => "Horário de saída deve ser posterior ao horário de entrada.",
                'conflictingEntry' => null
            ];
        }
        
        // PRÉ-CADASTROS V2.0.0: Campos específicos por tabela
        if ($tabela === 'visitantes_registros') {
            $sql = "SELECT c.nome, c.doc_number as cpf, r.entrada_at as entrada, r.saida_at as saida 
                    FROM visitantes_registros r
                    JOIN visitantes_cadastro c ON c.id = r.cadastro_id
                    WHERE c.doc_number = ? AND r.id != ? AND c.deleted_at IS NULL";
        } else {
            $campoEntrada = ($tabela === 'prestadores_servico') ? 'entrada' : 'hora_entrada';
            $campoSaida = ($tabela === 'prestadores_servico') ? 'saida' : 'hora_saida';
            $sql = "SELECT nome, cpf, {$campoEntrada} as entrada, {$campoSaida} as saida 
                    FROM {$tabela} 
                    WHERE cpf = ? AND id != ?";
        }
        
        $registrosExistentes = $this->db->fetchAll($sql, [$cpf, $excludeId]);
        
        foreach ($registrosExistentes as $registro) {
            $entradaExistente = $registro['entrada'];
            $saidaExistente = $registro['saida'];
            
            // Se registro existente não tem saída, considera como "ainda ativo"
            if (!$saidaExistente) {
                // Novo período não pode começar antes de registro sem saída
                if (strtotime($novaEntrada) >= strtotime($entradaExistente)) {
                    $horarioFormatado = date('d/m/Y H:i', strtotime($entradaExistente));
                    return [
                        'isValid' => false,
                        'message' => "Intervalo sobreposto a outro registro sem saída desde {$horarioFormatado}.",
                        'conflictingEntry' => $registro
                    ];
                }
            } else {
                // Verificar sobreposição com período completo
                $novoPeriodoInicio = strtotime($novaEntrada);
                $novoPeriodoFim = $novaSaida ? strtotime($novaSaida) : time(); // Se sem saída, considera até agora
                
                $periodoExistenteInicio = strtotime($entradaExistente);
                $periodoExistenteFim = strtotime($saidaExistente);
                
                // Há sobreposição se um período inicia antes do outro terminar
                if ($novoPeriodoInicio < $periodoExistenteFim && $novoPeriodoFim > $periodoExistenteInicio) {
                    $horarioFormatado = date('d/m/Y H:i', strtotime($entradaExistente)) . ' às ' . date('H:i', strtotime($saidaExistente));
                    return [
                        'isValid' => false,
                        'message' => "Intervalo de horário sobreposto a outro registro ({$horarioFormatado}).",
                        'conflictingEntry' => $registro
                    ];
                }
            }
        }
        
        return ['isValid' => true, 'message' => '', 'conflictingEntry' => null];
    }
    
    /**
     * Executa todas as validações para um novo registro
     * 
     * @param array $dados Dados do registro (cpf, placa_veiculo, etc.)
     * @param string $tipo Tipo do registro ('visitante' ou 'prestador')
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function validateNewEntry($dados, $tipo) {
        $errors = [];
        
        $cpf = $dados['cpf'] ?? '';
        $placa = $dados['placa_veiculo'] ?? null;
        
        // Validar CPF não está em aberto
        $cpfValidation = $this->validateCpfNotOpen($cpf);
        if (!$cpfValidation['isValid']) {
            $errors[] = $cpfValidation['message'];
        }
        
        // Validar placa não está em uso (se informada e não for "APE")
        if (!empty($placa) && $placa !== 'APE') {
            $placaValidation = $this->validatePlacaUnique($placa);
            if (!$placaValidation['isValid']) {
                $errors[] = $placaValidation['message'];
            }
        }
        
        // Validar debounce
        $debounceValidation = $this->validateDebounce($cpf, $placa);
        if (!$debounceValidation['isValid']) {
            $errors[] = $debounceValidation['message'];
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Executa validações para edição de registro
     * 
     * @param array $dados Dados do registro editado
     * @param int $id ID do registro sendo editado
     * @param string $tabela Tabela do registro
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function validateEditEntry($dados, $id, $tabela) {
        $errors = [];
        
        $cpf = $dados['cpf'] ?? '';
        $placa = $dados['placa_veiculo'] ?? null;
        $entrada = $dados['hora_entrada'] ?? $dados['entrada'] ?? '';
        $saida = $dados['hora_saida'] ?? $dados['saida'] ?? null;
        
        // Validar CPF não está em aberto (excluindo o próprio registro da mesma tabela)
        $cpfValidation = $this->validateCpfNotOpen($cpf, $id, $tabela);
        if (!$cpfValidation['isValid']) {
            $errors[] = $cpfValidation['message'];
        }
        
        // Validar placa não está em uso (se informada e não for "APE", excluindo o próprio registro da mesma tabela)
        if (!empty($placa) && $placa !== 'APE') {
            $placaValidation = $this->validatePlacaUnique($placa, $id, $tabela);
            if (!$placaValidation['isValid']) {
                $errors[] = $placaValidation['message'];
            }
        }
        
        // Validar sobreposição de horários
        if (!empty($entrada)) {
            $timeValidation = $this->validateTimeOverlap($cpf, $entrada, $saida, $id, $tabela);
            if (!$timeValidation['isValid']) {
                $errors[] = $timeValidation['message'];
            }
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}