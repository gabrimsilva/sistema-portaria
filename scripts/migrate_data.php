<?php
/**
 * Script de Migração de Dados - Servidor Antigo → Servidor Novo
 * 
 * Migra dados do banco antigo (10.3.1.135, Docker PostgreSQL 15)
 * para o banco novo (10.3.1.130, PostgreSQL 16)
 * 
 * Uso: php scripts/migrate_data.php
 * Executar NO SERVIDOR NOVO (10.3.1.130)
 */

echo "=====================================================\n";
echo "  MIGRAÇÃO DE DADOS: Servidor Antigo → Servidor Novo\n";
echo "  Sistema de Controle de Acesso v2.0\n";
echo "=====================================================\n\n";

$OLD_DB_HOST = '10.3.1.135';
$OLD_DB_PORT = 5432;
$OLD_DB_NAME = 'access_control';
$OLD_DB_USER = 'postgres';
$OLD_DB_PASS = 'renner@postgres2024';

$localConfigFile = __DIR__ . '/../config/database.local.php';
if (!file_exists($localConfigFile)) {
    die("ERRO: config/database.local.php não encontrado no servidor novo.\n");
}
$newConfig = require $localConfigFile;

try {
    $oldDb = new PDO(
        "pgsql:host={$OLD_DB_HOST};port={$OLD_DB_PORT};dbname={$OLD_DB_NAME}",
        $OLD_DB_USER,
        $OLD_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "[OK] Conectado ao banco ANTIGO (10.3.1.135)\n";
} catch (PDOException $e) {
    die("ERRO ao conectar banco ANTIGO: " . $e->getMessage() . "\n");
}

try {
    $newDb = new PDO(
        "pgsql:host={$newConfig['host']};port={$newConfig['port']};dbname={$newConfig['database']}",
        $newConfig['username'],
        $newConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "[OK] Conectado ao banco NOVO (10.3.1.130)\n\n";
} catch (PDOException $e) {
    die("ERRO ao conectar banco NOVO: " . $e->getMessage() . "\n");
}

$stats = [
    'usuarios' => 0,
    'roles' => 0,
    'role_permissions' => 0,
    'profissionais_renner' => 0,
    'registro_acesso' => 0,
    'prestadores_cadastro' => 0,
    'prestadores_registros' => 0,
    'audit_log' => 0,
];

$newDb->beginTransaction();

try {

    // ========================================
    // 1. MIGRAR ROLES
    // ========================================
    echo "--- 1. Migrando ROLES ---\n";
    $oldRoles = $oldDb->query("SELECT * FROM roles ORDER BY id")->fetchAll();
    foreach ($oldRoles as $role) {
        $stmt = $newDb->prepare("
            INSERT INTO roles (id, name, description, system_role, active, created_at, updated_at)
            VALUES (:id, :name, :description, :system_role, :active, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                name = EXCLUDED.name,
                description = EXCLUDED.description,
                system_role = EXCLUDED.system_role,
                active = EXCLUDED.active
        ");
        $stmt->execute([
            ':id' => $role['id'],
            ':name' => $role['name'],
            ':description' => $role['description'],
            ':system_role' => $role['system_role'] ? 'true' : 'false',
            ':active' => $role['active'] ? 'true' : 'false',
            ':created_at' => $role['created_at'],
            ':updated_at' => $role['updated_at']
        ]);
        $stats['roles']++;
    }
    echo "   [OK] {$stats['roles']} roles migrados\n\n";

    // ========================================
    // 2. MIGRAR USUARIOS
    // ========================================
    echo "--- 2. Migrando USUARIOS ---\n";
    $oldUsers = $oldDb->query("SELECT * FROM usuarios ORDER BY id")->fetchAll();
    foreach ($oldUsers as $user) {
        $stmt = $newDb->prepare("
            INSERT INTO usuarios (id, nome, email, senha_hash, perfil, ativo, data_criacao, ultimo_login, role_id, anonymized_at)
            VALUES (:id, :nome, :email, :senha_hash, :perfil, :ativo, :data_criacao, :ultimo_login, :role_id, :anonymized_at)
            ON CONFLICT (id) DO UPDATE SET
                nome = EXCLUDED.nome,
                email = EXCLUDED.email,
                senha_hash = EXCLUDED.senha_hash,
                perfil = EXCLUDED.perfil,
                ativo = EXCLUDED.ativo,
                role_id = EXCLUDED.role_id
        ");
        $stmt->execute([
            ':id' => $user['id'],
            ':nome' => $user['nome'],
            ':email' => $user['email'],
            ':senha_hash' => $user['senha_hash'],
            ':perfil' => $user['perfil'],
            ':ativo' => $user['ativo'] ? 'true' : 'false',
            ':data_criacao' => $user['data_criacao'],
            ':ultimo_login' => $user['ultimo_login'],
            ':role_id' => $user['role_id'],
            ':anonymized_at' => $user['anonymized_at']
        ]);
        $stats['usuarios']++;
    }
    echo "   [OK] {$stats['usuarios']} usuários migrados\n\n";

    // ========================================
    // 3a. MIGRAR PERMISSIONS
    // ========================================
    echo "--- 3a. Migrando PERMISSIONS ---\n";
    $oldPerms = $oldDb->query("SELECT * FROM permissions ORDER BY id")->fetchAll();
    $permCount = 0;
    foreach ($oldPerms as $perm) {
        $existing = $newDb->prepare("SELECT id FROM permissions WHERE key = :key");
        $existing->execute([':key' => $perm['key']]);
        if ($existing->fetch()) {
            $permCount++;
            continue;
        }
        $stmt = $newDb->prepare("
            INSERT INTO permissions (key, description, module, created_at)
            VALUES (:key, :description, :module, :created_at)
        ");
        $stmt->execute([
            ':key' => $perm['key'],
            ':description' => $perm['description'],
            ':module' => $perm['module'],
            ':created_at' => $perm['created_at']
        ]);
        $permCount++;
    }
    echo "   [OK] {$permCount} permissões migradas\n\n";

    $oldPermIdToKey = [];
    foreach ($oldPerms as $p) {
        $oldPermIdToKey[$p['id']] = $p['key'];
    }
    $newKeyToPermId = [];
    $newPermsAll = $newDb->query("SELECT id, key FROM permissions")->fetchAll();
    foreach ($newPermsAll as $p) {
        $newKeyToPermId[$p['key']] = $p['id'];
    }

    // ========================================
    // 3b. MIGRAR ROLE_PERMISSIONS
    // ========================================
    echo "--- 3b. Migrando ROLE_PERMISSIONS ---\n";
    $oldRolePerms = $oldDb->query("SELECT * FROM role_permissions ORDER BY role_id, permission_id")->fetchAll();
    $skipped = 0;
    foreach ($oldRolePerms as $rp) {
        $oldKey = $oldPermIdToKey[$rp['permission_id']] ?? null;
        if (!$oldKey || !isset($newKeyToPermId[$oldKey])) {
            $skipped++;
            continue;
        }
        $newPermId = $newKeyToPermId[$oldKey];
        
        $stmt = $newDb->prepare("
            INSERT INTO role_permissions (role_id, permission_id, created_at)
            VALUES (:role_id, :permission_id, :created_at)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute([
            ':role_id' => $rp['role_id'],
            ':permission_id' => $newPermId,
            ':created_at' => $rp['created_at']
        ]);
        $stats['role_permissions']++;
    }
    if ($skipped > 0) {
        echo "   [AVISO] {$skipped} permissões ignoradas (não encontradas no banco novo)\n";
    }
    echo "   [OK] {$stats['role_permissions']} permissões de roles migradas\n\n";

    // ========================================
    // 4. MIGRAR PROFISSIONAIS RENNER
    // ========================================
    echo "--- 4. Migrando PROFISSIONAIS RENNER ---\n";
    $oldProfs = $oldDb->query("SELECT * FROM profissionais_renner ORDER BY id")->fetchAll();
    foreach ($oldProfs as $prof) {
        $stmt = $newDb->prepare("
            INSERT INTO profissionais_renner (id, nome, cpf, setor, empresa, fre, data_admissao, data_entrada, saida, retorno, saida_final, placa_veiculo, created_at, updated_at)
            VALUES (:id, :nome, :cpf, :setor, :empresa, :fre, :data_admissao, :data_entrada, :saida, :retorno, :saida_final, :placa_veiculo, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                nome = EXCLUDED.nome,
                cpf = EXCLUDED.cpf,
                setor = EXCLUDED.setor,
                empresa = EXCLUDED.empresa,
                fre = EXCLUDED.fre,
                data_admissao = EXCLUDED.data_admissao,
                data_entrada = EXCLUDED.data_entrada,
                saida = EXCLUDED.saida,
                retorno = EXCLUDED.retorno,
                saida_final = EXCLUDED.saida_final,
                placa_veiculo = EXCLUDED.placa_veiculo
        ");
        $stmt->execute([
            ':id' => $prof['id'],
            ':nome' => $prof['nome'],
            ':cpf' => $prof['cpf'],
            ':setor' => $prof['setor'],
            ':empresa' => $prof['empresa'],
            ':fre' => $prof['fre'],
            ':data_admissao' => $prof['data_admissao'],
            ':data_entrada' => $prof['data_entrada'],
            ':saida' => $prof['saida'],
            ':retorno' => $prof['retorno'],
            ':saida_final' => $prof['saida_final'],
            ':placa_veiculo' => $prof['placa_veiculo'],
            ':created_at' => $prof['created_at'],
            ':updated_at' => $prof['updated_at']
        ]);
        $stats['profissionais_renner']++;
    }
    echo "   [OK] {$stats['profissionais_renner']} profissionais migrados\n\n";

    // ========================================
    // 5. MIGRAR REGISTRO DE ACESSO
    // ========================================
    echo "--- 5. Migrando REGISTRO DE ACESSO ---\n";
    
    $oldAcessos = $oldDb->query("SELECT * FROM registro_acesso ORDER BY id")->fetchAll();
    $batchCount = 0;
    foreach ($oldAcessos as $acesso) {
        $stmt = $newDb->prepare("
            INSERT INTO registro_acesso (id, tipo, nome, cpf, empresa, setor, placa_veiculo, funcionario_responsavel, observacao, entrada_at, saida_at, created_by, updated_by, created_at, updated_at, profissional_renner_id, retorno, saida_final)
            VALUES (:id, :tipo, :nome, :cpf, :empresa, :setor, :placa_veiculo, :funcionario_responsavel, :observacao, :entrada_at, :saida_at, :created_by, :updated_by, :created_at, :updated_at, :profissional_renner_id, :retorno, :saida_final)
            ON CONFLICT (id) DO NOTHING
        ");
        $stmt->execute([
            ':id' => $acesso['id'],
            ':tipo' => $acesso['tipo'],
            ':nome' => $acesso['nome'],
            ':cpf' => $acesso['cpf'],
            ':empresa' => $acesso['empresa'],
            ':setor' => $acesso['setor'],
            ':placa_veiculo' => $acesso['placa_veiculo'],
            ':funcionario_responsavel' => $acesso['funcionario_responsavel'],
            ':observacao' => $acesso['observacao'],
            ':entrada_at' => $acesso['entrada_at'],
            ':saida_at' => $acesso['saida_at'],
            ':created_by' => $acesso['created_by'],
            ':updated_by' => $acesso['updated_by'],
            ':created_at' => $acesso['created_at'],
            ':updated_at' => $acesso['updated_at'],
            ':profissional_renner_id' => $acesso['profissional_renner_id'],
            ':retorno' => $acesso['retorno'],
            ':saida_final' => $acesso['saida_final']
        ]);
        $stats['registro_acesso']++;
        $batchCount++;
        if ($batchCount % 1000 === 0) {
            echo "   ... {$batchCount} registros processados\n";
        }
    }
    echo "   [OK] {$stats['registro_acesso']} registros de acesso migrados\n\n";

    // ========================================
    // 6. MIGRAR PRESTADORES DE SERVIÇO
    // ========================================
    echo "--- 6. Migrando PRESTADORES DE SERVIÇO ---\n";
    echo "   Separando em: prestadores_cadastro + prestadores_registros\n";

    $oldPrestadores = $oldDb->query("SELECT * FROM prestadores_servico ORDER BY id")->fetchAll();
    
    $cadastroMap = [];
    
    foreach ($oldPrestadores as $prest) {
        $cpfKey = trim($prest['cpf'] ?? '');
        $nomeKey = trim(strtolower($prest['nome'] ?? ''));
        $lookupKey = !empty($cpfKey) ? $cpfKey : $nomeKey;
        
        if (!isset($cadastroMap[$lookupKey])) {
            $stmt = $newDb->prepare("
                INSERT INTO prestadores_cadastro (nome, empresa, doc_type, doc_number, doc_country, placa_veiculo, observacoes, valid_from, valid_until, ativo, created_at)
                VALUES (:nome, :empresa, :doc_type, :doc_number, 'BR', :placa_veiculo, NULL, :valid_from, :valid_until, true, :created_at)
                RETURNING id
            ");
            $stmt->execute([
                ':nome' => $prest['nome'],
                ':empresa' => $prest['empresa'],
                ':doc_type' => !empty($cpfKey) ? 'CPF' : null,
                ':doc_number' => !empty($cpfKey) ? $cpfKey : null,
                ':placa_veiculo' => $prest['placa_veiculo'],
                ':valid_from' => date('Y-m-d', strtotime($prest['created_at'] ?? 'now')),
                ':valid_until' => date('Y-m-d', strtotime('+1 year')),
                ':created_at' => $prest['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $cadastroId = $stmt->fetchColumn();
            $cadastroMap[$lookupKey] = $cadastroId;
            $stats['prestadores_cadastro']++;
        }
        
        $cadastroId = $cadastroMap[$lookupKey];
        
        $stmt = $newDb->prepare("
            INSERT INTO prestadores_registros (cadastro_id, funcionario_responsavel, setor, entrada_at, saida_at, observacao_entrada, observacao_saida, created_at)
            VALUES (:cadastro_id, :funcionario_responsavel, :setor, :entrada_at, :saida_at, :obs_entrada, NULL, :created_at)
        ");
        $stmt->execute([
            ':cadastro_id' => $cadastroId,
            ':funcionario_responsavel' => $prest['funcionario_responsavel'],
            ':setor' => $prest['setor'],
            ':entrada_at' => $prest['entrada'],
            ':saida_at' => $prest['saida'],
            ':obs_entrada' => $prest['observacao'],
            ':created_at' => $prest['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        $stats['prestadores_registros']++;
        
        if ($stats['prestadores_registros'] % 500 === 0) {
            echo "   ... {$stats['prestadores_registros']} registros processados\n";
        }
    }
    echo "   [OK] {$stats['prestadores_cadastro']} cadastros únicos criados\n";
    echo "   [OK] {$stats['prestadores_registros']} registros de acesso de prestadores migrados\n\n";

    // ========================================
    // 7. MIGRAR AUDIT LOG
    // ========================================
    echo "--- 7. Migrando AUDIT LOG ---\n";
    $oldLogs = $oldDb->query("SELECT * FROM audit_log ORDER BY id")->fetchAll();
    $batchCount = 0;
    foreach ($oldLogs as $log) {
        $stmt = $newDb->prepare("
            INSERT INTO audit_log (id, user_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip_address, user_agent, timestamp, severidade, modulo, resultado)
            VALUES (:id, :user_id, :acao, :entidade, :entidade_id, :dados_antes, :dados_depois, :ip_address, :user_agent, :timestamp, :severidade, :modulo, :resultado)
            ON CONFLICT (id) DO NOTHING
        ");
        $stmt->execute([
            ':id' => $log['id'],
            ':user_id' => $log['user_id'],
            ':acao' => $log['acao'],
            ':entidade' => $log['entidade'],
            ':entidade_id' => $log['entidade_id'],
            ':dados_antes' => $log['dados_antes'],
            ':dados_depois' => $log['dados_depois'],
            ':ip_address' => $log['ip_address'],
            ':user_agent' => $log['user_agent'],
            ':timestamp' => $log['timestamp'],
            ':severidade' => $log['severidade'] ?? 'info',
            ':modulo' => $log['modulo'],
            ':resultado' => $log['resultado'] ?? 'sucesso'
        ]);
        $stats['audit_log']++;
        $batchCount++;
        if ($batchCount % 1000 === 0) {
            echo "   ... {$batchCount} logs processados\n";
        }
    }
    echo "   [OK] {$stats['audit_log']} logs de auditoria migrados\n\n";

    // ========================================
    // 8. AJUSTAR SEQUENCES
    // ========================================
    echo "--- 8. Ajustando SEQUENCES (auto-increment) ---\n";
    $sequences = [
        "SELECT setval('usuarios_id_seq', COALESCE((SELECT MAX(id) FROM usuarios), 1))",
        "SELECT setval('profissionais_renner_id_seq', COALESCE((SELECT MAX(id) FROM profissionais_renner), 1))",
        "SELECT setval('registro_acesso_id_seq', COALESCE((SELECT MAX(id) FROM registro_acesso), 1))",
        "SELECT setval('prestadores_cadastro_id_seq', COALESCE((SELECT MAX(id) FROM prestadores_cadastro), 1))",
        "SELECT setval('prestadores_registros_id_seq', COALESCE((SELECT MAX(id) FROM prestadores_registros), 1))",
        "SELECT setval('audit_log_id_seq', COALESCE((SELECT MAX(id) FROM audit_log), 1))",
        "SELECT setval('roles_id_seq', COALESCE((SELECT MAX(id) FROM roles), 1))",
    ];
    foreach ($sequences as $sql) {
        try {
            $newDb->exec($sql);
        } catch (PDOException $e) {
            echo "   [AVISO] Sequence: " . $e->getMessage() . "\n";
        }
    }
    echo "   [OK] Sequences ajustadas\n\n";

    // ========================================
    // CONFIRMAR TRANSAÇÃO
    // ========================================
    $newDb->commit();
    
    echo "=====================================================\n";
    echo "  MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "=====================================================\n\n";
    echo "Resumo:\n";
    echo "  - Roles:                  {$stats['roles']}\n";
    echo "  - Usuários:               {$stats['usuarios']}\n";
    echo "  - Permissões de roles:    {$stats['role_permissions']}\n";
    echo "  - Profissionais Renner:   {$stats['profissionais_renner']}\n";
    echo "  - Registros de acesso:    {$stats['registro_acesso']}\n";
    echo "  - Prestadores (cadastro): {$stats['prestadores_cadastro']}\n";
    echo "  - Prestadores (registros):{$stats['prestadores_registros']}\n";
    echo "  - Audit log:              {$stats['audit_log']}\n";
    echo "\nTotal de registros migrados: " . array_sum($stats) . "\n";

} catch (Exception $e) {
    $newDb->rollBack();
    echo "\n[ERRO FATAL] Migração abortada!\n";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " linha " . $e->getLine() . "\n";
    echo "\nNenhum dado foi alterado (rollback executado).\n";
    exit(1);
}
