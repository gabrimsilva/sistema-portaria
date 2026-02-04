<?php
/**
 * Script de Migração do Banco de Dados
 * Sistema de Controle de Acesso v2.0
 * 
 * Uso: php scripts/migrate.php
 */

echo "===========================================\n";
echo "  Sistema de Controle de Acesso v2.0\n";
echo "  Script de Migração de Banco de Dados\n";
echo "===========================================\n\n";

// Carregar configuração do banco
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    // Primeiro tentar database.local.php (para servidores externos)
    $localConfigFile = __DIR__ . '/../config/database.local.php';
    if (file_exists($localConfigFile)) {
        $config = require $localConfigFile;
        $databaseUrl = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            $config['host'],
            $config['port'],
            $config['database']
        );
        $username = $config['username'];
        $password = $config['password'];
    } else {
        die("ERRO: Configure DATABASE_URL ou crie config/database.local.php\n");
    }
} else {
    $parsed = parse_url($databaseUrl);
    $databaseUrl = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $parsed['host'],
        $parsed['port'] ?? 5432,
        ltrim($parsed['path'], '/')
    );
    $username = $parsed['user'];
    $password = $parsed['pass'];
}

try {
    $pdo = new PDO($databaseUrl, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "[OK] Conexão com banco de dados estabelecida\n\n";
} catch (PDOException $e) {
    die("ERRO: Não foi possível conectar ao banco: " . $e->getMessage() . "\n");
}

// Lista de migrações
$migrations = [
    // Tabela de usuários
    "CREATE TABLE IF NOT EXISTS usuarios (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        perfil VARCHAR(50) DEFAULT 'operador',
        ativo BOOLEAN DEFAULT true,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_login TIMESTAMP,
        role_id INTEGER,
        anonymized_at TIMESTAMP
    )",
    
    // Tabela de profissionais Renner
    "CREATE TABLE IF NOT EXISTS profissionais_renner (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cpf VARCHAR(14),
        cargo VARCHAR(100),
        setor VARCHAR(100),
        foto VARCHAR(255),
        ativo BOOLEAN DEFAULT true,
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        brigadista BOOLEAN DEFAULT false
    )",
    
    // Tabela de registro de acesso
    "CREATE TABLE IF NOT EXISTS registro_acesso (
        id SERIAL PRIMARY KEY,
        profissional_renner_id INTEGER REFERENCES profissionais_renner(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        placa_veiculo VARCHAR(20),
        observacao TEXT,
        justificativa_retroativa TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    // Tabela de visitantes cadastro
    "CREATE TABLE IF NOT EXISTS visitantes_cadastro (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo_documento VARCHAR(50),
        numero_documento VARCHAR(50),
        pais_documento VARCHAR(100),
        empresa VARCHAR(255),
        foto VARCHAR(255),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validade_ate DATE,
        ativo BOOLEAN DEFAULT true
    )",
    
    // Tabela de visitantes registros
    "CREATE TABLE IF NOT EXISTS visitantes_registros (
        id SERIAL PRIMARY KEY,
        visitante_cadastro_id INTEGER REFERENCES visitantes_cadastro(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        motivo TEXT,
        setor_destino VARCHAR(100),
        pessoa_contato VARCHAR(255),
        cracha VARCHAR(50),
        observacao TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    // Tabela de prestadores cadastro
    "CREATE TABLE IF NOT EXISTS prestadores_cadastro (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo_documento VARCHAR(50),
        numero_documento VARCHAR(50),
        pais_documento VARCHAR(100),
        empresa VARCHAR(255),
        foto VARCHAR(255),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validade_ate DATE,
        ativo BOOLEAN DEFAULT true
    )",
    
    // Tabela de prestadores registros
    "CREATE TABLE IF NOT EXISTS prestadores_registros (
        id SERIAL PRIMARY KEY,
        prestador_cadastro_id INTEGER REFERENCES prestadores_cadastro(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        motivo TEXT,
        setor_destino VARCHAR(100),
        pessoa_contato VARCHAR(255),
        cracha VARCHAR(50),
        observacao TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    // Tabela de ramais
    "CREATE TABLE IF NOT EXISTS ramais (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        area VARCHAR(255),
        ramal_celular VARCHAR(50),
        tipo VARCHAR(20) DEFAULT 'interno',
        observacoes TEXT,
        ativo BOOLEAN DEFAULT true,
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Tabela de audit log
    "CREATE TABLE IF NOT EXISTS audit_log (
        id SERIAL PRIMARY KEY,
        usuario_id INTEGER REFERENCES usuarios(id),
        acao VARCHAR(100) NOT NULL,
        tabela_afetada VARCHAR(100),
        registro_id INTEGER,
        dados_anteriores JSONB,
        dados_novos JSONB,
        ip_address VARCHAR(45),
        user_agent TEXT,
        data_hora TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Tabela de consentimento LGPD
    "CREATE TABLE IF NOT EXISTS lgpd_consentimento (
        id SERIAL PRIMARY KEY,
        usuario_id INTEGER,
        tipo_consentimento VARCHAR(100) NOT NULL,
        aceito BOOLEAN DEFAULT false,
        ip_address VARCHAR(45),
        data_consentimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Índices para performance
    "CREATE INDEX IF NOT EXISTS idx_registro_acesso_profissional ON registro_acesso(profissional_renner_id)",
    "CREATE INDEX IF NOT EXISTS idx_registro_acesso_entrada ON registro_acesso(data_entrada)",
    "CREATE INDEX IF NOT EXISTS idx_registro_acesso_saida ON registro_acesso(data_saida)",
    "CREATE INDEX IF NOT EXISTS idx_visitantes_registros_cadastro ON visitantes_registros(visitante_cadastro_id)",
    "CREATE INDEX IF NOT EXISTS idx_prestadores_registros_cadastro ON prestadores_registros(prestador_cadastro_id)",
    "CREATE INDEX IF NOT EXISTS idx_ramais_area ON ramais(area)",
    "CREATE INDEX IF NOT EXISTS idx_audit_log_data ON audit_log(data_hora)"
];

echo "Executando migrações...\n\n";

$success = 0;
$errors = 0;

foreach ($migrations as $i => $sql) {
    try {
        $pdo->exec($sql);
        $success++;
        // Mostrar apenas os primeiros 50 caracteres do SQL
        $preview = substr(preg_replace('/\s+/', ' ', $sql), 0, 60);
        echo "[OK] " . ($i + 1) . ". " . $preview . "...\n";
    } catch (PDOException $e) {
        $errors++;
        echo "[ERRO] " . ($i + 1) . ". " . $e->getMessage() . "\n";
    }
}

echo "\n===========================================\n";
echo "Migrações concluídas!\n";
echo "Sucesso: $success | Erros: $errors\n";
echo "===========================================\n";

// Verificar se usuário admin existe
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
if ($stmt->fetchColumn() == 0) {
    echo "\nCriando usuário administrador padrão...\n";
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo) 
                VALUES ('Administrador', 'admin@sistema.com', '$hash', 'administrador', true)");
    echo "[OK] Usuário admin@sistema.com criado (senha: admin123)\n";
}

echo "\nMigração finalizada com sucesso!\n";
