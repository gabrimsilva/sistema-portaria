<?php
/**
 * Corrige a validade dos pré-cadastros de prestadores migrados.
 * Define valid_until como 1 ano após o valid_from (data original do cadastro).
 * Prestadores cujo valid_until já passou ficam como expirados naturalmente.
 * 
 * Uso: php scripts/fix_prestadores_validade.php
 */

$localConfigFile = __DIR__ . '/../config/database.local.php';
if (!file_exists($localConfigFile)) {
    die("ERRO: config/database.local.php não encontrado.\n");
}
$config = require $localConfigFile;

try {
    $db = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "[OK] Conectado ao banco\n\n";
} catch (PDOException $e) {
    die("ERRO: " . $e->getMessage() . "\n");
}

echo "Corrigindo validade dos pré-cadastros de prestadores...\n";

$result = $db->exec("
    UPDATE prestadores_cadastro 
    SET valid_until = valid_from + INTERVAL '1 year'
    WHERE valid_from IS NOT NULL
");

echo "[OK] {$result} registros atualizados\n";

$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(*) FILTER (WHERE valid_until >= CURRENT_DATE) as validos,
        COUNT(*) FILTER (WHERE valid_until < CURRENT_DATE) as expirados
    FROM prestadores_cadastro
")->fetch(PDO::FETCH_ASSOC);

echo "\nResumo:\n";
echo "  Total: {$stats['total']}\n";
echo "  Válidos: {$stats['validos']}\n";
echo "  Expirados: {$stats['expirados']}\n";
