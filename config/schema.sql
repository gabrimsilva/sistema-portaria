-- Sistema de Controle de Acesso
-- Database schema for access control system

CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) DEFAULT 'porteiro' CHECK (perfil IN ('administrador', 'porteiro', 'seguranca')),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP
);

CREATE TABLE IF NOT EXISTS funcionarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(20) UNIQUE NOT NULL,
    cargo VARCHAR(50),
    foto VARCHAR(255),
    data_admissao DATE DEFAULT CURRENT_DATE,
    ativo BOOLEAN DEFAULT TRUE,
    email VARCHAR(100),
    telefone VARCHAR(20),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visitantes (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(20),
    empresa VARCHAR(100),
    pessoa_visitada VARCHAR(100),
    foto VARCHAR(255),
    email VARCHAR(100),
    telefone VARCHAR(20),
    qr_code VARCHAR(255),
    status VARCHAR(20) DEFAULT 'ativo' CHECK (status IN ('ativo', 'saiu', 'vencido')),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_vencimento TIMESTAMP
);

CREATE TABLE IF NOT EXISTS acessos (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('entrada','saida')),
    funcionario_id INT REFERENCES funcionarios(id) ON DELETE SET NULL,
    visitante_id INT REFERENCES visitantes(id) ON DELETE SET NULL,
    usuario_id INT REFERENCES usuarios(id) ON DELETE SET NULL,
    observacoes TEXT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_pessoa CHECK (
        (funcionario_id IS NOT NULL AND visitante_id IS NULL) OR
        (funcionario_id IS NULL AND visitante_id IS NOT NULL)
    )
);

-- Índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_acessos_data_hora ON acessos(data_hora);
CREATE INDEX IF NOT EXISTS idx_acessos_funcionario ON acessos(funcionario_id);
CREATE INDEX IF NOT EXISTS idx_acessos_visitante ON acessos(visitante_id);
CREATE INDEX IF NOT EXISTS idx_visitantes_status ON visitantes(status);
CREATE INDEX IF NOT EXISTS idx_funcionarios_ativo ON funcionarios(ativo);

-- Admin user will be created by install.php script