-- =====================================================
-- Hospital do Ramiros - Banco de Dados
-- Sistema de Gestão Hospitalar
-- =====================================================

CREATE DATABASE IF NOT EXISTS hospital_urgencia
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;


USE hospital_urgencia;

-- ---------------------------------------------------
-- Tabela: pacientes
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    data_nascimento DATE NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(15) NOT NULL,
    endereco TEXT NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    convenio VARCHAR(50) DEFAULT 'Particular',
    plano VARCHAR(50) DEFAULT NULL,
    numero_convenio VARCHAR(30) DEFAULT NULL,
    contato_emergencia VARCHAR(100) DEFAULT NULL,
    telefone_emergencia VARCHAR(15) DEFAULT NULL,
    codigo_postal VARCHAR(10) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    altura DECIMAL(3,1) DEFAULT NULL,
    peso DECIMAL(5,1) DEFAULT NULL,
    alergias TEXT DEFAULT NULL,
    tipo_sanguineo VARCHAR(3) DEFAULT NULL,
    fator_rh CHAR(1) DEFAULT NULL,
    status_paciente ENUM('ativo','internado','alta','obito') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_pacientes_nome (nome_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabela: triagem
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS triagem (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    data_hora_chegada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pressao_arterial VARCHAR(10) DEFAULT NULL,
    frequencia_cardiaca INT DEFAULT NULL,
    temperatura DECIMAL(3,1) DEFAULT NULL,
    saturacao_oxigenio INT DEFAULT NULL,
    frequencia_respiratoria INT DEFAULT NULL,
    glicemia INT DEFAULT NULL,
    dor INT DEFAULT NULL,
    sintomas TEXT NOT NULL,
    prioridade ENUM('emergencia', 'alta', 'media', 'baixa') NOT NULL DEFAULT 'baixa',
    status ENUM('aguardando', 'em_atendimento', 'finalizado') NOT NULL DEFAULT 'aguardando',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    INDEX idx_triagem_paciente (paciente_id),
    INDEX idx_triagem_status (status),
    INDEX idx_triagem_prioridade (prioridade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabela: leitos_urgencia
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS leitos_urgencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_leito INT NOT NULL,
    ala VARCHAR(10) NOT NULL,
    status ENUM('disponivel', 'ocupado', 'manutencao') NOT NULL DEFAULT 'disponivel',
    paciente_id INT DEFAULT NULL,
    data_ocupacao DATETIME DEFAULT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabela: atendimentos
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS atendimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    triagem_id INT NOT NULL,
    paciente_id INT DEFAULT NULL,
    medico_responsavel VARCHAR(100) NOT NULL,
    diagnostico TEXT NOT NULL,
    prescricao TEXT DEFAULT NULL,
    exames_solicitados TEXT DEFAULT NULL,
    data_hora_atendimento DATETIME NOT NULL,
    encaminhamento VARCHAR(100) DEFAULT NULL,
    pressao_arterial VARCHAR(10) DEFAULT NULL,
    frequencia_cardiaca INT DEFAULT NULL,
    temperatura DECIMAL(3,1) DEFAULT NULL,
    saturacao_oxigenio INT DEFAULT NULL,
    cid_code VARCHAR(10) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (triagem_id) REFERENCES triagem(id) ON DELETE CASCADE,
    INDEX idx_atendimentos_data (data_hora_atendimento),
    INDEX idx_atendimentos_triagem (triagem_id),
    INDEX idx_atendimentos_paciente (paciente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabela: usuarios (login)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin','medico','enfermeiro','recepcionista') NOT NULL DEFAULT 'admin',
    nome_exibicao VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Dados Iniciais
-- ---------------------------------------------------

-- Usuário admin (senha: admin - hash bcrypt)
INSERT INTO usuarios (usuario, senha, nome_exibicao) VALUES
('admin', '$2y$10$XYIELGCl1nEFBcoMbyzkQeoG0aR3LDJfU5ljdDsjsdSGYVpcB2g.2', 'Administrador');

-- ---------------------------------------------------
-- Tabela: audit_log
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) DEFAULT NULL,
    acao VARCHAR(50) NOT NULL,
    entidade VARCHAR(50) NOT NULL,
    registro_id INT DEFAULT NULL,
    dados_antigos JSON DEFAULT NULL,
    dados_novos JSON DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entidade (entidade),
    INDEX idx_audit_registro (entidade, registro_id),
    INDEX idx_audit_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10 Leitos de urgência (alas A e B)
INSERT INTO leitos_urgencia (numero_leito, ala, status) VALUES
(1, 'A', 'disponivel'),
(2, 'A', 'disponivel'),
(3, 'A', 'disponivel'),
(4, 'A', 'disponivel'),
(5, 'A', 'disponivel'),
(6, 'B', 'disponivel'),
(7, 'B', 'disponivel'),
(8, 'B', 'disponivel'),
(9, 'B', 'disponivel'),
(10, 'B', 'disponivel');
