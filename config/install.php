<?php
// Database installation and setup script
require_once __DIR__ . '/config.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Starting database setup...\n";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql file");
    }
    
    // Execute schema
    $pdo->exec($schema);
    echo "Database schema created successfully.\n";
    
    // Insert or update default admin user with correct password
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo) 
        VALUES (?, ?, ?, ?, TRUE)
        ON CONFLICT (email) DO UPDATE SET 
            senha_hash = EXCLUDED.senha_hash,
            perfil = EXCLUDED.perfil,
            ativo = EXCLUDED.ativo,
            nome = EXCLUDED.nome
    ");
    
    $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt->execute(['Administrador', 'admin@sistema.com', $defaultPassword, 'administrador']);
    
    echo "Default admin user created/updated (admin@sistema.com / admin123).\n";
    echo "Database setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    echo "Please check your database connection settings.\n";
    exit(1);
} catch (Exception $e) {
    echo "Setup error: " . $e->getMessage() . "\n";
    exit(1);
}
?>