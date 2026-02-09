<?php
/**
 * Script de Migração de Dados - Servidor Antigo → Servidor Novo
 * 
 * Migra dados do banco antigo (10.3.1.135, Docker PostgreSQL 15)
 * para o banco novo (10.3.1.130, PostgreSQL 16)
 * 
 * Detecta automaticamente as colunas de cada tabela para lidar
 * com diferenças de schema entre os dois bancos.
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

function getTableColumns(PDO $db, string $table): array {
    $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = :table ORDER BY ordinal_position");
    $stmt->execute([':table' => $table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function migrateTable(PDO $oldDb, PDO $newDb, string $oldTable, string $newTable, array $columnMap = [], array $defaults = [], string $conflictCol = 'id', int $logEvery = 500): int {
    $oldCols = getTableColumns($oldDb, $oldTable);
    $newCols = getTableColumns($newDb, $newTable);
    
    echo "   Colunas origem ({$oldTable}): " . implode(', ', $oldCols) . "\n";
    echo "   Colunas destino ({$newTable}): " . implode(', ', $newCols) . "\n";
    
    $commonCols = array_intersect($oldCols, $newCols);
    
    foreach ($columnMap as $oldCol => $newCol) {
        if (in_array($oldCol, $oldCols) && in_array($newCol, $newCols)) {
            $commonCols[] = $newCol;
        }
    }
    $commonCols = array_unique(array_values($commonCols));
    
    foreach ($defaults as $col => $val) {
        if (in_array($col, $newCols) && !in_array($col, $commonCols)) {
            $commonCols[] = $col;
        }
    }
    
    echo "   Colunas a migrar: " . implode(', ', $commonCols) . "\n";
    
    $oldRows = $oldDb->query("SELECT * FROM {$oldTable} ORDER BY id")->fetchAll();
    $count = 0;
    
    foreach ($oldRows as $row) {
        $values = [];
        foreach ($commonCols as $col) {
            $reverseMap = array_flip($columnMap);
            $sourceCol = $reverseMap[$col] ?? $col;
            
            if (isset($defaults[$col])) {
                if (is_callable($defaults[$col])) {
                    $val = $defaults[$col]($row);
                } else {
                    $val = $defaults[$col];
                }
            } elseif (array_key_exists($sourceCol, $row)) {
                $val = $row[$sourceCol];
            } elseif (array_key_exists($col, $row)) {
                $val = $row[$col];
            } else {
                $val = null;
            }
            
            if ($val === '' || $val === null) {
                $val = null;
            } elseif ($val === 't' || $val === 'true' || $val === true) {
                $val = 't';
            } elseif ($val === 'f' || $val === 'false' || $val === false) {
                $val = 'f';
            }
            $values[":{$col}"] = $val;
        }
        
        $colList = implode(', ', $commonCols);
        $paramList = implode(', ', array_map(fn($c) => ":{$c}", $commonCols));
        
        $stmt = $newDb->prepare("
            INSERT INTO {$newTable} ({$colList})
            VALUES ({$paramList})
            ON CONFLICT ({$conflictCol}) DO NOTHING
        ");
        $stmt->execute($values);
        $count++;
        
        if ($logEvery > 0 && $count % $logEvery === 0) {
            echo "   ... {$count} registros processados\n";
        }
    }
    
    return $count;
}

$stats = [
    'roles' => 0,
    'usuarios' => 0,
    'permissions' => 0,
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
    $stats['roles'] = migrateTable($oldDb, $newDb, 'roles', 'roles');
    echo "   [OK] {$stats['roles']} roles migrados\n\n";

    // ========================================
    // 2. MIGRAR USUARIOS
    // ========================================
    echo "--- 2. Migrando USUARIOS ---\n";
    $stats['usuarios'] = migrateTable($oldDb, $newDb, 'usuarios', 'usuarios');
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
        $newCols = getTableColumns($newDb, 'permissions');
        $insertCols = [];
        $insertParams = [];
        $insertValues = [];
        foreach (['key', 'description', 'module', 'created_at'] as $col) {
            if (in_array($col, $newCols) && isset($perm[$col])) {
                $insertCols[] = $col;
                $insertParams[] = ":{$col}";
                $insertValues[":{$col}"] = $perm[$col];
            }
        }
        $colStr = implode(', ', $insertCols);
        $paramStr = implode(', ', $insertParams);
        $stmt = $newDb->prepare("INSERT INTO permissions ({$colStr}) VALUES ({$paramStr})");
        $stmt->execute($insertValues);
        $permCount++;
    }
    $stats['permissions'] = $permCount;
    echo "   [OK] {$permCount} permissões migradas\n\n";

    // ========================================
    // 3b. MIGRAR ROLE_PERMISSIONS (com mapeamento de IDs)
    // ========================================
    echo "--- 3b. Migrando ROLE_PERMISSIONS ---\n";
    $oldPermIdToKey = [];
    foreach ($oldPerms as $p) {
        $oldPermIdToKey[$p['id']] = $p['key'];
    }
    $newKeyToPermId = [];
    $newPermsAll = $newDb->query("SELECT id, key FROM permissions")->fetchAll();
    foreach ($newPermsAll as $p) {
        $newKeyToPermId[$p['key']] = $p['id'];
    }

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
        $params = [
            ':role_id' => $rp['role_id'],
            ':permission_id' => $newPermId,
        ];
        $params[':created_at'] = $rp['created_at'] ?? date('Y-m-d H:i:s');
        $stmt->execute($params);
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
    $stats['profissionais_renner'] = migrateTable($oldDb, $newDb, 'profissionais_renner', 'profissionais_renner');
    echo "   [OK] {$stats['profissionais_renner']} profissionais migrados\n\n";

    // ========================================
    // 5. MIGRAR REGISTRO DE ACESSO
    // ========================================
    echo "--- 5. Migrando REGISTRO DE ACESSO ---\n";
    
    $oldRegCols = getTableColumns($oldDb, 'registro_acesso');
    $newRegCols = getTableColumns($newDb, 'registro_acesso');
    
    $colMapping = [];
    if (in_array('data_entrada', $oldRegCols) && !in_array('data_entrada', $newRegCols) && in_array('entrada_at', $newRegCols)) {
        $colMapping['data_entrada'] = 'entrada_at';
    }
    if (in_array('data_saida', $oldRegCols) && !in_array('data_saida', $newRegCols) && in_array('saida_at', $newRegCols)) {
        $colMapping['data_saida'] = 'saida_at';
    }
    if (in_array('entrada_at', $oldRegCols) && !in_array('entrada_at', $newRegCols) && in_array('data_entrada', $newRegCols)) {
        $colMapping['entrada_at'] = 'data_entrada';
    }
    if (in_array('saida_at', $oldRegCols) && !in_array('saida_at', $newRegCols) && in_array('data_saida', $newRegCols)) {
        $colMapping['saida_at'] = 'data_saida';
    }
    
    $defaults = [];
    if (in_array('data_entrada', $newRegCols) && in_array('entrada_at', $newRegCols)) {
        $defaults['data_entrada'] = function($row) {
            return $row['data_entrada'] ?? $row['entrada_at'] ?? null;
        };
    }
    if (in_array('data_saida', $newRegCols) && in_array('saida_at', $newRegCols)) {
        $defaults['data_saida'] = function($row) {
            return $row['data_saida'] ?? $row['saida_at'] ?? null;
        };
    }
    
    $stats['registro_acesso'] = migrateTable($oldDb, $newDb, 'registro_acesso', 'registro_acesso', $colMapping, $defaults, 'id', 1000);
    echo "   [OK] {$stats['registro_acesso']} registros de acesso migrados\n\n";

    // ========================================
    // 6. MIGRAR PRESTADORES DE SERVIÇO
    // ========================================
    echo "--- 6. Migrando PRESTADORES DE SERVIÇO ---\n";
    
    $oldHasPrestadores = !empty(getTableColumns($oldDb, 'prestadores_servico'));
    $newHasCadastro = !empty(getTableColumns($newDb, 'prestadores_cadastro'));
    $newHasRegistros = !empty(getTableColumns($newDb, 'prestadores_registros'));
    
    if ($oldHasPrestadores && $newHasCadastro && $newHasRegistros) {
        echo "   Separando em: prestadores_cadastro + prestadores_registros\n";
        
        $newCadCols = getTableColumns($newDb, 'prestadores_cadastro');
        $newRegCols = getTableColumns($newDb, 'prestadores_registros');
        echo "   Colunas cadastro: " . implode(', ', $newCadCols) . "\n";
        echo "   Colunas registros: " . implode(', ', $newRegCols) . "\n";
        
        $oldPrestadores = $oldDb->query("SELECT * FROM prestadores_servico ORDER BY id")->fetchAll();
        $cadastroMap = [];
        
        foreach ($oldPrestadores as $prest) {
            $cpfKey = trim($prest['cpf'] ?? '');
            $nomeKey = trim(strtolower($prest['nome'] ?? ''));
            $lookupKey = !empty($cpfKey) ? $cpfKey : $nomeKey;
            
            if (!isset($cadastroMap[$lookupKey])) {
                $cadValues = [
                    ':nome' => $prest['nome'],
                    ':empresa' => $prest['empresa'] ?? null,
                ];
                $cadCols = ['nome', 'empresa'];
                
                if (in_array('doc_type', $newCadCols)) {
                    $cadCols[] = 'doc_type';
                    $cadValues[':doc_type'] = !empty($cpfKey) ? 'CPF' : null;
                }
                if (in_array('doc_number', $newCadCols)) {
                    $cadCols[] = 'doc_number';
                    $cadValues[':doc_number'] = !empty($cpfKey) ? $cpfKey : null;
                }
                if (in_array('doc_country', $newCadCols)) {
                    $cadCols[] = 'doc_country';
                    $cadValues[':doc_country'] = 'BR';
                }
                if (in_array('placa_veiculo', $newCadCols)) {
                    $cadCols[] = 'placa_veiculo';
                    $cadValues[':placa_veiculo'] = $prest['placa_veiculo'] ?? null;
                }
                if (in_array('valid_from', $newCadCols)) {
                    $cadCols[] = 'valid_from';
                    $cadValues[':valid_from'] = date('Y-m-d', strtotime($prest['created_at'] ?? 'now'));
                }
                if (in_array('valid_until', $newCadCols)) {
                    $cadCols[] = 'valid_until';
                    $cadValues[':valid_until'] = date('Y-m-d', strtotime('+1 year'));
                }
                if (in_array('ativo', $newCadCols)) {
                    $cadCols[] = 'ativo';
                    $cadValues[':ativo'] = true;
                }
                if (in_array('created_at', $newCadCols)) {
                    $cadCols[] = 'created_at';
                    $cadValues[':created_at'] = $prest['created_at'] ?? date('Y-m-d H:i:s');
                }
                if (in_array('data_cadastro', $newCadCols)) {
                    $cadCols[] = 'data_cadastro';
                    $cadValues[':data_cadastro'] = $prest['created_at'] ?? date('Y-m-d H:i:s');
                }
                
                $colStr = implode(', ', $cadCols);
                $paramStr = implode(', ', array_map(fn($c) => ":{$c}", $cadCols));
                
                $stmt = $newDb->prepare("INSERT INTO prestadores_cadastro ({$colStr}) VALUES ({$paramStr}) RETURNING id");
                $stmt->execute($cadValues);
                $cadastroId = $stmt->fetchColumn();
                $cadastroMap[$lookupKey] = $cadastroId;
                $stats['prestadores_cadastro']++;
            }
            
            $cadastroId = $cadastroMap[$lookupKey];
            
            $regValues = [':cadastro_id' => $cadastroId];
            $regCols = ['cadastro_id'];
            
            if (in_array('funcionario_responsavel', $newRegCols)) {
                $regCols[] = 'funcionario_responsavel';
                $regValues[':funcionario_responsavel'] = $prest['funcionario_responsavel'] ?? null;
            }
            if (in_array('setor', $newRegCols)) {
                $regCols[] = 'setor';
                $regValues[':setor'] = $prest['setor'] ?? null;
            }
            if (in_array('entrada_at', $newRegCols)) {
                $regCols[] = 'entrada_at';
                $regValues[':entrada_at'] = $prest['entrada'] ?? $prest['entrada_at'] ?? null;
            }
            if (in_array('data_entrada', $newRegCols)) {
                $regCols[] = 'data_entrada';
                $regValues[':data_entrada'] = $prest['entrada'] ?? $prest['data_entrada'] ?? null;
            }
            if (in_array('saida_at', $newRegCols)) {
                $regCols[] = 'saida_at';
                $regValues[':saida_at'] = $prest['saida'] ?? $prest['saida_at'] ?? null;
            }
            if (in_array('data_saida', $newRegCols)) {
                $regCols[] = 'data_saida';
                $regValues[':data_saida'] = $prest['saida'] ?? $prest['data_saida'] ?? null;
            }
            if (in_array('observacao_entrada', $newRegCols)) {
                $regCols[] = 'observacao_entrada';
                $regValues[':observacao_entrada'] = $prest['observacao'] ?? null;
            }
            if (in_array('observacao', $newRegCols)) {
                $regCols[] = 'observacao';
                $regValues[':observacao'] = $prest['observacao'] ?? null;
            }
            if (in_array('created_at', $newRegCols)) {
                $regCols[] = 'created_at';
                $regValues[':created_at'] = $prest['created_at'] ?? date('Y-m-d H:i:s');
            }
            
            $colStr = implode(', ', $regCols);
            $paramStr = implode(', ', array_map(fn($c) => ":{$c}", $regCols));
            
            $stmt = $newDb->prepare("INSERT INTO prestadores_registros ({$colStr}) VALUES ({$paramStr})");
            $stmt->execute($regValues);
            $stats['prestadores_registros']++;
            
            if ($stats['prestadores_registros'] % 500 === 0) {
                echo "   ... {$stats['prestadores_registros']} registros processados\n";
            }
        }
        echo "   [OK] {$stats['prestadores_cadastro']} cadastros únicos criados\n";
        echo "   [OK] {$stats['prestadores_registros']} registros de acesso de prestadores migrados\n\n";
    } else {
        echo "   [PULADO] Tabelas de prestadores não encontradas em ambos os bancos\n\n";
    }

    // ========================================
    // 7. MIGRAR AUDIT LOG
    // ========================================
    echo "--- 7. Migrando AUDIT LOG ---\n";
    $oldHasAudit = !empty(getTableColumns($oldDb, 'audit_log'));
    $newHasAudit = !empty(getTableColumns($newDb, 'audit_log'));
    
    if ($oldHasAudit && $newHasAudit) {
        $stats['audit_log'] = migrateTable($oldDb, $newDb, 'audit_log', 'audit_log', [], [], 'id', 1000);
        echo "   [OK] {$stats['audit_log']} logs de auditoria migrados\n\n";
    } else {
        echo "   [PULADO] Tabela audit_log não encontrada em ambos os bancos\n\n";
    }

    // ========================================
    // 8. MIGRAR RAMAIS
    // ========================================
    echo "--- 8. Migrando RAMAIS ---\n";
    $oldHasRamais = !empty(getTableColumns($oldDb, 'ramais'));
    $newHasRamais = !empty(getTableColumns($newDb, 'ramais'));
    
    if ($oldHasRamais && $newHasRamais) {
        $stats['ramais'] = migrateTable($oldDb, $newDb, 'ramais', 'ramais', [], [], 'id');
        echo "   [OK] {$stats['ramais']} ramais migrados\n\n";
    } else {
        echo "   [PULADO] Tabela ramais não encontrada em ambos os bancos\n\n";
    }

    // ========================================
    // 9. AJUSTAR SEQUENCES
    // ========================================
    echo "--- 9. Ajustando SEQUENCES (auto-increment) ---\n";
    $seqTables = ['usuarios', 'profissionais_renner', 'registro_acesso', 'prestadores_cadastro', 'prestadores_registros', 'audit_log', 'roles', 'permissions', 'ramais'];
    foreach ($seqTables as $tbl) {
        try {
            $newDb->exec("SELECT setval(pg_get_serial_sequence('{$tbl}', 'id'), COALESCE((SELECT MAX(id) FROM {$tbl}), 1))");
        } catch (PDOException $e) {
            echo "   [AVISO] Sequence {$tbl}: " . $e->getMessage() . "\n";
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
    foreach ($stats as $key => $val) {
        echo "  - " . str_pad($key, 25) . ": {$val}\n";
    }
    echo "\nTotal de registros migrados: " . array_sum($stats) . "\n";

} catch (Exception $e) {
    $newDb->rollBack();
    echo "\n[ERRO FATAL] Migração abortada!\n";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " linha " . $e->getLine() . "\n";
    echo "\nNenhum dado foi alterado (rollback executado).\n";
    exit(1);
}
